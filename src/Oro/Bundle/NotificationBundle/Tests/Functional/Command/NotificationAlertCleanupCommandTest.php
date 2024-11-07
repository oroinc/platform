<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional\Command;

use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\NotificationBundle\Tests\Functional\DataFixtures\LoadNotificationAlertData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class NotificationAlertCleanupCommandTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadNotificationAlertData::class]);
    }

    public function testHelp(): void
    {
        $result = self::runCommand('oro:notification:alerts:cleanup', ['--help']);

        self::assertStringContainsString(
            'Help: The oro:notification:alerts:cleanup command deletes notification alert',
            $result
        );
    }

    public function testExecuteWhenUserNotFound(): void
    {
        $totalRowsBefore = self::getTotalRows();

        $result = self::runCommand('oro:notification:alerts:cleanup', [
            '--current-user' => '999',
            '--current-organization' => '1',
        ]);

        self::assertStringContainsString(
            "In ConsoleContextGlobalOptionsProvider.php line 84: Can't find user with "
            . 'identifier 999 oro:notification:alerts:cleanup',
            $result
        );

        $totalRowsAfter = self::getTotalRows();

        self::assertEquals($totalRowsBefore, $totalRowsAfter);
    }

    public function testExecuteWhenSuccessfullyDeleted(): void
    {
        $totalRowsBefore = self::getTotalRows();

        $result = self::runCommand('oro:notification:alerts:cleanup');

        self::assertStringContainsString(
            $totalRowsBefore . ' notification alert(s) was successfully deleted.',
            $result
        );

        $totalRowsAfter = self::getTotalRows();

        self::assertEquals(0, $totalRowsAfter);
    }

    public function testExecuteWhenNothingToDelete(): void
    {
        $totalRowsBefore = self::getTotalRows();

        $result = self::runCommand('oro:notification:alerts:cleanup', [
            '--current-user' => 'simple_user',
            '--current-organization' => '1',
        ]);

        self::assertStringContainsString('There are no notification alerts.', $result);

        $totalRowsAfter = self::getTotalRows();

        self::assertEquals($totalRowsBefore, $totalRowsAfter);
    }

    private static function getTotalRows(): ?int
    {
        return count(
            self::getContainer()->get('oro_entity.doctrine_helper')
                ->getEntityRepository(NotificationAlert::class)
                ->findAll()
        );
    }
}
