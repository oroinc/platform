<?php

namespace Oro\Bundle\NotificationBundle\Controller;

use Oro\Bundle\NotificationBundle\Entity\MassNotification;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for MassNotification entity.
 */
#[Route(path: '/massnotification')]
class MassNotificationController extends AbstractController
{
    #[Route(
        path: '/{_format}',
        name: 'oro_notification_massnotification_index',
        requirements: ['_format' => 'html|json'],
        defaults: ['_format' => 'html']
    )]
    #[Template('@OroNotification/MassNotification/index.html.twig')]
    #[Acl(
        id: 'oro_notification_massnotification_view',
        type: 'entity',
        class: MassNotification::class,
        permission: 'VIEW'
    )]
    public function indexAction()
    {
        return [
            'entity_class' => MassNotification::class
        ];
    }

    #[Route(path: '/view/{id}', name: 'oro_notification_massnotification_view', requirements: ['id' => '\d+'])]
    #[Template('@OroNotification/MassNotification/view.html.twig')]
    #[AclAncestor('oro_notification_massnotification_view')]
    public function viewAction(MassNotification $massNotification)
    {
        return [
            'entity' => $massNotification
        ];
    }

    #[Route(path: '/info/{id}', name: 'oro_notification_massnotification_info', requirements: ['id' => '\d+'])]
    #[Template('@OroNotification/MassNotification/info.html.twig')]
    #[AclAncestor('oro_notification_massnotification_view')]
    public function infoAction(MassNotification $massNotification)
    {
        $translator = $this->container->get(TranslatorInterface::class);
        $statusLabel = $massNotification->getStatus() == MassNotification::STATUS_FAILED ?
            $translator->trans('oro.notification.massnotification.status.failed') :
            $translator->trans('oro.notification.massnotification.status.success');

        return [
            'entity'      => $massNotification,
            'statusLabel' => $statusLabel
        ];
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
            ]
        );
    }
}
