<?php
namespace Oro\Bundle\MessageQueueBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for MQ jobs.
 */
class JobController extends AbstractFOSRestController
{
    /**
     * Interrupt Root Job
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @ApiDoc(description="Interrupt Root Job", resource=true)
     * @AclAncestor("oro_message_queue_job")
     *
     * @param Job $job
     *
     * @return Response
     */
    public function interruptRootJobAction(Job $job)
    {
        $this->getJobProcessor()->interruptRootJob($job);

        return $this->handleView($this->view(
            [
                'successful' => true,
                'message' => $this->get('translator')->trans('oro.message_queue_job.rest.job_interrupted'),
            ],
            Response::HTTP_OK
        ));
    }

    /**
     * @return \Oro\Component\MessageQueue\Job\JobProcessor
     */
    private function getJobProcessor()
    {
        return $this->get('oro_message_queue.job.processor');
    }
}
