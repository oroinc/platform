<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ResetMenuUpdatesCommand extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData'
        ]);
    }

    /**
     * @dataProvider paramProvider
     *
     * @param string $expectedContent
     * @param array  $params
     * @param int    $rowsCount
     */
    public function testCommandOutput($expectedContent, $params, $rowsCount)
    {
        $result = $this->runCommand('oro:navigation:menu:reset', $params);
        $totalRows = $this->getContainer()->get('doctrine')->getRepository('OroNavigationBundle:MenuUpdate')->findAll();
        $this->assertContains($expectedContent, $result);
        $this->assertCount($rowsCount, $totalRows);
    }

    /**
     * @return array
     */
    public function paramProvider()
    {
        return [
            'should show help' => [
                '$expectedContent' => "Usage:\n  oro:navigation:menu:reset [options]",
                '$params'          => ['--help'],
                '$rowsCount'       => 3
            ],
            'should show failed reset for non existing user' => [
                '$expectedContent' => "User with email nonexist@user.com not exists.",
                '$params'          => ['--user=nonexist@user.com'],
                '$rowsCount'       => 3
            ],
            'should show success reset for user and menu' => [
                '$expectedContent' =>
                    "The menu for the user admin@example.com and menu other_menu is successfully reset.",
                '$params'          => ['--user=admin@example.com', '--menu=other_menu'],
                '$rowsCount'       => 2
            ],
            'should show success reset for user' => [
                '$expectedContent' => "The menu for the user admin@example.com is successfully reset.",
                '$params'          => ['--user=admin@example.com'],
                '$rowsCount'       => 1
            ],
            'should show success reset for organization' => [
                '$expectedContent' => "The menu for the organization is successfully reset.",
                '$params'          => [],
                '$rowsCount'       => 0
            ],
        ];
    }
}
