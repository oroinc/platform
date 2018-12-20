<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config;

use Oro\Bundle\ApiBundle\Config\FiltersConfig;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfig;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigExtra;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\TestConfigSection;

class ConfigContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigContext */
    private $context;

    protected function setUp()
    {
        $this->context = new ConfigContext();
    }

    public function testClassName()
    {
        self::assertNull($this->context->getClassName());

        $this->context->setClassName('test');
        self::assertEquals('test', $this->context->getClassName());
        self::assertEquals('test', $this->context->get(ConfigContext::CLASS_NAME));
    }

    public function testTargetAction()
    {
        self::assertNull($this->context->getTargetAction());

        $this->context->setTargetAction('test');
        self::assertEquals('test', $this->context->getTargetAction());
        self::assertEquals('test', $this->context->get(ConfigContext::TARGET_ACTION));
    }

    public function testMaxRelatedEntities()
    {
        self::assertNull($this->context->getMaxRelatedEntities());

        $this->context->setMaxRelatedEntities(123);
        self::assertEquals(123, $this->context->getMaxRelatedEntities());
        self::assertEquals(123, $this->context->get(ConfigContext::MAX_RELATED_ENTITIES));

        $this->context->setMaxRelatedEntities();
        self::assertNull($this->context->getMaxRelatedEntities());
        self::assertFalse($this->context->has(ConfigContext::MAX_RELATED_ENTITIES));
    }

    public function testExtras()
    {
        self::assertSame([], $this->context->getExtras());
        self::assertSame([], $this->context->get(ConfigContext::EXTRA));
        self::assertFalse($this->context->hasExtra('test'));
        self::assertFalse($this->context->has('test'));

        $extras = [new TestConfigExtra('test', ['test_attr' => true])];
        $this->context->setExtras($extras);
        self::assertEquals($extras, $this->context->getExtras());
        self::assertEquals(['test'], $this->context->get(ConfigContext::EXTRA));
        self::assertTrue($this->context->hasExtra('test'));
        self::assertTrue($this->context->has('test_attr'));

        $this->context->setExtras([]);
        self::assertSame([], $this->context->getExtras());
        self::assertFalse($this->context->hasExtra('test'));
        self::assertSame([], $this->context->get(ConfigContext::EXTRA));
    }

    public function testGetPropagableExtras()
    {
        self::assertSame([], $this->context->getPropagableExtras());

        $extras = [
            new TestConfigExtra('test'),
            new TestConfigSection('test_section')
        ];
        $this->context->setExtras($extras);
        self::assertEquals(
            [new TestConfigSection('test_section')],
            $this->context->getPropagableExtras()
        );

        $this->context->setExtras([]);
        self::assertSame([], $this->context->getPropagableExtras());
    }

    public function testFilters()
    {
        self::assertFalse($this->context->hasFilters());
        self::assertNull($this->context->getFilters());

        $filters = new FiltersConfig();

        $this->context->setFilters($filters);
        self::assertTrue($this->context->hasFilters());
        self::assertEquals($filters, $this->context->getFilters());
        self::assertEquals($filters, $this->context->get(FiltersConfigExtra::NAME));

        $this->context->setFilters(null);
        self::assertTrue($this->context->hasFilters());
    }

    public function testSorters()
    {
        self::assertFalse($this->context->hasSorters());
        self::assertNull($this->context->getSorters());

        $sorters = new SortersConfig();

        $this->context->setSorters($sorters);
        self::assertTrue($this->context->hasSorters());
        self::assertEquals($sorters, $this->context->getSorters());
        self::assertEquals($sorters, $this->context->get(SortersConfigExtra::NAME));

        $this->context->setSorters(null);
        self::assertTrue($this->context->hasSorters());
    }
}
