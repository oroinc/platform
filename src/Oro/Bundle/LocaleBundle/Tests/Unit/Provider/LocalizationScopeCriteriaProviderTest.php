<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\LocaleBundle\Provider\LocalizationScopeCriteriaProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalizationScopeCriteriaProviderTest extends TestCase
{
    private CurrentLocalizationProvider&MockObject $currentLocalizationProvider;
    private LocalizationScopeCriteriaProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->currentLocalizationProvider = $this->createMock(CurrentLocalizationProvider::class);

        $this->provider = new LocalizationScopeCriteriaProvider($this->currentLocalizationProvider);
    }

    public function testGetCriteriaField(): void
    {
        $this->assertEquals(LocalizationScopeCriteriaProvider::LOCALIZATION, $this->provider->getCriteriaField());
    }

    public function testGetCriteriaValue(): void
    {
        $localization = new Localization();

        $this->currentLocalizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->assertSame($localization, $this->provider->getCriteriaValue());
    }

    public function testGetCriteriaValueType(): void
    {
        $this->assertEquals(Localization::class, $this->provider->getCriteriaValueType());
    }
}
