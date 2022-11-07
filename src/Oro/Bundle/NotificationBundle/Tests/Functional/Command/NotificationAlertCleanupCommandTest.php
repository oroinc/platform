<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional\Command;

use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\NotificationBundle\Tests\Functional\DataFixtures\LoadNotificationAlertData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class NotificationAlertCleanupCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadNotificationAlertData::class]);
    }

    /**
     * @dataProvider paramProvider
     */
    public function testCommandOutput(string $expectedContent, array $params, int $rowsCount)
    {
        $result = $this->runCommand('oro:notification:alerts:cleanup', $params);

        self::assertStringContainsString($expectedContent, $result);

        $totalRows = self::getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository(NotificationAlert::class)
            ->findAll();
        $this->assertCount($rowsCount, $totalRows);
    }

    public function paramProvider(): array
    {
        return [
            'should show help' => [
                '$expectedContent' => 'Help: The oro:notification:alerts:cleanup command deletes notification alert',
                '$params'          => ['--help'],
                '$rowsCount'       => 4
            ],
            'should warn if given user is not found'       => [
                '$expectedContent' => "In ConsoleContextGlobalOptionsProvider.php line 81: Can't find user with "
                    . 'identifier 999 oro:notification:alerts:cleanup',
                '$params'          => [
                    '--current-user'         => '999',
                    '--current-organization' => '1'
                ],
                '$rowsCount'       => 4
            ],
            'should show success deleted message'           => [
                '$expectedContent' => '4 notification alert(s) was successfully deleted.',
                '$params'          => [],
                '$rowsCount'       => 0
            ],
            'should notify there is no notification alerts' => [
                '$expectedContent' => 'There are no notification alerts.',
                '$params'          => [
                    '--current-user'         => 'simple_user',
                    '--current-organization' => '1'
                ],
                '$rowsCount'       => 0
            ],
        ];
    }
}
