<?php

namespace Oro\Bundle\NotificationBundle\Controller;

use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for notification alerts
 */
#[Route(path: '/notification-alert')]
class NotificationAlertController extends AbstractController
{
    use RequestHandlerTrait;

    #[Route(path: '/', name: 'oro_notification_notificationalert_index')]
    #[Template('@OroNotification/NotificationAlert/index.html.twig')]
    #[Acl(
        id: 'oro_notification_notificationalert_view',
        type: 'entity',
        class: NotificationAlert::class,
        permission: 'VIEW'
    )]
    public function indexAction()
    {
        return [
            'entity_class' => NotificationAlert::class
        ];
    }
}
