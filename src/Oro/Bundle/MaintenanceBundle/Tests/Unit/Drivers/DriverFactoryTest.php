<?php

namespace Oro\Bundle\MaintenanceBundle\Tests\Unit\Drivers;

use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\MaintenanceBundle\Drivers\FileDriver;

class DriverFactoryTest extends \PHPUnit\Framework\TestCase
{
    private function getDriverFactory(array $options): DriverFactory
    {
        return new DriverFactory($options);
    }

    /**
     * @dataProvider getDriverDataProvider
     */
    public function testGetDriver(array $options): void
    {
        $factory = $this->getDriverFactory($options);

        $driver = $factory->getDriver();

        self::assertInstanceOf(FileDriver::class, $driver);
        self::assertEquals($options['options'], $driver->getOptions());
    }

    public function getDriverDataProvider(): array
    {
        return [
            'only path' => [
                [
                    'class' => FileDriver::class,
                    'options'=> [
                        'file_path' => 'file/path',
                    ],
                ],
            ],
            'path and some options' => [
                [
                    'class' => FileDriver::class,
                    'options'=> [
                        'file_path' => 'file/path',
                        'unknown_option' => 'value',
                    ],
                ],
            ],
        ];
    }

    public function testGetDriverShouldReturnExistingInstanceOfDriver(): void
    {
        $factory = $this->getDriverFactory(['options'=> ['file_path' => 'file/path']]);

        $driver = $factory->getDriver();

        // test that a new instance of the driver is not created each time the driver is requested
        self::assertSame($driver, $factory->getDriver());
    }
}
