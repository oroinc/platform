<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalizationHelperTest extends TestCase
{
    private LocalizationManager&MockObject $localizationManager;
    private LocalizationProviderInterface&MockObject $currentLocalizationProvider;
    private LocalizationHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->currentLocalizationProvider = $this->createMock(LocalizationProviderInterface::class);

        $this->helper = new LocalizationHelper($this->localizationManager, $this->currentLocalizationProvider);
    }

    private function getLocalization(int $id): Localization
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }

    private function getLocalizedFallbackValue(
        string $value,
        ?string $fallback,
        ?Localization $localization
    ): LocalizedFallbackValue {
        $localizedFallbackValue = new LocalizedFallbackValue();
        $localizedFallbackValue->setString($value);
        $localizedFallbackValue->setFallback($fallback);
        $localizedFallbackValue->setLocalization($localization);

        return $localizedFallbackValue;
    }

    public function testGetCurrentLocalization(): void
    {
        $localization = new Localization();

        $this->currentLocalizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->assertSame($localization, $this->helper->getCurrentLocalization());
    }

    public function testGetLocalizations(): void
    {
        $localizations = [new Localization()];

        $this->localizationManager->expects($this->once())
            ->method('getLocalizations')
            ->willReturn($localizations);

        $this->assertSame($localizations, $this->helper->getLocalizations());
    }

    public function testGetLocalizedValue(): void
    {
        $localization1 = $this->getLocalization(1);
        $localization2 = $this->getLocalization(2);
        $localization2->setParentLocalization($localization1);
        $localization3 = $this->getLocalization(3);
        $localization3->setParentLocalization($localization2);

        $value1 = $this->getLocalizedFallbackValue('value1', FallbackType::NONE, null);
        $value2 = $this->getLocalizedFallbackValue('value2', FallbackType::NONE, $localization1);
        $value3 = $this->getLocalizedFallbackValue('value3', FallbackType::PARENT_LOCALIZATION, $localization2);
        $value4 = $this->getLocalizedFallbackValue('value4', FallbackType::SYSTEM, $localization3);

        $values = new ArrayCollection([$value1, $value2, $value3, $value4]);

        // test 'FallbackType::NONE'
        $this->assertEquals($value2, $this->helper->getLocalizedValue($values, $localization1));
        // test 'FallbackType::PARENT_LOCALIZATION;
        $this->assertEquals($value2, $this->helper->getLocalizedValue($values, $localization2));
        // test 'FallbackType::SYSTEM;
        $this->assertEquals($value1, $this->helper->getLocalizedValue($values, $localization3));

        $badValues = new ArrayCollection([$value1, $value1]);
        $this->helper->getLocalizedValue($badValues, $localization1);
    }

    public function testGetFirstNonEmptyLocalizedValueForDefaultString(): void
    {
        $defaultValue = new LocalizedFallbackValue();
        $defaultValue->setString('0');
        $value = new LocalizedFallbackValue();
        $value->setString('0');
        $value->setLocalization(new Localization());
        $values = new ArrayCollection([$defaultValue, $value]);

        $this->assertEquals($defaultValue, $this->helper->getFirstNonEmptyLocalizedValue($values));
    }

    public function testGetFirstNonEmptyLocalizedValueForDefaultText(): void
    {
        $defaultValue = new LocalizedFallbackValue();
        $defaultValue->setText('0');
        $value = new LocalizedFallbackValue();
        $value->setText('0');
        $value->setLocalization(new Localization());
        $values = new ArrayCollection([$defaultValue, $value]);

        $this->assertEquals($defaultValue, $this->helper->getFirstNonEmptyLocalizedValue($values));
    }

    public function testGetFirstNonEmptyLocalizedValueWithoutDefault(): void
    {
        $value = new LocalizedFallbackValue();
        $value->setString('0');
        $value->setLocalization(new Localization());
        $values = new ArrayCollection([$value]);

        $this->assertEquals($value, $this->helper->getFirstNonEmptyLocalizedValue($values));
    }

    public function testGetFirstNonEmptyLocalizedValueWithoutDefaultText(): void
    {
        $value = new LocalizedFallbackValue();
        $value->setText('0');
        $value->setLocalization(new Localization());
        $values = new ArrayCollection([$value]);

        $this->assertEquals($value, $this->helper->getFirstNonEmptyLocalizedValue($values));
    }

    public function testGetFirstNonEmptyLocalizedValueNull(): void
    {
        $value = new LocalizedFallbackValue();
        $value->setText('');
        $value->setLocalization(new Localization());
        $values = new ArrayCollection([$value]);

        $this->assertNull($this->helper->getFirstNonEmptyLocalizedValue($values));
    }
}
