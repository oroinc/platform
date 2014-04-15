<?php

namespace Oro\Bundle\UserBundle\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

class DashboardController extends Controller
{
    /**
     * @Route(
     *      "/recent_emails/{widget}/{activeTab}/{contentType}",
     *      name="oro_user_dashboard_recent_emails",
     *      requirements={"widget"="[\w-]+", "activeTab"="inbox|sent", "contentType"="full|tab"},
     *      defaults={"activeTab" = "inbox", "contentType" = "full"}
     * )
     */
    public function recentEmailsAction($widget, $activeTab, $contentType)
    {
        $loggedUserId     = $this->getUser()->getId();
        $renderMethod     = ($contentType === 'tab') ? 'render' : 'renderView';
        $activeTabContent = $this->$renderMethod(
            'OroUserBundle:Dashboard:recentEmailsGrid.html.twig',
            [
                'loggedUserId' => $loggedUserId,
                'gridName'     => sprintf('dashboard-recent-emails-%s-grid', $activeTab)
            ]
        );

        if ($contentType === 'tab') {
            return $activeTabContent;
        } else {
            $params = array_merge(
                [
                    'loggedUserId'     => $loggedUserId,
                    'activeTab'        => $activeTab,
                    'activeTabContent' => $activeTabContent
                ],
                $this->get('oro_dashboard.widget_attributes')->getWidgetAttributesForTwig($widget)
            );

            return $this->render(
                'OroUserBundle:Dashboard:recentEmails.html.twig',
                $params
            );
        }
    }
}
