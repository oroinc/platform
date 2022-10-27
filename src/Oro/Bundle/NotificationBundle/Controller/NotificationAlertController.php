<?php

namespace Oro\Bundle\NotificationBundle\Controller;

use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for notification alerts
 *
 * @Route("/notification-alert")
 */
class NotificationAlertController extends AbstractController
{
    use RequestHandlerTrait;

    /**
     * @Route("/", name="oro_notification_notificationalert_index")
     * @Acl(
     *      id="oro_notification_notificationalert_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroNotificationBundle:NotificationAlert"
     * )
     * @Template()
     */
    public function indexAction()
    {
        return [
            'entity_class' => NotificationAlert::class
        ];
    }
}
