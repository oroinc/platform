<?php

namespace Oro\Bundle\MaintenanceBundle\Tests\Functional\Drivers;

use Oro\Bundle\MaintenanceBundle\Drivers\FileDriver;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FileDriverTest extends WebTestCase
{
    private FileDriver $driver;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->driver = static::getContainer()->get('oro_maintenance.driver.factory')->getDriver();
        if (!$this->driver instanceof FileDriver) {
            self::markTestSkipped('Skipping test, another driver is used');
        }
    }

    protected function tearDown(): void
    {
        $this->driver->unlock();
    }

    public function testGetMessageLock(): void
    {
        self::assertEquals('Server is under maintenance.', $this->driver->getMessageLock($this->driver->lock()));
        self::assertEquals(
            'Server is already under maintenance.',
            $this->driver->getMessageLock($this->driver->lock())
        );
    }

    public function testGetMessageUnlock(): void
    {
        $this->driver->lock();

        self::assertEquals('Server is online.', $this->driver->getMessageUnlock($this->driver->unlock()));
        self::assertEquals(
            'Impossible to do this action.',
            $this->driver->getMessageUnlock($this->driver->unlock())
        );
    }
}
