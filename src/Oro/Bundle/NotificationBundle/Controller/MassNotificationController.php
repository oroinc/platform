<?php

namespace Oro\Bundle\NotificationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;

/**
 * @Route("/massnotification")
 */
class MassNotificationController extends Controller
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
     *      class="OroNotificationBundle:MassNotification",
     *      permission="VIEW"
     * )
     * @Template()
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('oro_notification.massnotification.entity.class')
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
        $translator = $this->get('translator');
        $statusLabel = $massNotification->getStatus() == MassNotification::STATUS_FAILED ?
            $translator->trans('oro.notification.massnotification.status.failed') :
            $translator->trans('oro.notification.massnotification.status.success');
        
        return [
            'entity'      => $massNotification,
            'statusLabel' => $statusLabel
        ];
    }
}
