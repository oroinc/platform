<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional\EventListener;

use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Event\ConfigureMenuEvent;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\EventListener\NavigationListener;
use Oro\Bundle\ReportBundle\Tests\Functional\DataFixtures\LoadReportsData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

/**
 * @dbIsolationPerTest
 */
class NavigationListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();

        // Delete unneeded here 'Campaign Performance' report, which can be loaded by
        // Oro\Bridge\MarketingCRM\Migrations\Migrations\Data\ORM\LoadCampaignPerformanceReport
        $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityRepositoryForClass(Report::class)
            ->createQueryBuilder('report')->delete()->getQuery()->execute();
        $this->loadFixtures([LoadReportsData::class]);

        /** @var User $user */
        $user = $this->getContainer()->get('doctrine')
            ->getRepository('OroUserBundle:User')
            ->findOneBy(['username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME]);

        $token = new UsernamePasswordOrganizationToken($user, [], 'main', $user->getOrganization());
        $this->client->getContainer()->get('security.token_storage')->setToken($token);
    }

    public function testOnNavigationConfigure()
    {
        /** @var MenuFactory $factory */
        $factory = $this->getContainer()->get('knp_menu.factory');

        $menu = new MenuItem('test_reports_menu', $factory);
        $reportTab = new MenuItem('reports_tab', $factory);
        $menu->addChild($reportTab);

        $event = new ConfigureMenuEvent($factory, $menu);
        $this->getNavigationListener()->onNavigationConfigure($event);

        $children = $reportTab->getChildren();
        $this->assertCount(4, $children);
        $divider = array_splice($children, 0, 1);
        $divider = reset($divider);

        $this->assertContains('divider', $divider->getName());
        foreach ($children as $child) {
            $this->assertRegExp('/^Report [123]_report$/i', $child->getFirstChild()->getName());
            $this->assertRegExp('/^Report [123]$/i', $child->getFirstChild()->getLabel());
        }
    }

    /**
     * @return NavigationListener
     */
    protected function getNavigationListener()
    {
        return $this->getContainer()->get('oro_report.listener.navigation_listener');
    }
}
