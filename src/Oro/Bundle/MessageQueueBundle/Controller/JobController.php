<?php

namespace Oro\Bundle\MessageQueueBundle\Controller;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for Job entity.
 */
#[Route(path: '/jobs')]
class JobController extends AbstractController
{
    #[Route(path: '/', name: 'oro_message_queue_root_jobs')]
    #[Template]
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
    #[Template]
    #[AclAncestor('oro_message_queue_job')]
    public function childJobsAction(Job $job)
    {
        return [
            'entity' => $job,
        ];
    }
}
