<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;

class FilterFieldsConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    /** @var FilterFieldsConfigExtra */
    private $extra;

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
        self::assertEquals(FilterFieldsConfigExtra::NAME, $this->extra->getName());
    }

    public function testConfigureContext()
    {
        $context = new ConfigContext();
        $this->extra->configureContext($context);
        self::assertEquals(
            [
                'products'   => ['id', 'code'],
                'categories' => ['name']
            ],
            $context->get(FilterFieldsConfigExtra::NAME)
        );
    }

    public function testIsPropagable()
    {
        self::assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart()
    {
        self::assertEquals(
            'fields:products(id,code)categories(name)',
            $this->extra->getCacheKeyPart()
        );
    }
}
