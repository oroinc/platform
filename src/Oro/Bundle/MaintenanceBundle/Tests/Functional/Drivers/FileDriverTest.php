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

    public function testMaintenanceLockWithTtl(): void
    {
        $this->driver->lock();
        self::assertTrue($this->driver->hasTtl());
        self::assertNotNull($this->driver->getTtl());
        self::assertTrue($this->driver->isExists());
        self::assertFalse($this->driver->isExpired());
    }

    public function testMaintenanceModeIsExpired(): void
    {
        $this->driver->setTtl(1);
        $this->driver->lock();
        self::assertTrue($this->driver->hasTtl());
        self::assertNotNull($this->driver->getTtl());
        self::assertTrue($this->driver->isExists());
        sleep(2);
        self::assertTrue($this->driver->isExpired());
    }
}
