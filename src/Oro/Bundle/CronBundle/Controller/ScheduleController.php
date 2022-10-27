<?php

namespace Oro\Bundle\CronBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The controller for Schedule entity.
 * @Route("/schedule")
 */
class ScheduleController extends AbstractController
{
    /**
     * @Template
     * @Route("/", name="oro_cron_schedule_index")
     * @Acl(
     *      id="oro_cron_schedule_view",
     *      type="entity",
     *      class="OroCronBundle:Schedule",
     *      permission="VIEW"
     * )
     */
    public function indexAction()
    {
        return [];
    }
}
