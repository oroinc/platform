<?php

namespace Oro\Bundle\CronBundle\Controller;

use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The controller for Schedule entity.
 */
#[Route(path: '/schedule')]
class ScheduleController extends AbstractController
{
    #[Route(path: '/', name: 'oro_cron_schedule_index')]
    #[Template('@OroCron/Schedule/index.html.twig')]
    #[Acl(id: 'oro_cron_schedule_view', type: 'entity', class: Schedule::class, permission: 'VIEW')]
    public function indexAction()
    {
        return [];
    }
}
