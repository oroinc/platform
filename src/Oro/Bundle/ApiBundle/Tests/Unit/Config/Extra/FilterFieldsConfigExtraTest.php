<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Extra;

use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use PHPUnit\Framework\TestCase;

class FilterFieldsConfigExtraTest extends TestCase
{
    private const array FIELD_FILTERS = [
        'products'   => ['id', 'code'],
        'categories' => ['name'],
        'users' => [],
        'organizations' => null
    ];

    private FilterFieldsConfigExtra $extra;

    #[\Override]
    protected function setUp(): void
    {
        $this->extra = new FilterFieldsConfigExtra(self::FIELD_FILTERS);
    }

    public function testGetName(): void
    {
        self::assertEquals(FilterFieldsConfigExtra::NAME, $this->extra->getName());
    }

    public function testGetFieldFilters(): void
    {
        self::assertSame(
            self::FIELD_FILTERS,
            $this->extra->getFieldFilters()
        );
    }

    public function testConfigureContextAndGetFieldFilters(): void
    {
        $context = new ConfigContext();
        $this->extra->configureContext($context);
        self::assertSame(
            self::FIELD_FILTERS,
            $context->get(FilterFieldsConfigExtra::NAME)
        );
    }

    public function testIsPropagable(): void
    {
        self::assertTrue($this->extra->isPropagable());
    }

    public function testCacheKeyPart(): void
    {
        self::assertEquals(
            'fields:products(id,code)categories(name)users()organizations',
            $this->extra->getCacheKeyPart()
        );
    }
}
