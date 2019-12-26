<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\FilterBundle\DependencyInjection\Compiler\FilterTypesPass;
use Oro\Bundle\FilterBundle\Filter\FilterBag;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class FilterTypesPassTest extends \PHPUnit\Framework\TestCase
{
    private const FILTER_BAG_SERVICE_ID = 'test_filter_bag';
    private const FILTER_TAG            = 'test_filter';

    /** @var FilterTypesPass */
    private $compiler;

    public function setUp()
    {
        $this->compiler = new FilterTypesPass(self::FILTER_BAG_SERVICE_ID, self::FILTER_TAG);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $filterBagDef = $container->register(self::FILTER_BAG_SERVICE_ID, FilterBag::class);
        $filter1Def = $container->register('filter1_service')
            ->setPublic(false)
            ->addTag(self::FILTER_TAG, ['type' => 'filter1']);
        $filter2Def = $container->register('filter2_service')
            ->addTag(self::FILTER_TAG, ['type' => 'filter2']);

        $this->compiler->process($container);

        self::assertEquals(
            ['filter1', 'filter2'],
            $filterBagDef->getArgument(0)
        );

        $filterBagServiceLocatorReference = $filterBagDef->getArgument(1);
        self::assertInstanceOf(Reference::class, $filterBagServiceLocatorReference);
        $filterBagServiceLocatorDef = $container->getDefinition((string)$filterBagServiceLocatorReference);
        self::assertEquals(ServiceLocator::class, $filterBagServiceLocatorDef->getClass());
        self::assertEquals(
            [
                'filter1' => new ServiceClosureArgument(new Reference('filter1_service')),
                'filter2' => new ServiceClosureArgument(new Reference('filter2_service'))
            ],
            $filterBagServiceLocatorDef->getArgument(0)
        );

        self::assertFalse($filter1Def->isPublic());
        self::assertTrue($filter2Def->isPublic());
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @expectedExceptionMessage The attribute "type" is required for "test_filter" tag and its value should not be blank. Service: "filter1_service".
     */
    // @codingStandardsIgnoreEnd
    public function testProcessFilterWithoutTypeAttribute()
    {
        $container = new ContainerBuilder();
        $container->register(self::FILTER_BAG_SERVICE_ID, FilterBag::class);
        $container->register('filter1_service')
            ->addTag(self::FILTER_TAG);

        $this->compiler->process($container);
    }
}
