<?php

namespace Oro\Bundle\NotificationBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\NotificationBundle\Entity\MassNotification;

/**
 * @Route("/massnotification")
 */
class MassNotificationController extends Controller
{
    /**
     * @Route(
     *      "/{_format}",
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
}
