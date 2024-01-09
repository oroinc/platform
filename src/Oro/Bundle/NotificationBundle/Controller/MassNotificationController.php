<?php

namespace Oro\Bundle\NotificationBundle\Controller;

use Oro\Bundle\NotificationBundle\Entity\MassNotification;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for MassNotification entity.
 * @Route("/massnotification")
 */
class MassNotificationController extends AbstractController
{
    /**
     * @Route(
     *      "/{_format}",
     *      name="oro_notification_massnotification_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Acl(
     *      id="oro_notification_massnotification_view",
     *      type="entity",
     *      class="Oro\Bundle\NotificationBundle\Entity\MassNotification",
     *      permission="VIEW"
     * )
     * @Template()
     */
    public function indexAction()
    {
        return [
            'entity_class' => MassNotification::class
        ];
    }

    /**
     * @Route("/view/{id}", name="oro_notification_massnotification_view", requirements={"id"="\d+"})
     * @Template()
     * @AclAncestor("oro_notification_massnotification_view")
     */
    public function viewAction(MassNotification $massNotification)
    {
        return [
            'entity' => $massNotification
        ];
    }

    /**
     * @Route("/info/{id}", name="oro_notification_massnotification_info", requirements={"id"="\d+"})
     * @Template()
     * @AclAncestor("oro_notification_massnotification_view")
     */
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

    /**
     * {@inheritdoc}
     */
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
