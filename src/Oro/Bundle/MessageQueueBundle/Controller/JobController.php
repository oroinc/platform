<?php

namespace Oro\Bundle\MessageQueueBundle\Controller;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The controller for Job entity.
 */
#[Route(path: '/jobs')]
class JobController extends AbstractController
{
    #[Route(path: '/', name: 'oro_message_queue_root_jobs')]
    #[Template('@OroMessageQueue/Job/rootJobs.html.twig')]
    #[AclAncestor('oro_message_queue_job')]
    public function rootJobsAction()
    {
        return [];
    }

    /**
     * @param Job $job
     * @return array
     */
    #[Route(path: '/{id}', name: 'oro_message_queue_child_jobs', requirements: ['id' => '\d+'])]
    #[Template('@OroMessageQueue/Job/childJobs.html.twig')]
    #[AclAncestor('oro_message_queue_job')]
    public function childJobsAction(Job $job)
    {
        return [
            'entity' => $job,
        ];
    }
}
