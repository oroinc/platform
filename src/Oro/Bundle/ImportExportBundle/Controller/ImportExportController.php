<?php

namespace Oro\Bundle\ImportExportBundle\Controller;

use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;
use Oro\Bundle\ImportExportBundle\Handler\ImportHandler;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\File\FileSystemOperator;
use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;

class ImportExportController extends Controller
{
    /**
     * Take uploaded file and move it to temp dir
     *
     * @Route("/import", name="oro_importexport_import_form")
     * @AclAncestor("oro_importexport_import")
     * @Template
     */
    public function importFormAction()
    {
        $entityName = $this->getRequest()->get('entity');

        $importForm = $this->createForm('oro_importexport_import', null, array('entityName' => $entityName));

        if ($this->getRequest()->isMethod('POST')) {
            $importForm->submit($this->getRequest());

            if ($importForm->isValid()) {
                /** @var ImportData $data */
                $data           = $importForm->getData();
                $file           = $data->getFile();
                $processorAlias = $data->getProcessorAlias();

                /** @var ImportHandler $handler */
                $handler = $this->get('oro_importexport.handler.import');
                $handler->saveImportingFile($file, $processorAlias, 'csv');

                return $this->forward(
                    'OroImportExportBundle:ImportExport:importValidate',
                    array('processorAlias' => $processorAlias),
                    $this->getRequest()->query->all()
                );
            }
        }

        return array(
            'entityName' => $entityName,
            'form'       => $importForm->createView()
        );
    }

    /**
     * Validate import data
     *
     * @Route("/import/validate/{processorAlias}", name="oro_importexport_import_validate")
     * @AclAncestor("oro_importexport_import")
     * @Template
     */
    public function importValidateAction($processorAlias)
    {
        /** @var ImportHandler $handler */
        $handler = $this->get('oro_importexport.handler.import');

        return $handler->handleImportValidation(
            JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV,
            $processorAlias
        );
    }

    /**
     * @Route("/import/process/{processorAlias}", name="oro_importexport_import_process")
     * @AclAncestor("oro_importexport_export")
     *
     * @param string $processorAlias
     * @return Response
     */
    public function importProcessAction($processorAlias)
    {
        /** @var ImportHandler $handler */
        $handler = $this->get('oro_importexport.handler.import');

        return $handler->handleImport(
            JobExecutor::JOB_IMPORT_FROM_CSV,
            $processorAlias
        );
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
        /** @var ExportHandler $handler */
        $handler = $this->get('oro_importexport.handler.export');

        return $handler->handleExport(
            JobExecutor::JOB_EXPORT_TO_CSV,
            $processorAlias
        );
    }

    /**
     * @Route("/export/download/{fileName}", name="oro_importexport_export_download")
     * @AclAncestor("oro_importexport_export")
     */
    public function downloadExportResultAction($fileName)
    {
        /** @var ExportHandler $handler */
        $handler = $this->get('oro_importexport.handler.export');

        return $handler->handleDownloadExportResult($fileName);
    }

    /**
     * @Route("/import_export/error/{jobCode}.log", name="oro_importexport_error_log")
     * @AclAncestor("oro_importexport")
     */
    public function errorLogAction($jobCode)
    {
        /** @var JobExecutor $jobExecutor */
        $jobExecutor = $this->get('oro_importexport.job_executor');
        $errors  = array_merge(
            $jobExecutor->getJobFailureExceptions($jobCode),
            $jobExecutor->getJobErrors($jobCode)
        );
        $content = implode("\r\n", $errors);

        return new Response($content, 200, array('Content-Type' => 'text/x-log'));
    }
}
