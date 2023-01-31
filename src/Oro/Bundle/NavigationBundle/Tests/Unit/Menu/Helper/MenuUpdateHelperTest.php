<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Menu\Helper;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\NavigationBundle\Menu\Helper\MenuUpdateHelper;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Contracts\Translation\TranslatorInterface;

class MenuUpdateHelperTest extends \PHPUnit\Framework\TestCase
{
    private TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator;

    private LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject $localizationHelper;

    private MenuUpdateHelper $helper;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->helper = new MenuUpdateHelper($this->translator, $this->localizationHelper);
    }

    private function getLanguage(string $code): Language
    {
        $language = new Language();
        $language->setCode($code);

        return $language;
    }

    public function testApplyLocalizedFallbackValue(): void
    {
        $update = new MenuUpdateStub();

        $this->translator->expects(self::exactly(3))
            ->method('trans')
            ->willReturnMap([
                ['test.title', [], null, 'en', 'EN Test Title'],
                ['test.title', [], null, 'de', 'DE Test Title'],
            ]);

        $enLocalization = new Localization();
        $enLocalization->setLanguage($this->getLanguage('en'));

        $deLocalization = new Localization();
        $deLocalization->setLanguage($this->getLanguage('de'));

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizations')
            ->willReturn([$enLocalization, $deLocalization]);

        $this->helper->applyLocalizedFallbackValue($update, 'test.title', 'title', 'string');

        $deFallbackValue = new LocalizedFallbackValue();
        $deFallbackValue->setLocalization($deLocalization);
        $deFallbackValue->setString('DE Test Title');

        $enFallbackValue = new LocalizedFallbackValue();
        $enFallbackValue->setLocalization($enLocalization);
        $enFallbackValue->setFallback(FallbackType::SYSTEM);

        $result = new MenuUpdateStub();
        $result->setDefaultTitle('EN Test Title');
        $result->addTitle($enFallbackValue);
        $result->addTitle($deFallbackValue);

        self::assertEquals($result, $update);
    }

    public function testApplyLocalizedFallbackValueWhenEmptyValue(): void
    {
        $update = new MenuUpdateStub();

        $this->translator->expects(self::never())
            ->method('trans');

        $enLocalization = new Localization();
        $enLocalization->setLanguage($this->getLanguage('en'));

        $deLocalization = new Localization();
        $deLocalization->setLanguage($this->getLanguage('de'));

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizations')
            ->willReturn([$enLocalization, $deLocalization]);

        $this->helper->applyLocalizedFallbackValue($update, '', 'title', 'string');

        $deFallbackValue = new LocalizedFallbackValue();
        $deFallbackValue->setLocalization($deLocalization);
        $deFallbackValue->setFallback(FallbackType::SYSTEM);

        $enFallbackValue = new LocalizedFallbackValue();
        $enFallbackValue->setLocalization($enLocalization);
        $enFallbackValue->setFallback(FallbackType::SYSTEM);

        $result = new MenuUpdateStub();
        $result->setDefaultTitle('');
        $result->addTitle($enFallbackValue);
        $result->addTitle($deFallbackValue);

        self::assertEquals($result, $update);
    }

    public function testApplyLocalizedFallbackValueWithExistedFallbackValue(): void
    {
        $update = new MenuUpdateStub();
        $update->setDefaultTitle('Test Title');

        $this->translator->expects(self::never())
            ->method('trans');

        $this->localizationHelper->expects(self::never())
            ->method('getLocalizations');

        $this->helper->applyLocalizedFallbackValue($update, 'test.title', 'title', 'string');
    }

    public function testApplyLocalizedFallbackValueWithUndefinedName(): void
    {
        $update = new MenuUpdateStub();

        $this->translator->expects(self::never())
            ->method('trans');

        $this->localizationHelper->expects(self::never())
            ->method('getLocalizations');

        $message = 'Can\'t get a way to read the property "undefined_names" in class "' . MenuUpdateStub::class . '".';

        $this->expectException(NoSuchPropertyException::class);
        $this->expectExceptionMessage($message);

        $this->helper->applyLocalizedFallbackValue($update, 'test.title', 'undefined_name', 'string');
    }
}
