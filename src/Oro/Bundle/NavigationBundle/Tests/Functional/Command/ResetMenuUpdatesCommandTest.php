<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Command;

use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ResetMenuUpdatesCommandTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            MenuUpdateData::class
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
                '$expectedContent' => "Usage: oro:navigation:menu:reset [options]",
                '$params'          => ['--help'],
                '$rowsCount'       => 7
            ],
            'should show failed reset for non existing user' => [
                '$expectedContent' => "User with email nonexist@user.com not exists.",
                '$params'          => ['--user=nonexist@user.com'],
                '$rowsCount'       => 7
            ],
            'should show success reset for user and menu' => [
                '$expectedContent' =>
                    "The menu \"application_menu\" for user \"simple_user@example.com\" is successfully reset.",
                '$params'          => ['--user=simple_user@example.com', '--menu=application_menu'],
                '$rowsCount'       => 5
            ],
            'should show success reset for user' => [
                '$expectedContent' => "All menus for user \"simple_user@example.com\" is successfully reset.",
                '$params'          => ['--user=simple_user@example.com'],
                '$rowsCount'       => 5
            ],
            'should show success reset for global scope and menu' => [
                '$expectedContent' => "The menu \"shortcuts\" for global scope is successfully reset.",
                '$params'          => ['--menu=shortcuts'],
                '$rowsCount'       => 5
            ],
            'should show success reset for global scope' => [
                '$expectedContent' => "All menus in global scope is successfully reset.",
                '$params'          => [],
                '$rowsCount'       => 0
            ],
        ];
    }
}
