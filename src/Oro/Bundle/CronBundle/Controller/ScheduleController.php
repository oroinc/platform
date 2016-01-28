<?php

namespace Oro\Bundle\CronBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * @Route("/schedule")
 */
class ScheduleController extends Controller
{
    /**
     * @Template
     * @Route("/", name="oro_cron_schedule_index")
     * @AclAncestor("oro_schedules")
     */
    public function indexAction()
    {
        return [];
    }
}
