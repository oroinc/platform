<?php
namespace Oro\Bundle\MessageQueueBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\NamePrefix("oro_api_message_queue_job_")
 */
class JobController extends FOSRestController
{
    /**
     * Interrupt Root Job
     *
     * Returns
     * - HTTP_OK (200)
     *
     * @Rest\Get(
     *      "/api/rest/{version}/message-queue/job/interrupt/{id}",
     *      requirements={"version"="latest|v1"},
     *      defaults={"version"="latest", "_format"="json"}
     * )
     * @ApiDoc(description="Interrupt Root Job", resource=true)
     * @AclAncestor("oro_message_queue_job")
     *
     * @param Job $job
     *
     * @return Response
     */
    public function interruptRootJobAction(Job $job)
    {
        $this->getJobProcessor()->interruptRootJob($job, false);

        return $this->handleView($this->view(
            [
                'successful' => true,
                'message' => $this->get('translator')->trans('oro.message_queue_job.rest.job_interrupted'),
            ],
            Codes::HTTP_OK
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
