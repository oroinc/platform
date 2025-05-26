<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Config\EntityConfigMerger;
use Oro\Bundle\ApiBundle\Provider\CombinedConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Provider\ConfigBagInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CombinedConfigBagTest extends TestCase
{
    private ConfigBagInterface&MockObject $configBag1;

    private ConfigBagInterface&MockObject $configBag2;

    private EntityConfigMerger&MockObject $entityConfigMerger;
    private CombinedConfigBag $combinedConfigBag;

    #[\Override]
    protected function setUp(): void
    {
        $this->configBag1 = $this->createMock(ConfigBag::class);
        $this->configBag2 = $this->createMock(ConfigBagInterface::class);
        $this->entityConfigMerger = $this->createMock(EntityConfigMerger::class);

        $this->combinedConfigBag = new CombinedConfigBag(
            [$this->configBag1, $this->configBag2],
            $this->entityConfigMerger
        );
    }

    public function testGetClassNames(): void
    {
        $version = '1.2';

        $this->configBag1->expects(self::once())
            ->method('getClassNames')
            ->with($version)
            ->willReturn(['Test\Class2', 'Test\Class3']);
        $this->configBag2->expects(self::once())
            ->method('getClassNames')
            ->with($version)
            ->willReturn(['Test\Class1', 'Test\Class2']);

        self::assertEquals(
            ['Test\Class2', 'Test\Class3', 'Test\Class1'],
            $this->combinedConfigBag->getClassNames($version)
        );
    }

    public function testNoConfig(): void
    {
        $className = 'Test\Class1';
        $version = '1.2';

        $this->configBag1->expects(self::once())
            ->method('getConfig')
            ->with($className, $version)
            ->willReturn(null);
        $this->configBag2->expects(self::once())
            ->method('getConfig')
            ->with($className, $version)
            ->willReturn(null);
        $this->entityConfigMerger->expects(self::never())
            ->method('merge');

        self::assertNull(
            $this->combinedConfigBag->getConfig($className, $version)
        );
        // test that data is cached in memory
        self::assertNull(
            $this->combinedConfigBag->getConfig($className, $version)
        );
    }

    public function testOnlyFirstBagHasConfig(): void
    {
        $className = 'Test\Class1';
        $version = '1.2';
        $config = ['fields' => ['field1' => []]];

        $this->configBag1->expects(self::once())
            ->method('getConfig')
            ->with($className, $version)
            ->willReturn($config);
        $this->configBag2->expects(self::once())
            ->method('getConfig')
            ->with($className, $version)
            ->willReturn(null);
        $this->entityConfigMerger->expects(self::never())
            ->method('merge');

        self::assertEquals(
            $config,
            $this->combinedConfigBag->getConfig($className, $version)
        );
        // test that data is cached in memory
        self::assertEquals(
            $config,
            $this->combinedConfigBag->getConfig($className, $version)
        );
    }

    public function testOnlySecondBagHasConfig(): void
    {
        $className = 'Test\Class1';
        $version = '1.2';
        $config = ['fields' => ['field1' => []]];

        $this->configBag1->expects(self::once())
            ->method('getConfig')
            ->with($className, $version)
            ->willReturn(null);
        $this->configBag2->expects(self::once())
            ->method('getConfig')
            ->with($className, $version)
            ->willReturn($config);
        $this->entityConfigMerger->expects(self::never())
            ->method('merge');

        self::assertEquals(
            $config,
            $this->combinedConfigBag->getConfig($className, $version)
        );
        // test that data is cached in memory
        self::assertEquals(
            $config,
            $this->combinedConfigBag->getConfig($className, $version)
        );
    }

    public function testAllBagsHaveConfigs(): void
    {
        $className = 'Test\Class1';
        $version = '1.2';
        $mergedConfig = ['fields' => ['field1' => [], 'field2' => []]];

        $this->configBag1->expects(self::once())
            ->method('getConfig')
            ->with($className, $version)
            ->willReturn(['fields' => ['field1' => []]]);
        $this->configBag2->expects(self::once())
            ->method('getConfig')
            ->with($className, $version)
            ->willReturn(['fields' => ['field2' => []]]);
        $this->entityConfigMerger->expects(self::once())
            ->method('merge')
            ->with(['fields' => ['field1' => []]], ['fields' => ['field2' => []]])
            ->willReturn($mergedConfig);

        self::assertEquals(
            $mergedConfig,
            $this->combinedConfigBag->getConfig($className, $version)
        );
        // test that data is cached in memory
        self::assertEquals(
            $mergedConfig,
            $this->combinedConfigBag->getConfig($className, $version)
        );
    }

    public function testReset(): void
    {
        $className = 'Test\Class1';
        $version = '1.2';
        $config = ['fields' => ['field1' => []]];

        $this->configBag1->expects(self::exactly(2))
            ->method('getConfig')
            ->with($className, $version)
            ->willReturn(null);
        $this->configBag2->expects(self::exactly(2))
            ->method('getConfig')
            ->with($className, $version)
            ->willReturn($config);
        $this->entityConfigMerger->expects(self::never())
            ->method('merge');
        $this->configBag1->expects(self::once())
            ->method('reset');

        self::assertEquals(
            $config,
            $this->combinedConfigBag->getConfig($className, $version)
        );
        // test that data is cached in memory
        self::assertEquals(
            $config,
            $this->combinedConfigBag->getConfig($className, $version)
        );

        $this->combinedConfigBag->reset();
        self::assertEquals(
            $config,
            $this->combinedConfigBag->getConfig($className, $version)
        );
    }
}
