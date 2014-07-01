<?php

namespace Oro\Bundle\TrackingBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\SecurityBundle\Annotation\Acl;

/**
 * @RouteResource("tracking_data")
 * @NamePrefix("oro_api_")
 */
class TrackingDataController extends ContainerAware
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

        if (!$isSuccessful) {
            $response['errors'] = $jobResult->getFailureExceptions();
        }

        return new Response();
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
}
