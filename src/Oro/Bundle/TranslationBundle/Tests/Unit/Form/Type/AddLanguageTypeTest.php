<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Download\TranslationMetricsProviderInterface;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Form\Type\AddLanguageType;
use Oro\Bundle\TranslationBundle\Tests\Unit\Download\OroTranslationServiceAdapterTest;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Locales;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddLanguageTypeTest extends FormIntegrationTestCase
{
    protected AddLanguageType $formType;
    protected LanguageRepository $repository;
    protected LocaleSettings $localeSettings;
    protected TranslationMetricsProviderInterface $translationStatisticProvider;
    protected TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LanguageRepository::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->translationStatisticProvider = $this->createMock(TranslationMetricsProviderInterface::class);
        $this->translator = $this->getMockForAbstractClass(TranslatorInterface::class);
        $this->translator->method('trans')->willReturnArgument(0);

        $this->formType = new AddLanguageType(
            $this->repository,
            $this->localeSettings,
            $this->translationStatisticProvider,
            $this->translator
        );

        parent::setUp();
    }

    public function testGetBlockPrefix(): void
    {
        static::assertEquals('oro_translation_add_language', $this->formType->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        static::assertEquals(OroChoiceType::class, $this->formType->getParent());
    }

    public function testBuildForm(): void
    {
        $defaultLocale = 'de';
        $installedLanguages = ['en' => true, 'en_US' => true, 'uk_UA' => true];
        $allIntlLanguages = Locales::getNames($defaultLocale);
        $this->repository->method('getAvailableLanguageCodesAsArrayKeys')->willReturn($installedLanguages);
        $this->localeSettings->method('getLanguage')->willReturn($defaultLocale);

        $this->translationStatisticProvider->method('getAll')->willReturn(OroTranslationServiceAdapterTest::METRICS);

        $form = $this->factory->create(AddLanguageType::class);
        $choices = $form->getConfig()->getOption('choices');

        $expectedWithTranslations = \array_diff_key(
            \array_intersect_key($allIntlLanguages, OroTranslationServiceAdapterTest::METRICS),
            $installedLanguages
        );
        $expectedWithoutTranslations = \array_diff_key(
            $allIntlLanguages,
            $expectedWithTranslations,
            $installedLanguages
        );

        static::assertSame(
            \array_keys($expectedWithTranslations),
            \array_values($choices['oro.translation.language.form.select.group.crowdin'])
        );
        static::assertSame(
            \array_keys($expectedWithoutTranslations),
            \array_values($choices['oro.translation.language.form.select.group.intl'])
        );
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function getExtensions(): array
    {
        $choiceType = $this->getMockBuilder(OroChoiceType::class)
            ->onlyMethods(['configureOptions', 'getParent'])
            ->disableOriginalConstructor()
            ->getMock();

        $choiceType->method('getParent')->willReturn(ChoiceType::class);

        return [
            new PreloadedExtension(
                [$this->formType, OroChoiceType::class => $choiceType],
                []
            )
        ];
    }
}
