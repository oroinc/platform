<?php

namespace Oro\Bundle\TrackingBundle\Controller\Api\Rest;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @RouteResource("tracking_data")
 * @NamePrefix("oro_api_")
 */
class TrackingDataController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Create TrackingData entity. Decouple TrackingEvent if possible",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_tracking_data_create",
     *      type="entity",
     *      class="OroTrackingBundle:TrackingData",
     *      permission="CREATE"
     * )
     * @param Request $request
     * @return Response
     */
    public function createAction(Request $request)
    {
        $jobResult = $this->getJobExecutor()->executeJob(
            ProcessorRegistry::TYPE_IMPORT,
            'import_request_to_database',
            [
                'import' => [
                    'entityName'     => $this->getClassName(),
                    'processorAlias' => 'oro_tracking.processor.data',
                    'data'           => $request->query->all(),
                ]
            ]
        );

        $isSuccessful = $jobResult->isSuccessful();
        $response     = [
            'success' => $isSuccessful
        ];

        $code = Codes::HTTP_OK;

        if (!$isSuccessful) {
            $response['errors'] = $jobResult->getFailureExceptions();

            $code = Codes::HTTP_BAD_REQUEST;
        }

        return $this->handleView(
            $this->view($response, $code)
        );
    }

    /**
     * @return JobExecutor
     */
    protected function getJobExecutor()
    {
        return $this->container->get('oro_importexport.job_executor');
    }

    /**
     * @return string
     */
    protected function getClassName()
    {
        return $this->container->getParameter('oro_tracking.tracking_data.class');
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \Exception('Not implemented');
    }
}
