<?php
namespace Oro\Bundle\MessageQueueBundle\Controller;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/jobs")
 */
class JobController extends Controller
{
    /**
     * @Template
     * @Route("/", name="oro_message_queue_root_jobs")
     * @AclAncestor("oro_message_queue_job")
     */
    public function rootJobsAction()
    {
        return [];
    }

    /**
     * @Route("/{id}", name="oro_message_queue_child_jobs", requirements={"id"="\d+"})
     * @Template
     * @AclAncestor("oro_message_queue_job")
     *
     * @param Job $job
     *
     * @return array
     */
    public function childJobsAction(Job $job)
    {
        return [
            'entity' => $job,
        ];
    }
}
