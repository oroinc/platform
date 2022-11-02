<?php

namespace Oro\Bundle\EmailBundle\Controller\Dashboard;

use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\EmailBundle\Manager\EmailNotificationManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provide functionality to manage recent emails on dashboard
 */
class DashboardController extends AbstractController
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(parent::getSubscribedServices(), [
            TokenAccessorInterface::class,
            WidgetConfigs::class,
            EmailNotificationManager::class
        ]);
    }

    /**
     * @Route(
     *      "/recent_emails/{widget}/{activeTab}/{contentType}",
     *      name="oro_email_dashboard_recent_emails",
     *      requirements={"widget"="[\w\-]+", "activeTab"="inbox|sent|new", "contentType"="full|tab"},
     *      defaults={"activeTab" = "inbox", "contentType" = "full"}
     * )
     */
    public function recentEmailsAction($widget, $activeTab, $contentType)
    {
        $loggedUser = $this->getUser();
        $loggedUserId = $loggedUser->getId();
        $renderMethod = ($contentType === 'tab') ? 'render' : 'renderView';
        $activeTabContent = $this->$renderMethod(
            '@OroEmail/Dashboard/recentEmailsGrid.html.twig',
            [
                'loggedUserId' => $loggedUserId,
                'gridName' => sprintf('dashboard-recent-emails-%s-grid', $activeTab)
            ]
        );

        if ($contentType === 'tab') {
            return $activeTabContent;
        } else {
            $currentOrganization = $this->get(TokenAccessorInterface::class)->getOrganization();

            $unreadMailCount = 0;
            if ($this->isGranted('oro_email_email_user_view')) {
                $unreadMailCount = $this
                    ->get(EmailNotificationManager::class)
                    ->getCountNewEmails($loggedUser, $currentOrganization);
            }

            $params = array_merge(
                [
                    'loggedUserId'     => $loggedUserId,
                    'activeTab'        => $activeTab,
                    'activeTabContent' => $activeTabContent,
                    'unreadMailCount'  => $unreadMailCount,
                ],
                $this->get(WidgetConfigs::class)->getWidgetAttributesForTwig($widget)
            );

            return $this->render(
                '@OroEmail/Dashboard/recentEmails.html.twig',
                $params
            );
        }
    }
}
