<?php

namespace Oro\Bundle\ImportExportBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationRegistryInterface;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Exception\ImportExportExpiredException;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Form\Model\ExportData;
use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;
use Oro\Bundle\ImportExportBundle\Form\Type\ExportTemplateType;
use Oro\Bundle\ImportExportBundle\Form\Type\ExportType;
use Oro\Bundle\ImportExportBundle\Form\Type\ImportType;
use Oro\Bundle\ImportExportBundle\Handler\CsvFileHandler;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for import/export actions
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImportExportController extends AbstractController
{
    #[Route(path: '/import', name: 'oro_importexport_import_form')]
    #[Template('@OroImportExport/ImportExport/importForm.html.twig')]
    #[AclAncestor('oro_importexport_import')]
    public function importFormAction(Request $request): array|Response
    {
        $entityName = $request->get('entity');
        $importJob = $request->get('importJob');
        $importValidateJob = $request->get('importValidateJob');

        $importForm = $this->getImportForm($entityName);

        if ($this->handleRequest($request, $importForm)) {
            [$originalFileName, $processorAlias, $fileName] = $this->getImportData($importForm);

            return $this->forward(
                __CLASS__ . '::importProcessAction',
                [
                    'processorAlias' => $processorAlias,
                    'fileName' => $fileName,
                    'originFileName' => $originalFileName
                ],
                $request->query->all()
            );
        }

        return [
            'entityName' => $entityName,
            'form' => $importForm->createView(),
            'options' => $this->getOptionsFromRequest($request),
            'importJob' => $importJob,
            'importValidateJob' => $importValidateJob
        ];
    }

    #[Route(path: '/import_validate_export', name: 'oro_importexport_import_validate_export_template_form')]
    #[Template('@OroImportExport/ImportExport/widget/importValidateExportTemplate.html.twig')]
    #[AclAncestor('oro_importexport_import')]
    public function importValidateExportTemplateFormAction(Request $request): array|Response
    {
        $configAlias = $request->get('alias');
        if (!$configAlias) {
            throw new BadRequestHttpException('Alias should be provided in request');
        }

        $isValidate = $request->request->getBoolean('isValidateJob');
        $entityName = $request->get('entity');
        $configurationsByAlias = $this->getImportConfigurations($configAlias);
        $configsWithForm = [];
        $importForm = null;
        $aclChecker = $this->container->get('security.authorization_checker');
        $entityVisibility = [];

        foreach ($configurationsByAlias as $configuration) {
            $className = $configuration->getEntityClass();
            $oid = new ObjectIdentity('entity', $className);
            $entityVisibility[$className] = $aclChecker->isGranted('CREATE', $oid)
                || $aclChecker->isGranted('EDIT', $oid);
            $form = $this->getImportForm(
                $className,
                $configuration->getImportFileMimeTypes(),
                $configuration->getImportFileAcceptAttribute()
            );

            if ($className === $entityName) {
                $importForm = $form;
            }

            $configsWithForm[] = ['form' => $form, 'configuration' => $configuration];
        }

        if ($entityName && null !== $importForm) {
            if ($this->handleRequest($request, $importForm)) {
                [$originalFileName, $processorAlias, $fileName] = $this->getImportData($importForm);

                return $this->forward(
                    $isValidate ? __CLASS__ . '::importValidateAction' : __CLASS__ . '::importProcessAction',
                    [
                        'processorAlias' => $processorAlias,
                        'fileName' => $fileName,
                        'originFileName' => $originalFileName,
                        'importProcessorTopicName' => $request->get('importProcessorTopicName'),
                    ],
                    $request->query->all()
                );
            }
        }

        return [
            'options' => $this->getOptionsFromRequest($request),
            'alias' => $configAlias,
            'configsWithForm' => $configsWithForm,
            'chosenEntityName' => $entityName,
            'entityVisibility' => $entityVisibility
        ];
    }

    #[Route(path: '/import-validate', name: 'oro_importexport_import_validation_form')]
    #[Template('@OroImportExport/ImportExport/importValidationForm.html.twig')]
    #[AclAncestor('oro_importexport_import')]
    public function importValidateFormAction(Request $request): array|Response
    {
        $entityName = $request->get('entity');
        $importJob = $request->get('importJob');
        $importValidateJob = $request->get('importValidateJob');

        $importForm = $this->getImportForm($entityName);

        if ($this->handleRequest($request, $importForm)) {
            /** @var ImportData $data */
            $data = $importForm->getData();
            /** @var UploadedFile $file */
            $file = $data->getFile();

            $fileName = $this->getFileManager()->saveImportingFile($file);

            return $this->forward(
                __CLASS__ . '::importValidateAction',
                [
                    'processorAlias' => $data->getProcessorAlias(),
                    'fileName' => $fileName,
                    'originFileName' => $file->getClientOriginalName()
                ],
                $request->query->all()
            );
        }

        return [
            'entityName' => $entityName,
            'form' => $importForm->createView(),
            'options' => $this->getOptionsFromRequest($request),
            'importJob' => $importJob,
            'importValidateJob' => $importValidateJob
        ];
    }

    #[Route(path: '/import/validate/{processorAlias}', name: 'oro_importexport_import_validate', methods: ['POST'])]
    #[AclAncestor('oro_importexport_import')]
    #[CsrfProtection()]
    public function importValidateAction(Request $request, string $processorAlias): JsonResponse
    {
        $jobName = $request->get('importValidateJob', JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV);
        $fileName = $request->get('fileName', null);
        $originFileName = $request->get('originFileName', null);

        $this->getMessageProducer()->send(
            PreImportTopic::getName(),
            [
                'fileName' => $fileName,
                'process' => ProcessorRegistry::TYPE_IMPORT_VALIDATION,
                'originFileName' => $originFileName,
                'userId' => $this->getUser()->getId(),
                'jobName' => $jobName,
                'processorAlias' => $processorAlias,
                'options' => $this->getOptionsFromRequest($request)
            ]
        );

        return new JsonResponse([
            'success' => true,
            'message' => $this->getTranslator()->trans('oro.importexport.import.validate.success.message')
        ]);
    }

    #[Route(path: '/import/process/{processorAlias}', name: 'oro_importexport_import_process', methods: ['POST'])]
    #[AclAncestor('oro_importexport_export')]
    #[CsrfProtection()]
    public function importProcessAction(Request $request, string $processorAlias): JsonResponse
    {
        $jobName = $request->get('importJob', JobExecutor::JOB_IMPORT_FROM_CSV);
        $fileName = $request->get('fileName', null);
        $originFileName = $request->get('originFileName', null);
        $importProcessorTopicName  = $request->get('importProcessorTopicName') ?: PreImportTopic::getName();

        $this->getMessageProducer()->send(
            $importProcessorTopicName,
            [
                'fileName' => $fileName,
                'process' => ProcessorRegistry::TYPE_IMPORT,
                'userId' => $this->getUser()->getId(),
                'originFileName' => $originFileName,
                'jobName' => $jobName,
                'processorAlias' => $processorAlias,
                'options' => $this->getOptionsFromRequest($request)
            ]
        );

        return new JsonResponse([
            'success' => true,
            'message' => $this->getTranslator()->trans('oro.importexport.import.success.message')
        ]);
    }

    #[Route(path: '/export/instant/{processorAlias}', name: 'oro_importexport_export_instant', methods: ['POST'])]
    #[AclAncestor('oro_importexport_export')]
    #[CsrfProtection()]
    public function instantExportAction(string $processorAlias, Request $request): Response
    {
        $jobName = $request->get('exportJob', JobExecutor::JOB_EXPORT_TO_CSV);
        $filePrefix = $request->get('filePrefix', null);
        $options = $this->getOptionsFromRequest($request);
        $token = $this->getTokenStorage()->getToken();

        $this->getMessageProducer()->send(PreExportTopic::getName(), [
            'jobName' => $jobName,
            'processorAlias' => $processorAlias,
            'outputFilePrefix' => $filePrefix,
            'options' => $options,
            'userId' => $this->getUser()->getId(),
            'organizationId' => $token->getOrganization()->getId(),
        ]);

        return new JsonResponse(['success' => true]);
    }

    #[Route(path: '/export/config', name: 'oro_importexport_export_config')]
    #[Template('@OroImportExport/ImportExport/configurableExport.html.twig')]
    #[AclAncestor('oro_importexport_export')]
    public function configurableExportAction(Request $request): array|Response
    {
        $entityName = $request->get('entity');

        $exportForm = $this->createForm(ExportType::class, null, [
            'entityName' => $entityName,
            'processorAlias' => $request->get('processorAlias')
        ]);

        if ($this->handleRequest($request, $exportForm)) {
            /** @var ExportData $data */
            $data = $exportForm->getData();

            return $this->forward(
                __CLASS__ . '::instantExportAction',
                [
                    'processorAlias' => $data->getProcessorAlias(),
                    'request' => $request
                ]
            );
        }

        return [
            'entityName' => $entityName,
            'form' => $exportForm->createView(),
            'options' => $this->getOptionsFromRequest($request),
            'exportJob' => $request->get('exportJob')
        ];
    }

    #[Route(path: '/export/template/config', name: 'oro_importexport_export_template_config')]
    #[Template('@OroImportExport/ImportExport/configurableTemplateExport.html.twig')]
    #[AclAncestor('oro_importexport_export')]
    public function configurableTemplateExportAction(Request $request): array|Response
    {
        $entityName = $request->get('entity');

        $exportForm = $this->createForm(ExportTemplateType::class, null, ['entityName' => $entityName]);

        if ($this->handleRequest($request, $exportForm)) {
            $data = $exportForm->getData();

            $exportTemplateResponse = $this->forward(
                __CLASS__ . '::templateExport',
                ['processorAlias' => $data->getProcessorAlias()]
            );

            return new JsonResponse(['url' => $exportTemplateResponse->getTargetUrl()]);
        }

        return [
            'entityName' => $entityName,
            'form' => $exportForm->createView(),
            'options' => $this->getOptionsFromRequest($request)
        ];
    }

    #[Route(path: '/export/template/{processorAlias}', name: 'oro_importexport_export_template')]
    #[AclAncestor('oro_importexport_import')]
    public function templateExportAction(string $processorAlias, Request $request): Response
    {
        $jobName = $request->get('exportTemplateJob', JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV);
        $result = $this->getExportHandler()->getExportResult(
            $jobName,
            $processorAlias,
            ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
            'entity_export_template_to_json' === $jobName ? 'json' : 'csv',
            'import_template',
            $this->getOptionsFromRequest($request)
        );

        return $this->getExportHandler()->handleDownloadExportResult($result['file']);
    }

    #[Route(
        path: '/export/download/{jobId}',
        name: 'oro_importexport_export_download',
        requirements: ['jobId' => '\d+']
    )]
    public function downloadExportResultAction(
        #[MapEntity(mapping: ['jobId' => 'jobId'])]
        ImportExportResult $result
    ): Response {
        if (!$this->isGranted('VIEW', $result)) {
            throw new AccessDeniedException('Insufficient permission');
        }

        if ($result->isExpired()) {
            throw new ImportExportExpiredException();
        }

        return $this->getExportHandler()->handleDownloadExportResult($result->getFilename());
    }

    #[Route(
        path: '/import_export/job-error-log/{jobId}.log',
        name: 'oro_importexport_job_error_log',
        requirements: ['jobId' => '\d+']
    )]
    public function importExportJobErrorLogAction(
        #[MapEntity(mapping: ['jobId' => 'jobId'])]
        ImportExportResult $result
    ): Response {
        if (!$this->isGranted('VIEW', $result)) {
            throw new AccessDeniedException('Insufficient permission');
        }

        if ($result->isExpired()) {
            throw new ImportExportExpiredException();
        }

        $jobRepository = $this->container->get(ManagerRegistry::class)->getRepository(Job::class);
        $job = $jobRepository->find($result->getJobId());

        if (!$job) {
            throw new NotFoundHttpException(sprintf('Job %s not found', $result->getJobId()));
        }

        $content = $this->container->get(ImportExportResultSummarizer::class)->getErrorLog($job);

        return new Response($content, 200, ['Content-Type' => 'text/x-log']);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                MessageProducerInterface::class,
                CsvFileHandler::class,
                FileManager::class,
                ImportExportConfigurationRegistryInterface::class,
                ExportHandler::class,
                ImportExportResultSummarizer::class,
                ManagerRegistry::class,
                TokenStorageInterface::class,
                FeatureChecker::class
            ]
        );
    }

    /**
     * @return array [original file name, processor alias, file name]
     */
    private function getImportData(FormInterface $importForm): array
    {
        /** @var ImportData $data */
        $data = $importForm->getData();
        /** @var UploadedFile $file */
        $file = $data->getFile();
        if ($file->getClientOriginalExtension() === 'csv') {
            $file = $this->getCsvFileHandler()->normalizeLineEndings($file);
            $fileName = $this->getFileManager()->saveImportingFile($file);
            @unlink($file->getRealPath());
        } else {
            $fileName = $this->getFileManager()->saveImportingFile($file);
        }

        return [$file->getClientOriginalName(), $data->getProcessorAlias(), $fileName];
    }

    private function getImportForm(
        string $entityName,
        ?array $mimeTypes = null,
        ?string $fileAcceptAttribute = null
    ): FormInterface {
        return $this->createForm(
            ImportType::class,
            null,
            ['entityName' => $entityName, 'fileMimeTypes' => $mimeTypes, 'fileAcceptAttribute' => $fileAcceptAttribute]
        );
    }

    private function getOptionsFromRequest(Request $request): array
    {
        $options = $request->get('options', []);

        if (!is_array($options)) {
            throw new InvalidArgumentException('Request parameter "options" must be array.');
        }

        return $options;
    }

    private function handleRequest(Request $request, FormInterface $form): bool
    {
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return ImportExportConfigurationInterface[]
     */
    private function getImportConfigurations(string $configAlias): array
    {
        $configurationsByAlias = $this->container->get(ImportExportConfigurationRegistryInterface::class)
            ->getConfigurations($configAlias);

        $featureChecker = $this->container->get(FeatureChecker::class);

        return array_filter(
            $configurationsByAlias,
            function (ImportExportConfigurationInterface $configuration) use ($featureChecker) {
                if (
                    $configuration->getFeatureName()
                    && !$featureChecker->isFeatureEnabled($configuration->getFeatureName())
                ) {
                    return null;
                }

                return $configuration->getImportProcessorAlias();
            }
        );
    }

    private function getFileManager(): FileManager
    {
        return $this->container->get(FileManager::class);
    }

    private function getExportHandler(): ExportHandler
    {
        return $this->container->get(ExportHandler::class);
    }

    private function getCsvFileHandler(): CsvFileHandler
    {
        return $this->container->get(CsvFileHandler::class);
    }

    private function getMessageProducer(): MessageProducerInterface
    {
        return $this->container->get(MessageProducerInterface::class);
    }

    private function getTokenStorage(): TokenStorageInterface
    {
        return $this->container->get(TokenStorageInterface::class);
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }
}
