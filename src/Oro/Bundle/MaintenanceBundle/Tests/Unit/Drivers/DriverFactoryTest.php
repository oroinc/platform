<?php

namespace Oro\Bundle\MaintenanceBundle\Tests\Unit\Drivers;

use Oro\Bundle\MaintenanceBundle\Drivers\DriverFactory;
use Oro\Bundle\MaintenanceBundle\Drivers\FileDriver;
use Symfony\Contracts\Translation\TranslatorInterface;

class DriverFactoryTest extends \PHPUnit\Framework\TestCase
{
    private TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    /**
     * @dataProvider getDriverDataProvider
     */
    public function testGetDriver(array $options): void
    {
        $factory = new DriverFactory($this->translator, $options);

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
            'path and ttl' => [
                [
                    'class' => FileDriver::class,
                    'options'=> [
                        'file_path' => 'file/path',
                        'ttl' => 10,
                        'unknown_option' => 'value',
                    ],
                ],
            ],
        ];
    }
}
