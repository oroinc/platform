<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigBag;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\EntityConfigPassStub;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle2\TestBundle2;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class EntityConfigPassTest extends TestCase
{
    public function testLoadConfig(): void
    {
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                'bundle_1' => TestBundle1::class,
                'bundle_2' => TestBundle2::class,
            ]);

        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder->expects(self::any())
            ->method('getDefinition')
            ->willReturnCallback(function (string $class) {
                return new Definition($class, [1, 2, 3]);
            });
        $containerBuilder->expects(self::any())
            ->method('setDefinition')
            ->willReturnArgument(1);

        $config = [
            'sharding' => [
                'entity' => [
                    'items' => [
                        'discrimination_field' => [null, null],
                    ]
                ]
            ]
        ];

        $configBag = new Definition(PropertyConfigBag::class, [$config]);
        $configBag->setPublic(false);
        $configBag->setLazy(true);

        $containerBuilder->expects(self::any())
            ->method('setDefinition')
            ->willReturnCallback(function (string $id, Definition $definition) use ($configBag) {
                if (EntityConfigPassStub::CONFIG_BAG_SERVICE === $id) {
                    self::assertEquals($configBag, $definition);
                }
            });

        $pass = new EntityConfigPassStub();
        $pass->process($containerBuilder);
    }
}
