<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Provider;

use Oro\Bundle\PlatformBundle\Provider\DbalTypeDefaultValueProvider;

class DbalTypeDefaultValueProviderTest extends \PHPUnit\Framework\TestCase
{
    private DbalTypeDefaultValueProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new DbalTypeDefaultValueProvider();
    }

    public function testAddDefaultValuesForDbalTypes(): void
    {
        $this->provider->addDefaultValuesForDbalTypes(['sample_type_1' => true, 'sample_type_2' => 'sample_value']);

        self::assertTrue($this->provider->hasDefaultValueForDbalType('sample_type_1'));
        self::assertTrue($this->provider->hasDefaultValueForDbalType('sample_type_2'));
        self::assertTrue($this->provider->getDefaultValueForDbalType('sample_type_1'));
        self::assertSame('sample_value', $this->provider->getDefaultValueForDbalType('sample_type_2'));
    }

    public function testHasDefaultValueForDbalType(): void
    {
        self::assertTrue($this->provider->hasDefaultValueForDbalType('integer'));
        self::assertFalse($this->provider->hasDefaultValueForDbalType('missing_type'));
    }

    public function testGetDefaultValueForDbalType(): void
    {
        self::assertSame(0, $this->provider->getDefaultValueForDbalType('integer'));
    }
}
