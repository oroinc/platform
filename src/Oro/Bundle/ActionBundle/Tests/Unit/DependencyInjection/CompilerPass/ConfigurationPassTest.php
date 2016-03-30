<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Doctrine\Common\Cache\CacheProvider;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;

use Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass\ConfigurationPass;
use Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1;
use Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2;

use Oro\Component\Config\CumulativeResourceManager;

class ConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder */
    protected $container;

    /** @var ConfigurationPass */
    protected $compilerPass;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->compilerPass = new ConfigurationPass();
    }

    protected function tearDown()
    {
        unset($this->compilerPass, $this->container);
    }

    public function testProcess()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1->getName() => get_class($bundle1), $bundle2->getName() => get_class($bundle2)]);

        $operations = $actionGroups = null;

        $this->container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturn(true);
        $this->container->expects($this->exactly(2))
            ->method('getDefinition')
            ->willReturnMap([
                [ConfigurationPass::OPERATIONS_PROVIDER, $this->getConfigProviderDefinitionMock($operations)],
                [ConfigurationPass::ACTION_GROUPS_PROVIDER, $this->getConfigProviderDefinitionMock($actionGroups)]
            ]);
        $this->container->expects($this->exactly(2))
            ->method('has')
            ->willReturn(true);
        $this->container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    ConfigurationPass::OPERATIONS_CACHE,
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->getCacheProviderMock()
                ],
                [
                    ConfigurationPass::ACTION_GROUPS_CACHE,
                    ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                    $this->getCacheProviderMock()
                ]
            ]);

        $this->compilerPass->process($this->container);

        $this->assertEquals(
            [
                'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1' => [
                    'test_operation1' => [
                        'label' => 'Test Operation 1'
                    ],
                    'test_operation2' => [
                        'label' => 'Test Operation 2'
                    ],
                ],
                'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2' => [
                    'test_operation4' => [
                        'label' => 'Test Operation 4'
                    ]
                ]
            ],
            $operations
        );

        $this->assertEquals(
            [
                'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle1\TestBundle1' => [
                    'group1' => [
                        'parameters' => [
                            '$.data' => [
                                'type' => 'Oro\Bundle\TestBundle\Entity\Test',
                                'required' => true
                            ]
                        ],
                        'conditions' => [
                            '@gt' => ['$updatedAt', '$.date']
                        ],
                        'actions' => [
                            ['@assign_value' => ['$expired', true]]
                        ]
                    ]
                ],
                'Oro\Bundle\ActionBundle\Tests\Unit\Fixtures\Bundles\TestBundle2\TestBundle2' => [
                    'group2' => [
                        'parameters' => [
                            '$.date' => [
                                'type' => 'DateTime',
                                'message' => 'No data specified!'
                            ]
                        ],
                        'conditions' => [
                            '@gt' => ['$updatedAt', '$.date']
                        ],
                        'actions' => [
                            ['@assign_value' => ['$expired', true]]
                        ]
                    ]
                ]
            ],
            $actionGroups
        );
    }

    public function testProcessWithoutConfigurationProvider()
    {
        $this->container->expects($this->exactly(2))
            ->method('has')
            ->willReturnMap([
                [ConfigurationPass::OPERATIONS_CACHE, false],
                [ConfigurationPass::ACTION_GROUPS_CACHE, false]
            ]);
        $this->container->expects($this->never())->method('get')->with($this->anything());

        $this->container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturnMap([
                [ConfigurationPass::OPERATIONS_PROVIDER, false],
                [ConfigurationPass::ACTION_GROUPS_PROVIDER, false]
            ]);

        $this->container->expects($this->never())->method('getDefinition')->with($this->anything());

        $this->compilerPass->process($this->container);
    }

    /**
     * @param array $result
     * @return \PHPUnit_Framework_MockObject_MockObject|Definition
     */
    protected function getConfigProviderDefinitionMock(&$result)
    {
        $mock = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $mock->expects($this->once())
            ->method('replaceArgument')
            ->willReturnCallback(
                function ($index, $argument) use (&$result) {
                    $this->assertEquals(3, $index);

                    $result = $argument;
                }
            );

        return $mock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheProvider
     */
    protected function getCacheProviderMock()
    {
        $mock = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['deleteAll'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $mock->expects($this->once())
            ->method('deleteAll')
            ->willReturn(true);

        return $mock;
    }
}
