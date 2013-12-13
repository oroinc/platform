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
     *      "/recent_emails/{activeTab}/{contentType}/{_format}",
     *      name="oro_user_dashboard_recent_emails",
     *      requirements={"activeTab"="inbox|sent", "contentType"="full|tab", "_format"="html|json"},
     *      defaults={"activeTab" = "inbox", "contentType" = "full", "_format" = "html"}
     * )
     */
    public function recentEmailsAction($activeTab, $contentType)
    {
        $currentUserId    = $this->getUser()->getId();
        $renderMethod     = ($contentType === 'tab') ? 'render' : 'renderView';
        $activeTabContent = $this->$renderMethod(
            'OroUserBundle:Dashboard:recentEmailsGrid.html.twig',
            [
                'currentUserId' => $currentUserId,
                'gridName'      => sprintf('dashboard-recent-emails-%s-grid', $activeTab)
            ]
        );

        if ($contentType === 'tab') {
            return $activeTabContent;
        } else {
            return $this->render(
                'OroUserBundle:Dashboard:recentEmails.html.twig',
                [
                    'currentUserId'    => $currentUserId,
                    'activeTab'        => $activeTab,
                    'activeTabContent' => $activeTabContent
                ]
            );
        }
    }
}
