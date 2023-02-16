<?php

namespace Oro\Bundle\ImportExportBundle\Controller;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
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
use Oro\Bundle\ImportExportBundle\Twig\GetImportExportConfigurationExtension;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for import/export actions
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * Responsible for the import and export
 */
class ImportExportController extends AbstractController
{
    /**
     * Take uploaded file and move it to temp dir
     *
     * @Route("/import", name="oro_importexport_import_form")
     * @AclAncestor("oro_importexport_import")
     * @Template("@OroImportExport/ImportExport/importForm.html.twig")
     *
     * @param Request $request
     *
     * @return array|Response
     */
    public function importFormAction(Request $request)
    {
        $entityName = $request->get('entity');
        $importJob = $request->get('importJob');
        $importValidateJob = $request->get('importValidateJob');

        $importForm = $this->getImportForm($entityName);

        if ($this->handleRequest($request, $importForm)) {
            /** @var ImportData $data */
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

    /**
     * @Route("/import_validate_export", name="oro_importexport_import_validate_export_template_form")
     * @AclAncestor("oro_importexport_import")
     * @Template("@OroImportExport/ImportExport/widget/importValidateExportTemplate.html.twig")
     *
     * @param Request $request
     *
     * @return array|Response
     */
    public function importValidateExportTemplateFormAction(Request $request)
    {
        $configAlias = $request->get('alias');
        if (!$configAlias) {
            throw new BadRequestHttpException('Alias should be provided in request');
        }

        $isValidate = $request->request->getBoolean('isValidateJob', false);
        $entityName = $request->get('entity');
        $configurationsByAlias = $this->getImportConfigurations($configAlias);
        $configsWithForm = [];
        $importForm = null;
        $aclChecker = $this->get('security.authorization_checker');
        $entityVisibility = [];

        foreach ($configurationsByAlias as $configuration) {
            $className = $configuration->getEntityClass();
            $oid = new ObjectIdentity('entity', $className);
            $entityVisibility[$className] = $aclChecker->isGranted('CREATE', $oid)
                || $aclChecker->isGranted('EDIT', $oid);
            $form = $this->getImportForm($className);

            if ($className === $entityName) {
                $importForm = $form;
            }

            $configsWithForm[] = ['form' => $form, 'configuration' => $configuration];
        }

        if ($entityName && null !== $importForm) {
            if ($this->handleRequest($request, $importForm)) {
                /** @var ImportData $data */
                [$originalFileName, $processorAlias, $fileName] = $this->getImportData($importForm);

                $importForward = ImportExportController::class . '::importProcessAction';
                $validateForward = ImportExportController::class . '::importValidateAction';

                $forward = $isValidate ? $validateForward : $importForward;

                return $this->forward(
                    $forward,
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

    private function getImportConfigurations(string $configAlias): array
    {
        $configurationsByAlias = $this
            ->get(GetImportExportConfigurationExtension::class)
            ->getConfiguration($configAlias);

        return array_filter(
            $configurationsByAlias,
            function (ImportExportConfigurationInterface $configuration) {
                return $configuration->getImportProcessorAlias();
            }
        );
    }

    /**
     * Take uploaded file and move it to temp dir
     *
     * @Route("/import-validate", name="oro_importexport_import_validation_form")
     * @AclAncestor("oro_importexport_import")
     * @Template("@OroImportExport/ImportExport/importValidationForm.html.twig")
     *
     * @param Request $request
     *
     * @return array|Response
     */
    public function importValidateFormAction(Request $request)
    {
        $entityName = $request->get('entity');
        $importJob = $request->get('importJob');
        $importValidateJob = $request->get('importValidateJob');

        $importForm = $this->getImportForm($entityName);

        if ($this->handleRequest($request, $importForm)) {
            /** @var ImportData $data */
            $data = $importForm->getData();
            $file = $data->getFile();
            $processorAlias = $data->getProcessorAlias();

            $fileName = $this->getFileManager()->saveImportingFile($file);

            return $this->forward(
                ImportExportController::class . '::importValidateAction',
                [
                    'processorAlias' => $processorAlias,
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

    /**
     * @param string $entityName
     * @return FormInterface
     */
    protected function getImportForm($entityName)
    {
        return $this->createForm(ImportType::class, null, ['entityName' => $entityName]);
    }

    /**
     * Validate import data
     * Called by importValidateExportTemplateFormAction with forward
     *
     * @Route("/import/validate/{processorAlias}", name="oro_importexport_import_validate", methods={"POST"})
     * @AclAncestor("oro_importexport_import")
     * @CsrfProtection()
     *
     * @param Request $request
     * @param string $processorAlias
     *
     * @return JsonResponse
     */
    public function importValidateAction(Request $request, $processorAlias)
    {
        $jobName = $request->get('importValidateJob', JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV);
        $fileName = $request->get('fileName', null);
        $originFileName = $request->get('originFileName', null);

        $this->get(MessageProducerInterface::class)->send(
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
            'message' => $this
                ->get(TranslatorInterface::class)
                ->trans('oro.importexport.import.validate.success.message'),
        ]);
    }

    /**
     * Execute import process
     * Called by importValidateExportTemplateFormAction with forward
     *
     * @Route("/import/process/{processorAlias}", name="oro_importexport_import_process", methods={"POST"})
     * @AclAncestor("oro_importexport_export")
     * @CsrfProtection()
     *
     * @param string $processorAlias
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function importProcessAction(Request $request, $processorAlias)
    {
        $jobName = $request->get('importJob', JobExecutor::JOB_IMPORT_FROM_CSV);
        $fileName = $request->get('fileName', null);
        $originFileName = $request->get('originFileName', null);
        $importProcessorTopicName  = $request->get('importProcessorTopicName') ?: PreImportTopic::getName();

        $this->get(MessageProducerInterface::class)->send(
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
            'message' => $this->get(TranslatorInterface::class)->trans('oro.importexport.import.success.message'),
        ]);
    }

    /**
     * @Route("/export/instant/{processorAlias}", name="oro_importexport_export_instant", methods={"POST"})
     * @AclAncestor("oro_importexport_export")
     * @CsrfProtection()
     *
     * @param string $processorAlias
     * @param Request $request
     * @return Response
     */
    public function instantExportAction($processorAlias, Request $request)
    {
        $jobName = $request->get('exportJob', JobExecutor::JOB_EXPORT_TO_CSV);
        $filePrefix = $request->get('filePrefix', null);
        $options = $this->getOptionsFromRequest($request);
        $token = $this->getSecurityToken()->getToken();

        $this->get(MessageProducerInterface::class)->send(PreExportTopic::getName(), [
            'jobName' => $jobName,
            'processorAlias' => $processorAlias,
            'outputFilePrefix' => $filePrefix,
            'options' => $options,
            'userId' => $this->getUser()->getId(),
            'organizationId' => $token->getOrganization()->getId(),
        ]);

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/export/config", name="oro_importexport_export_config")
     * @AclAncestor("oro_importexport_export")
     * @Template("@OroImportExport/ImportExport/configurableExport.html.twig")
     *
     * @param Request $request
     *
     * @return array|Response
     */
    public function configurableExportAction(Request $request)
    {
        $entityName = $request->get('entity');

        $exportForm = $this->createForm(ExportType::class, null, [
            'entityName' => $entityName,
            'processorAlias' => $request->get('processorAlias') ?? null
        ]);

        if ($this->handleRequest($request, $exportForm)) {
            /** @var ExportData $data */
            $data = $exportForm->getData();

            return $this->forward(
                ImportExportController::class . '::instantExportAction',
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

    /**
     * @Route("/export/template/config", name="oro_importexport_export_template_config")
     * @AclAncestor("oro_importexport_export")
     * @Template("@OroImportExport/ImportExport/configurableTemplateExport.html.twig")
     *
     * @param Request $request
     * @return array|Response
     */
    public function configurableTemplateExportAction(Request $request)
    {
        $entityName = $request->get('entity');

        $exportForm = $this->createForm(ExportTemplateType::class, null, ['entityName' => $entityName]);

        if ($this->handleRequest($request, $exportForm)) {
            $data = $exportForm->getData();

            $exportTemplateResponse = $this->forward(
                ImportExportController::class . '::templateExport',
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

    /**
     * @Route("/export/template/{processorAlias}", name="oro_importexport_export_template")
     * @AclAncestor("oro_importexport_import")
     *
     * @param string $processorAlias
     * @param Request $request
     *
     * @return Response
     */
    public function templateExportAction($processorAlias, Request $request)
    {
        $jobName = $request->get('exportTemplateJob', JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV);
        $result = $this->getExportHandler()->getExportResult(
            $jobName,
            $processorAlias,
            ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
            'csv',
            'import_template',
            $this->getOptionsFromRequest($request)
        );

        return $this->getExportHandler()->handleDownloadExportResult($result['file']);
    }

    /**
     * @Route("/export/download/{jobId}", name="oro_importexport_export_download", requirements={"jobId"="\d+"})
     * @ParamConverter("result", options={"mapping": {"jobId": "jobId"}})
     *
     * @param ImportExportResult $result
     *
     * @return Response
     */
    public function downloadExportResultAction(ImportExportResult $result)
    {
        if (!$this->isGranted('VIEW', $result)) {
            throw new AccessDeniedException('Insufficient permission');
        }

        if ($result->isExpired()) {
            throw new ImportExportExpiredException();
        }

        return $this->getExportHandler()->handleDownloadExportResult($result->getFilename());
    }

    /**
     * @Route(
     *     "/import_export/job-error-log/{jobId}.log",
     *     name="oro_importexport_job_error_log",
     *     requirements={"jobId"="\d+"}
     * )
     * @ParamConverter("result", options={"mapping": {"jobId": "jobId"}})
     *
     * @param ImportExportResult $result
     * @return Response
     */
    public function importExportJobErrorLogAction(ImportExportResult $result)
    {
        if (!$this->isGranted('VIEW', $result)) {
            throw new AccessDeniedException('Insufficient permission');
        }

        if ($result->isExpired()) {
            throw new ImportExportExpiredException();
        }

        $jobRepository = $this->getDoctrine()->getManagerForClass(Job::class)->getRepository(Job::class);
        $job = $jobRepository->find($result->getJobId());

        if (!$job) {
            throw new NotFoundHttpException(sprintf('Job %s not found', $result->getJobId()));
        }

        $content = $this->get(ImportExportResultSummarizer::class)->getErrorLog($job);

        return new Response($content, 200, ['Content-Type' => 'text/x-log']);
    }

    /**
     * @return FileManager
     */
    protected function getFileManager()
    {
        return $this->get(FileManager::class);
    }

    /**
     * @return ExportHandler
     */
    protected function getExportHandler()
    {
        return $this->get(ExportHandler::class);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function getOptionsFromRequest(Request $request)
    {
        $options = $request->get('options', []);

        if (!is_array($options)) {
            throw new InvalidArgumentException('Request parameter "options" must be array.');
        }

        return $options;
    }

    /**
     * @return CsvFileHandler
     */
    protected function getCsvFileHandler()
    {
        return $this->get(CsvFileHandler::class);
    }

    /**
     * @return TokenStorageInterface
     */
    protected function getSecurityToken()
    {
        return $this->get('security.token_storage');
    }

    /**
     * @param Request $request
     * @param FormInterface $form
     *
     * @return bool
     */
    protected function handleRequest(Request $request, FormInterface $form)
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
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                MessageProducerInterface::class,
                CsvFileHandler::class,
                FileManager::class,
                GetImportExportConfigurationExtension::class,
                ExportHandler::class,
                ImportExportResultSummarizer::class,
            ]
        );
    }

    private function getImportData(FormInterface $importForm): array
    {
        $data = $importForm->getData();
        $file = $data->getFile();
        $originalFileName = $file->getClientOriginalName();
        $processorAlias = $data->getProcessorAlias();

        if ($file->getClientOriginalExtension() === 'csv') {
            $file = $this->getCsvFileHandler()->normalizeLineEndings($file);
            $fileName = $this->getFileManager()->saveImportingFile($file);
            @unlink($file->getRealPath());
        } else {
            $fileName = $this->getFileManager()->saveImportingFile($file);
        }

        return [$originalFileName, $processorAlias, $fileName];
    }
}
