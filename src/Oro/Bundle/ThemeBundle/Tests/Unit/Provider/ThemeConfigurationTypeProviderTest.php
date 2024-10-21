<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Provider;

use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationTypeProvider;
use Oro\Bundle\ThemeBundle\Tests\Unit\Stubs\Provider\ThemeConfigurationTypeProviderStub;
use PHPUnit\Framework\TestCase;

final class ThemeConfigurationTypeProviderTest extends TestCase
{
    private ThemeConfigurationTypeProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new ThemeConfigurationTypeProvider([
            new ThemeConfigurationTypeProviderStub('storefront', 'Storefront'),
            new ThemeConfigurationTypeProviderStub('sales-frontend', 'Sales Frontend')
        ]);
    }

    public function testGetTypes(): void
    {
        self::assertEquals(['storefront', 'sales-frontend'], $this->provider->getTypes());
    }

    public function testGetLabelsAndTypes(): void
    {
        $expected = ['Storefront' => 'storefront', 'Sales Frontend' => 'sales-frontend'];

        self::assertEquals($expected, $this->provider->getLabelsAndTypes());
    }
}
