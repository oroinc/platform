<?php

namespace Oro\Bundle\ImportExportBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Handler\HttpImportHandler;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class ImportExportController extends Controller
{
    /**
     * Take uploaded file and move it to temp dir
     *
     * @Route("/import", name="oro_importexport_import_form")
     * @AclAncestor("oro_importexport_import")
     * @Template
     *
     * @return array
     */
    public function importFormAction()
    {
        $entityName = $this->getRequest()->get('entity');

        $importForm = $this->createForm('oro_importexport_import', null, ['entityName' => $entityName]);

        if ($this->getRequest()->isMethod('POST')) {
            $importForm->submit($this->getRequest());

            if ($importForm->isValid()) {
                /** @var ImportData $data */
                $data           = $importForm->getData();
                $file           = $data->getFile();
                $processorAlias = $data->getProcessorAlias();

                $this->getImportHandler()->saveImportingFile($file, $processorAlias, 'csv');

                return $this->forward(
                    'OroImportExportBundle:ImportExport:importValidate',
                    ['processorAlias' => $processorAlias],
                    $this->getRequest()->query->all()
                );
            }
        }

        return [
            'entityName' => $entityName,
            'form'       => $importForm->createView()
        ];
    }

    /**
     * Validate import data
     *
     * @Route("/import/validate/{processorAlias}", name="oro_importexport_import_validate")
     * @AclAncestor("oro_importexport_import")
     * @Template
     *
     * @param string $processorAlias
     * @return array
     */
    public function importValidateAction($processorAlias)
    {
        return $this->getImportHandler()->handleImportValidation(
            JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV,
            $processorAlias
        );
    }

    /**
     * @Route("/import/process/{processorAlias}", name="oro_importexport_import_process")
     * @AclAncestor("oro_importexport_export")
     *
     * @param string $processorAlias
     * @return JsonResponse
     */
    public function importProcessAction($processorAlias)
    {
        $result = $this->getImportHandler()->handleImport(
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $processorAlias
        );

        return new JsonResponse($result);
    }

    /**
     * @Route("/export/instant/{processorAlias}", name="oro_importexport_export_instant")
     * @AclAncestor("oro_importexport_export")
     *
     * @param string $processorAlias
     * @return Response
     */
    public function instantExportAction($processorAlias)
    {
        return $this->getExportHandler()->handleExport(
            JobExecutor::JOB_EXPORT_TO_CSV,
            $processorAlias,
            ProcessorRegistry::TYPE_EXPORT,
            'csv',
            null,
            ['organization' => $this->get('oro_security.security_facade')->getOrganization()]
        );
    }

    /**
     * @Route("/export/template/{processorAlias}", name="oro_importexport_export_template")
     * @AclAncestor("oro_importexport_export")
     *
     * @param string $processorAlias
     * @return Response
     */
    public function templateExportAction($processorAlias)
    {
        $result = $this->getExportHandler()->getExportResult(
            JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV,
            $processorAlias,
            ProcessorRegistry::TYPE_EXPORT_TEMPLATE
        );

        return $this->redirect($result['url']);
    }

    /**
     * @Route("/export/download/{fileName}", name="oro_importexport_export_download")
     * @AclAncestor("oro_importexport_export")
     *
     * @param string $fileName
     * @return Response
     */
    public function downloadExportResultAction($fileName)
    {
        return $this->getExportHandler()->handleDownloadExportResult($fileName);
    }

    /**
     * @Route("/import_export/error/{jobCode}.log", name="oro_importexport_error_log")
     * @AclAncestor("oro_importexport")
     *
     * @param string $jobCode
     * @return Response
     */
    public function errorLogAction($jobCode)
    {
        $jobExecutor = $this->getJobExecutor();
        $errors  = array_merge(
            $jobExecutor->getJobFailureExceptions($jobCode),
            $jobExecutor->getJobErrors($jobCode)
        );
        $content = implode("\r\n", $errors);

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
     * @return ExportHandler
     */
    protected function getExportHandler()
    {
        return $this->get('oro_importexport.handler.export');
    }

    /**
     * @return JobExecutor
     */
    protected function getJobExecutor()
    {
        return $this->get('oro_importexport.job_executor');
    }
}
