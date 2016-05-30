<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class FilterFieldsConfigExtraTest extends \PHPUnit_Framework_TestCase
{
    /** @var FilterFieldsConfigExtra */
    protected $extra;

    protected function setUp()
    {
        $this->extra = new FilterFieldsConfigExtra(
            [
                'products'   => ['id', 'code'],
                'categories' => ['name']
            ]
        );
    }

    public function testGetName()
    {
        $this->assertEquals(FilterFieldsConfigExtra::NAME, $this->extra->getName());
    }

    public function testConfigureContext()
    {
        $context = new ConfigContext();
        $this->extra->configureContext($context);
        $this->assertEquals(
            [
                'products'   => ['id', 'code'],
                'categories' => ['name']
            ],
            $context->get(FilterFieldsConfigExtra::NAME)
        );
    }

    public function testIsPropagable()
    {
        $this->assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        $this->assertEquals(
            'fields:products(id,code)categories(name)',
            $this->extra->getCacheKeyPart()
        );
    }
}
