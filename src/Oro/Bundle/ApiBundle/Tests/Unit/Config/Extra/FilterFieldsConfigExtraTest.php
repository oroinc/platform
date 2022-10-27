<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;

class FilterFieldsConfigExtraTest extends \PHPUnit\Framework\TestCase
{
    private const FIELD_FILTERS = [
        'products'   => ['id', 'code'],
        'categories' => ['name']
    ];

    /** @var FilterFieldsConfigExtra */
    private $extra;

    protected function setUp(): void
    {
        $this->extra = new FilterFieldsConfigExtra(self::FIELD_FILTERS);
    }

    public function testGetName()
    {
        self::assertEquals(FilterFieldsConfigExtra::NAME, $this->extra->getName());
    }

    public function testGetFieldFilters()
    {
        self::assertEquals(
            self::FIELD_FILTERS,
            $this->extra->getFieldFilters()
        );
    }

    public function testConfigureContextAndGetFieldFilters()
    {
        $context = new ConfigContext();
        $this->extra->configureContext($context);
        self::assertEquals(
            self::FIELD_FILTERS,
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
