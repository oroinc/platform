<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Command;

use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ResetMenuUpdatesCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([MenuUpdateData::class]);
    }

    /**
     * @dataProvider paramProvider
     */
    public function testCommandOutput(string $expectedContent, array $params, int $rowsCount)
    {
        $result = $this->runCommand('oro:navigation:menu:reset', $params);
        $totalRows = $this->getContainer()->get('doctrine')->getRepository(MenuUpdate::class)->findAll();
        self::assertStringContainsString($expectedContent, $result);
        $this->assertCount($rowsCount, $totalRows);
    }

    public function paramProvider(): array
    {
        return [
            'should show help' => [
                '$expectedContent' => 'Usage: oro:navigation:menu:reset [options]',
                '$params'          => ['--help'],
                '$rowsCount'       => 9
            ],
            'should show failed reset for non existing user' => [
                '$expectedContent' => 'User with email nonexist@user.com not exists.',
                '$params'          => ['--user=nonexist@user.com'],
                '$rowsCount'       => 9
            ],
            'should show success reset for user and menu' => [
                '$expectedContent' =>
                    'The menu "application_menu" for user "simple_user@example.com" is successfully reset.',
                '$params'          => ['--user=simple_user@example.com', '--menu=application_menu'],
                '$rowsCount'       => 7
            ],
            'should show success reset for user' => [
                '$expectedContent' => 'All menus for user "simple_user@example.com" is successfully reset.',
                '$params'          => ['--user=simple_user@example.com'],
                '$rowsCount'       => 6
            ],
            'should show success reset for global scope and menu' => [
                '$expectedContent' => 'The menu "shortcuts" for global scope is successfully reset.',
                '$params'          => ['--menu=shortcuts'],
                '$rowsCount'       => 6
            ],
            'should show success reset for global scope' => [
                '$expectedContent' => 'All menus in global scope is successfully reset.',
                '$params'          => [],
                '$rowsCount'       => 0
            ],
        ];
    }
}
