<?php

namespace Oro\Bundle\ImportExportBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\ImportExportBundle\Async\ImportExportResultSummarizer;
use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Form\Model\ExportData;
use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;
use Oro\Bundle\ImportExportBundle\Form\Type\ImportType;
use Oro\Bundle\ImportExportBundle\Handler\CsvFileHandler;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ImportExportController extends Controller
{
    /**
     * Take uploaded file and move it to temp dir
     *
     * @Route("/import", name="oro_importexport_import_form")
     * @AclAncestor("oro_importexport_import")
     * @Template("OroImportExportBundle:ImportExport:importForm.html.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function importFormAction(Request $request)
    {
        $entityName = $request->get('entity');
        $importJob = $request->get('importJob');
        $importValidateJob = $request->get('importValidateJob');

        $importForm = $this->getImportForm($entityName);

        if ($request->isMethod('POST')) {
            $importForm->submit($request);

            if ($importForm->isValid()) {
                /** @var ImportData $data */
                $data           = $importForm->getData();
                $file           = $data->getFile();
                $processorAlias = $data->getProcessorAlias();
                if ($file->getClientOriginalExtension() === 'csv') {
                    $file = $this->getCsvFileHandler()->normalizeLineEndings($file);
                }
                $fileName = $this->getFileManager()->saveImportingFile($file);

                return $this->forward(
                    'OroImportExportBundle:ImportExport:importProcess',
                    [
                        'processorAlias' => $processorAlias,
                        'fileName' => $fileName,
                        'originFileName' => $file->getClientOriginalName()
                    ],
                    $request->query->all()
                );
            }
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
     * Take uploaded file and move it to temp dir
     *
     * @Route("/import-validate", name="oro_importexport_import_validation_form")
     * @AclAncestor("oro_importexport_import")
     * @Template("OroImportExportBundle:ImportExport:importValidationForm.html.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function importValidateFormAction(Request $request)
    {
        $entityName = $request->get('entity');
        $importJob = $request->get('importJob');
        $importValidateJob = $request->get('importValidateJob');

        $importForm = $this->getImportForm($entityName);

        if ($request->isMethod('POST')) {
            $importForm->submit($request);

            if ($importForm->isValid()) {
                /** @var ImportData $data */
                $data           = $importForm->getData();
                $file           = $data->getFile();
                $processorAlias = $data->getProcessorAlias();

                $fileName = $this->getFileManager()->saveImportingFile($file);

                return $this->forward(
                    'OroImportExportBundle:ImportExport:importValidate',
                    [
                        'processorAlias' => $processorAlias,
                        'fileName' => $fileName,
                        'originFileName' => $file->getClientOriginalName()
                    ],
                    $request->query->all()
                );
            }
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
        return $this->createForm(ImportType::NAME, null, ['entityName' => $entityName]);
    }

    /**
     * Validate import data
     *
     * @Route("/import/validate/{processorAlias}", name="oro_importexport_import_validate")
     * @AclAncestor("oro_importexport_import")
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
        $token = $this->getSecurityToken()->getToken();

        $this->getMessageProducer()->send(
            Topics::PRE_HTTP_IMPORT,
            [
                'fileName' => $fileName,
                'process' => ProcessorRegistry::TYPE_IMPORT_VALIDATION,
                'originFileName' => $originFileName,
                'userId' => $this->getUser()->getId(),
                'securityToken' => $this->getTokenSerializer()->serialize($token),
                'jobName' => $jobName,
                'processorAlias' => $processorAlias,
                'options' => $this->getOptionsFromRequest($request)
            ]
        );

        return new JsonResponse([
            'flashMessage' => $this->get('translator')->trans('oro.importexport.import.validation.start'),
            'remove' => true
        ]);
    }

    /**
     * @Route("/import/process/{processorAlias}", name="oro_importexport_import_process")
     * @AclAncestor("oro_importexport_export")
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
        $token = $this->getSecurityToken()->getToken();

        $this->getMessageProducer()->send(
            Topics::PRE_HTTP_IMPORT,
            [
                'fileName' => $fileName,
                'process' => ProcessorRegistry::TYPE_IMPORT,
                'userId' => $this->getUser()->getId(),
                'originFileName' => $originFileName,
                'securityToken' => $this->getTokenSerializer()->serialize($token),
                'jobName' => $jobName,
                'processorAlias' => $processorAlias,
                'options' => $this->getOptionsFromRequest($request)
            ]
        );

        return new JsonResponse([
            'flashMessage' => $this->get('translator')->trans('oro.importexport.import.start'),
            'remove' => true
        ]);
    }

    /**
     * @Route("/export/instant/{processorAlias}", name="oro_importexport_export_instant")
     * @AclAncestor("oro_importexport_export")
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

        $this->getMessageProducer()->send(Topics::PRE_EXPORT, [
            'jobName' => $jobName,
            'processorAlias' => $processorAlias,
            'outputFilePrefix' => $filePrefix,
            'options' => $options,
            'userId' => $this->getUser()->getId(),
            'organizationId' => $token->getOrganizationContext()->getId(),
            'securityToken' => $this->getTokenSerializer()->serialize($token),
        ]);

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/export/config", name="oro_importexport_export_config")
     * @AclAncestor("oro_importexport_export")
     * @Template("OroImportExportBundle:ImportExport:configurableExport.html.twig")
     *
     * @param Request $request
     *
     * @return array|Response
     */
    public function configurableExportAction(Request $request)
    {
        $entityName = $request->get('entity');

        $exportForm = $this->createForm('oro_importexport_export', null, ['entityName' => $entityName]);

        if ($request->isMethod('POST')) {
            $exportForm->submit($request);

            if ($exportForm->isValid()) {
                /** @var ExportData $data */
                $data = $exportForm->getData();

                return $this->forward(
                    'OroImportExportBundle:ImportExport:instantExport',
                    [
                        'processorAlias' => $data->getProcessorAlias(),
                        'request' => $request
                    ]
                );
            }
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
     * @Template("OroImportExportBundle:ImportExport:configurableTemplateExport.html.twig")
     *
     * @param Request $request
     * @return array|Response
     */
    public function configurableTemplateExportAction(Request $request)
    {
        $entityName = $request->get('entity');

        $exportForm = $this->createForm('oro_importexport_export_template', null, ['entityName' => $entityName]);

        if ($request->isMethod('POST')) {
            $exportForm->submit($request);

            if ($exportForm->isValid()) {
                $data = $exportForm->getData();

                $exportTemplateResponse = $this->forward(
                    'OroImportExportBundle:ImportExport:templateExport',
                    ['processorAlias' => $data->getProcessorAlias()]
                );

                return new JsonResponse(['url' => $exportTemplateResponse->getTargetUrl()]);
            }
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
        $result  = $this->getExportHandler()->getExportResult(
            $jobName,
            $processorAlias,
            ProcessorRegistry::TYPE_EXPORT_TEMPLATE,
            'csv',
            null,
            $this->getOptionsFromRequest($request)
        );

        return $this->redirectToRoute('oro_importexport_export_download', ['fileName' => $result['file']]);
    }

    /**
     * @Route("/export/download/{fileName}", name="oro_importexport_export_download")
     *
     * @param string $fileName
     *
     * @return Response
     */
    public function downloadExportResultAction($fileName)
    {
        if (!$this->isGranted('oro_importexport_import')
            && !$this->isGranted('oro_importexport_export')
        ) {
            throw new AccessDeniedException('Insufficient permission');
        }

        return $this->getExportHandler()->handleDownloadExportResult($fileName);
    }

    /**
     * @Route("/import_export/error/{jobCode}.log", name="oro_importexport_error_log")
     *
     * @param string $jobCode
     * @return Response
     * @throws AccessDeniedException
     */
    public function errorLogAction($jobCode)
    {
        if (!$this->isGranted('oro_importexport_import')
            && !$this->isGranted('oro_importexport_export')
        ) {
            throw new AccessDeniedException('Insufficient permission');
        }

        $jobExecutor = $this->getJobExecutor();
        $errors      = array_merge(
            $jobExecutor->getJobFailureExceptions($jobCode),
            $jobExecutor->getJobErrors($jobCode)
        );
        $content     = implode("\r\n", $errors);

        return new Response($content, 200, ['Content-Type' => 'text/x-log']);
    }

    /**
     * @Route("/import_export/job-error-log/{jobId}.log", name="oro_importexport_job_error_log")
     *
     * @param $jobId
     * @return Response
     */
    public function importExportJobErrorLogAction($jobId)
    {
        if (!$this->isGranted('oro_importexport_import')
            && !$this->isGranted('oro_importexport_export')
        ) {
            throw new AccessDeniedException('Insufficient permission');
        }

        $job = $this->getDoctrine()->getManagerForClass(Job::class)->getRepository(Job::class)->find($jobId);

        if (!$job) {
            throw new NotFoundHttpException(sprintf('Job %s not found', $jobId));
        }

        $content = $this->getImportExportResultSummarizer()->getErrorLog($job);

        return new Response($content, 200, ['Content-Type' => 'text/x-log']);
    }

    /**
     * @return HttpImportHandler
     */
    protected function getImportHandler()
    {
        return $this->get('oro_importexport.handler.import.http');
    }

    /**
     * @return FileManager
     */
    protected function getFileManager()
    {
        return $this->get('oro_importexport.file.file_manager');
    }

    /**
     * @return ExportHandler
     */
    protected function getExportHandler()
    {
        return $this->get('oro_importexport.handler.export');
    }

    /**
     * @return ImportExportResultSummarizer
     */
    protected function getImportExportResultSummarizer()
    {
        return $this->get('oro_importexport.async.import_export_result_summarizer');
    }

    /**
     * @return JobExecutor
     */
    protected function getJobExecutor()
    {
        return $this->get('oro_importexport.job_executor');
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
     * @return MessageProducerInterface
     */
    protected function getMessageProducer()
    {
        return $this->get('oro_message_queue.client.message_producer');
    }

    /**
     * @return CsvFileHandler
     */
    protected function getCsvFileHandler()
    {
        return $this->get('oro_importexport.handler.csv.file');
    }

    /**
     * @return TokenStorageInterface
     */
    protected function getSecurityToken()
    {
        return $this->get('security.token_storage');
    }

    /**
     * @return TokenSerializerInterface
     */
    protected function getTokenSerializer()
    {
        return $this->get('oro_security.token_serializer');
    }
}
