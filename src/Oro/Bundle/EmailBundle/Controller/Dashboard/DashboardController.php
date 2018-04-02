<?php

namespace Oro\Bundle\EmailBundle\Controller\Dashboard;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DashboardController extends Controller
{
    /**
     * @Route(
     *      "/recent_emails/{widget}/{activeTab}/{contentType}",
     *      name="oro_email_dashboard_recent_emails",
     *      requirements={"widget"="[\w-]+", "activeTab"="inbox|sent|new", "contentType"="full|tab"},
     *      defaults={"activeTab" = "inbox", "contentType" = "full"}
     * )
     */
    public function recentEmailsAction($widget, $activeTab, $contentType)
    {
        $loggedUser = $this->getUser();
        $loggedUserId = $loggedUser->getId();
        $renderMethod = ($contentType === 'tab') ? 'render' : 'renderView';
        $activeTabContent = $this->$renderMethod(
            'OroEmailBundle:Dashboard:recentEmailsGrid.html.twig',
            [
                'loggedUserId' => $loggedUserId,
                'gridName' => sprintf('dashboard-recent-emails-%s-grid', $activeTab)
            ]
        );

        if ($contentType === 'tab') {
            return $activeTabContent;
        } else {
            $currentOrganization = $this->get('oro_security.token_accessor')->getOrganization();

            $unreadMailCount = 0;
            if ($this->isGranted('oro_email_email_user_view')) {
                $unreadMailCount = $this
                    ->get('oro_email.manager.notification')
                    ->getCountNewEmails($loggedUser, $currentOrganization);
            }

            $params = array_merge(
                [
                    'loggedUserId'     => $loggedUserId,
                    'activeTab'        => $activeTab,
                    'activeTabContent' => $activeTabContent,
                    'unreadMailCount'  => $unreadMailCount,
                ],
                $this->get('oro_dashboard.widget_configs')->getWidgetAttributesForTwig($widget)
            );

            return $this->render(
                'OroEmailBundle:Dashboard:recentEmails.html.twig',
                $params
            );
        }
    }
}
