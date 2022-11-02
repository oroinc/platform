<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\SimpleFilterFactoryCompilerPass;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\SimpleFilterFactory;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\FilterFactoryStub;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SimpleFilterFactoryCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var SimpleFilterFactoryCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new SimpleFilterFactoryCompilerPass();
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $simpleFilterFactoryDef = $container->register('oro_api.filter_factory.default', SimpleFilterFactory::class);
        $simpleFilterFactoryDef->setArguments([[], [], null]);
        DependencyInjectionUtil::setConfig(
            $container,
            [
                'filters' => [
                    'filter1' => [
                        'class' => ComparisonFilter::class
                    ],
                    'filter2' => [
                        'class'   => ComparisonFilter::class,
                        'option1' => 'value1'
                    ],
                    'filter3' => [
                        'factory' => ['@factory3_service', 'create']
                    ],
                    'filter4' => [
                        'factory' => ['@factory4_service', 'create'],
                        'option1' => 'value1'
                    ]
                ]
            ]
        );
        $container->register('factory3_service', FilterFactoryStub::class)
            ->setPublic(false);
        $container->register('factory4_service', FilterFactoryStub::class)
            ->setPublic(false);

        $this->compiler->process($container);

        self::assertEquals(
            [
                'filter1' => [ComparisonFilter::class, []],
                'filter2' => [ComparisonFilter::class, ['option1' => 'value1']]
            ],
            $simpleFilterFactoryDef->getArgument(0)
        );
        self::assertEquals(
            [
                'filter3' => ['factory3_service', 'create', []],
                'filter4' => ['factory4_service', 'create', ['option1' => 'value1']]
            ],
            $simpleFilterFactoryDef->getArgument(1)
        );
        $factoryContainerServiceLocatorRef = $simpleFilterFactoryDef->getArgument(2);
        self::assertInstanceOf(Reference::class, $factoryContainerServiceLocatorRef);
        $factoryContainerServiceLocatorDef = $container->getDefinition((string)$factoryContainerServiceLocatorRef);
        self::assertEquals(ServiceLocator::class, $factoryContainerServiceLocatorDef->getClass());
        self::assertEquals(
            [
                'factory3_service' => new ServiceClosureArgument(new Reference('factory3_service')),
                'factory4_service' => new ServiceClosureArgument(new Reference('factory4_service'))
            ],
            $factoryContainerServiceLocatorDef->getArgument(0)
        );
    }

    public function testFilterFactoryWhenFactoryMethodDoesNotExist()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The "unknownMethod($dataType)" public method must be declared in the "%s" class.',
            FilterFactoryStub::class
        ));

        $container = new ContainerBuilder();
        DependencyInjectionUtil::setConfig(
            $container,
            [
                'filters' => [
                    'filter1' => [
                        'factory' => ['@factory1_service', 'unknownMethod']
                    ]
                ]
            ]
        );
        $container->register('factory1_service', FilterFactoryStub::class)
            ->setPublic(false);

        $this->compiler->process($container);
    }

    public function testFilterFactoryWhenFactoryMethodIsNotPublic()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The "privateCreate($dataType)" public method must be declared in the "%s" class.',
            FilterFactoryStub::class
        ));

        $container = new ContainerBuilder();
        DependencyInjectionUtil::setConfig(
            $container,
            [
                'filters' => [
                    'filter1' => [
                        'factory' => ['@factory1_service', 'privateCreate']
                    ]
                ]
            ]
        );
        $container->register('factory1_service', FilterFactoryStub::class)
            ->setPublic(false);

        $this->compiler->process($container);
    }

    public function testFilterFactoryWhenFactoryMethodHasInvalidSignature()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(sprintf(
            'The "createWithoutDataType($dataType)" public method must be declared in the "%s" class.',
            FilterFactoryStub::class
        ));

        $container = new ContainerBuilder();
        DependencyInjectionUtil::setConfig(
            $container,
            [
                'filters' => [
                    'filter1' => [
                        'factory' => ['@factory1_service', 'createWithoutDataType']
                    ]
                ]
            ]
        );
        $container->register('factory1_service', FilterFactoryStub::class)
            ->setPublic(false);

        $this->compiler->process($container);
    }
}
