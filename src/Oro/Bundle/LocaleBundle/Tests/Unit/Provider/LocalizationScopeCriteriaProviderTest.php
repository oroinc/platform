<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\LocaleBundle\Provider\LocalizationScopeCriteriaProvider;

class LocalizationScopeCriteriaProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CurrentLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $currentLocalizationProvider;

    /** @var LocalizationScopeCriteriaProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->currentLocalizationProvider = $this->createMock(CurrentLocalizationProvider::class);

        $this->provider = new LocalizationScopeCriteriaProvider($this->currentLocalizationProvider);
    }

    public function testGetCriteriaField()
    {
        $this->assertEquals(LocalizationScopeCriteriaProvider::LOCALIZATION, $this->provider->getCriteriaField());
    }

    public function testGetCriteriaValue()
    {
        $localization = new Localization();

        $this->currentLocalizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->assertSame($localization, $this->provider->getCriteriaValue());
    }

    public function testGetCriteriaValueType()
    {
        $this->assertEquals(Localization::class, $this->provider->getCriteriaValueType());
    }
}
