<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\FallbackTrait;

class LocalizationHelperTest extends \PHPUnit\Framework\TestCase
{
    use FallbackTrait;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $localizationManager;

    /** @var LocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $currentLocalizationProvider;

    /** @var LocalizationHelper */
    protected $helper;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->localizationManager = $this->getMockBuilder(LocalizationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currentLocalizationProvider = $this->getMockBuilder(LocalizationProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new LocalizationHelper($this->localizationManager, $this->currentLocalizationProvider);
    }

    public function testGetCurrentLocalization()
    {
        $localization = new Localization();

        $this->currentLocalizationProvider->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->assertSame($localization, $this->helper->getCurrentLocalization());
    }

    public function testGetLocalizations()
    {
        $localizations = [new Localization()];

        $this->localizationManager->expects($this->once())
            ->method('getLocalizations')
            ->willReturn($localizations);

        $this->assertSame($localizations, $this->helper->getLocalizations());
    }

    public function testGetLocalizedValue()
    {
        $this->assertFallbackValue($this->helper, 'getLocalizedValue');
    }

    public function testGetFirstNonEmptyLocalizedValueForDefaultString()
    {
        $defaultValue = new LocalizedFallbackValue();
        $defaultValue->setString('0');
        $value = new LocalizedFallbackValue();
        $value->setString('0');
        $value->setLocalization(new Localization());
        $values = new ArrayCollection(
            [
                $defaultValue,
                $value
            ]
        );

        $this->assertEquals($defaultValue, $this->helper->getFirstNonEmptyLocalizedValue($values));
    }

    public function testGetFirstNonEmptyLocalizedValueForDefaultText()
    {
        $defaultValue = new LocalizedFallbackValue();
        $defaultValue->setText('0');
        $value = new LocalizedFallbackValue();
        $value->setText('0');
        $value->setLocalization(new Localization());
        $values = new ArrayCollection(
            [
                $defaultValue,
                $value
            ]
        );

        $this->assertEquals($defaultValue, $this->helper->getFirstNonEmptyLocalizedValue($values));
    }

    public function testGetFirstNonEmptyLocalizedValueWithoutDefault()
    {
        $value = new LocalizedFallbackValue();
        $value->setString('0');
        $value->setLocalization(new Localization());
        $values = new ArrayCollection(
            [
                $value
            ]
        );

        $this->assertEquals($value, $this->helper->getFirstNonEmptyLocalizedValue($values));
    }

    public function testGetFirstNonEmptyLocalizedValueWithoutDefaultText()
    {
        $value = new LocalizedFallbackValue();
        $value->setText('0');
        $value->setLocalization(new Localization());
        $values = new ArrayCollection(
            [
                $value
            ]
        );

        $this->assertEquals($value, $this->helper->getFirstNonEmptyLocalizedValue($values));
    }

    public function testGetFirstNonEmptyLocalizedValueNull()
    {
        $value = new LocalizedFallbackValue();
        $value->setText('');
        $value->setLocalization(new Localization());
        $values = new ArrayCollection(
            [
                $value
            ]
        );

        $this->assertNull($this->helper->getFirstNonEmptyLocalizedValue($values));
    }
}
