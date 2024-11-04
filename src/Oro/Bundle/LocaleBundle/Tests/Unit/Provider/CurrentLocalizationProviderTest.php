<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Gedmo\Translatable\TranslatableListener;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\TranslationBundle\Entity\Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class CurrentLocalizationProviderTest extends TestCase
{
    private static string $defaultLocale;

    private LocalizationManager|MockObject $localizationManager;

    private LocaleAwareInterface|MockObject $translator;

    private TranslatableListener|MockObject $translatableListener;

    #[\Override]
    public static function setUpBeforeClass(): void
    {
        self::$defaultLocale = \Locale::getDefault();
    }

    #[\Override]
    public static function tearDownAfterClass(): void
    {
        \Locale::setDefault(self::$defaultLocale);
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->translator = $this->createMock(LocaleAwareInterface::class);
        $this->translatableListener = $this->createMock(TranslatableListener::class);

        \Locale::setDefault(self::$defaultLocale);
    }

    private function createProvider(array $extensions): CurrentLocalizationProvider
    {
        return new CurrentLocalizationProvider(
            $extensions,
            $this->localizationManager,
            $this->translator,
            $this->translatableListener
        );
    }

    public function testGetCurrentLocalizationAndNoExtensions(): void
    {
        self::assertNull($this->createProvider([])->getCurrentLocalization());
    }

    public function testGetCurrentLocalization(): void
    {
        $localization = new Localization();

        $extension1 = $this->createMock(CurrentLocalizationExtensionInterface::class);
        $extension2 = $this->createMock(CurrentLocalizationExtensionInterface::class);
        $extension3 = $this->createMock(CurrentLocalizationExtensionInterface::class);

        $extension1->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn(null);
        $extension2->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);
        $extension3->expects(self::never())
            ->method('getCurrentLocalization');

        $provider = $this->createProvider([$extension1, $extension2, $extension3]);

        self::assertSame($localization, $provider->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationWhenAllExtensionsDidNotReturnLocalization(): void
    {
        $extension1 = $this->createMock(CurrentLocalizationExtensionInterface::class);

        $extension1->expects(self::once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $provider = $this->createProvider([$extension1]);

        self::assertNull($provider->getCurrentLocalization());
    }

    public function testSetCurrentLocalizationWithExplicitLocalization(): void
    {
        $explicitLocalization = (new Localization())
            ->setLanguage((new Language())->setCode('de'))
            ->setFormattingCode('de_DE');

        $extension = $this->createMock(CurrentLocalizationExtensionInterface::class);
        $extension
            ->expects(self::never())
            ->method('getCurrentLocalization');

        $this->localizationManager
            ->expects(self::never())
            ->method('getDefaultLocalization');

        $this->translator
            ->expects(self::once())
            ->method('setLocale')
            ->with($explicitLocalization->getLanguageCode());

        $this->translatableListener
            ->expects(self::once())
            ->method('setTranslatableLocale')
            ->with($explicitLocalization->getLanguageCode());

        $provider = $this->createProvider([$extension]);

        self::assertEquals(self::$defaultLocale, \Locale::getDefault());

        $provider->setCurrentLocalization($explicitLocalization);

        self::assertSame($explicitLocalization, $provider->getCurrentLocalization());
        self::assertEquals($explicitLocalization->getFormattingCode(), \Locale::getDefault());
    }

    public function testSetCurrentLocalizationWithNullWhenHasCurrentLocalization(): void
    {
        $currentLocalization = (new Localization())
            ->setLanguage((new Language())->setCode('de'))
            ->setFormattingCode('de_DE');

        $extension = $this->createMock(CurrentLocalizationExtensionInterface::class);
        $extension
            ->expects(self::exactly(2))
            ->method('getCurrentLocalization')
            ->willReturn($currentLocalization);

        $this->localizationManager
            ->expects(self::never())
            ->method('getDefaultLocalization');

        $this->translator
            ->expects(self::once())
            ->method('setLocale')
            ->with($currentLocalization->getLanguageCode());

        $this->translatableListener
            ->expects(self::once())
            ->method('setTranslatableLocale')
            ->with($currentLocalization->getLanguageCode());

        $provider = $this->createProvider([$extension]);

        self::assertEquals(self::$defaultLocale, \Locale::getDefault());

        $provider->setCurrentLocalization(null);

        self::assertSame($currentLocalization, $provider->getCurrentLocalization());
        self::assertEquals($currentLocalization->getFormattingCode(), \Locale::getDefault());
    }

    public function testSetCurrentLocalizationWithNullWhenNoCurrentLocalization(): void
    {
        $defaultLocalization = (new Localization())
            ->setLanguage((new Language())->setCode('de'))
            ->setFormattingCode('de_DE');

        $extension = $this->createMock(CurrentLocalizationExtensionInterface::class);
        $extension
            ->expects(self::exactly(2))
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $this->localizationManager
            ->expects(self::once())
            ->method('getDefaultLocalization')
            ->willReturn($defaultLocalization);

        $this->translator
            ->expects(self::once())
            ->method('setLocale')
            ->with($defaultLocalization->getLanguageCode());

        $this->translatableListener
            ->expects(self::once())
            ->method('setTranslatableLocale')
            ->with($defaultLocalization->getLanguageCode());

        $provider = $this->createProvider([$extension]);

        self::assertEquals(self::$defaultLocale, \Locale::getDefault());

        $provider->setCurrentLocalization(null);

        self::assertNull($provider->getCurrentLocalization());
        self::assertEquals($defaultLocalization->getFormattingCode(), \Locale::getDefault());
    }

    public function testSetCurrentLocalizationWithNullWhenNoDefaultLocalization(): void
    {
        $extension = $this->createMock(CurrentLocalizationExtensionInterface::class);
        $extension
            ->expects(self::exactly(2))
            ->method('getCurrentLocalization')
            ->willReturn(null);

        $this->localizationManager
            ->expects(self::once())
            ->method('getDefaultLocalization')
            ->willReturn(null);

        $this->translator
            ->expects(self::once())
            ->method('setLocale')
            ->with(Configuration::DEFAULT_LANGUAGE);

        $this->translatableListener
            ->expects(self::once())
            ->method('setTranslatableLocale')
            ->with(Configuration::DEFAULT_LANGUAGE);

        $provider = $this->createProvider([$extension]);

        self::assertEquals(self::$defaultLocale, \Locale::getDefault());

        $provider->setCurrentLocalization(null);

        self::assertNull($provider->getCurrentLocalization());
        self::assertEquals(Configuration::DEFAULT_LOCALE, \Locale::getDefault());
    }
}
