<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config;

use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ConfigContextTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigContext */
    protected $context;

    protected function setUp()
    {
        $this->context = new ConfigContext();
    }

    public function testClassName()
    {
        $this->assertNull($this->context->getClassName());

        $this->context->setClassName('test');
        $this->assertEquals('test', $this->context->getClassName());
        $this->assertEquals('test', $this->context->get(ConfigContext::CLASS_NAME));
    }

    public function testConfigSections()
    {
        $this->assertSame([], $this->context->getConfigSections());
        $this->assertNull($this->context->get(ConfigContext::CONFIG_SECTION));

        $this->context->setConfigSections(['test']);
        $this->assertEquals(['test'], $this->context->getConfigSections());
        $this->assertEquals(['test'], $this->context->get(ConfigContext::CONFIG_SECTION));

        $this->context->setConfigSections([]);
        $this->assertSame([], $this->context->getConfigSections());
        $this->assertNull($this->context->get(ConfigContext::CONFIG_SECTION));
    }

    public function testFilters()
    {
        $this->assertFalse($this->context->hasFilters());
        $this->assertNull($this->context->getFilters());

        $filters = ConfigUtil::getInitialConfig();

        $this->context->setFilters($filters);
        $this->assertTrue($this->context->hasFilters());
        $this->assertEquals($filters, $this->context->getFilters());
        $this->assertEquals($filters, $this->context->get(ConfigUtil::FILTERS));

        $this->context->setFilters(null);
        $this->assertTrue($this->context->hasFilters());
    }

    public function testSorters()
    {
        $this->assertFalse($this->context->hasSorters());
        $this->assertNull($this->context->getSorters());

        $sorters = ConfigUtil::getInitialConfig();

        $this->context->setSorters($sorters);
        $this->assertTrue($this->context->hasSorters());
        $this->assertEquals($sorters, $this->context->getSorters());
        $this->assertEquals($sorters, $this->context->get(ConfigUtil::SORTERS));

        $this->context->setSorters(null);
        $this->assertTrue($this->context->hasSorters());
    }
}
