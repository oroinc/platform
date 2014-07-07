<?php

namespace Oro\Bundle\TrackingBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;

/**
 * @Route("/tracking/data")
 */
class TrackingDataController extends Controller
{
    /**
     * @Route("/create", name="oro_tracking_data_create")
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
                    'entityName'     => $this->container->getParameter('oro_tracking.tracking_data.class'),
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

        return new JsonResponse($response, 201);
    }

    /**
     * @return JobExecutor
     */
    protected function getJobExecutor()
    {
        return $this->container->get('oro_importexport.job_executor');
    }
}
