<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Download\TranslationMetricsProviderInterface;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Form\Type\AddLanguageType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Locales;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddLanguageTypeTest extends FormIntegrationTestCase
{
    /** @var LanguageRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var TranslationMetricsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translationStatisticProvider;

    /** @var AddLanguageType */
    private $formType;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LanguageRepository::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->translationStatisticProvider = $this->createMock(TranslationMetricsProviderInterface::class);

        $translator = $this->getMockForAbstractClass(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->formType = new AddLanguageType(
            $this->repository,
            $this->localeSettings,
            $this->translationStatisticProvider,
            $translator
        );

        parent::setUp();
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals('oro_translation_add_language', $this->formType->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        self::assertEquals(OroChoiceType::class, $this->formType->getParent());
    }

    public function testBuildForm(): void
    {
        $metrics = [
            'uk_UA' => [
                'code' => 'uk_UA',
                'translationStatus' => 100,
                'lastBuildDate' => '2020-08-24T00:00:00+0300'
            ],
            'de_DE' => [
                'code' => 'de_DE',
                'altCode' => 'de',
                'translationStatus' => 90,
                'lastBuildDate' => '2020-10-03T23:59:59+0100'
            ],
            'fr_FR' => [
                'code' => 'fr_FR',
                'altCode' => 'fr',
                'translationStatus' => 80,
                'lastBuildDate' => '2020-07-14T00:00:00+0200'
            ],
        ];

        $defaultLocale = 'de';
        $installedLanguages = ['en' => true, 'en_US' => true, 'uk_UA' => true];
        $allIntlLanguages = Locales::getNames($defaultLocale);
        $this->repository->expects(self::any())
            ->method('getAvailableLanguageCodesAsArrayKeys')
            ->willReturn($installedLanguages);
        $this->localeSettings->expects(self::any())
            ->method('getLanguage')
            ->willReturn($defaultLocale);

        $this->translationStatisticProvider->expects(self::any())
            ->method('getAll')
            ->willReturn($metrics);

        $form = $this->factory->create(AddLanguageType::class);
        $choices = $form->getConfig()->getOption('choices');

        $expectedWithTranslations = [
            'de_DE' => $allIntlLanguages['de_DE'],
            'fr_FR' => $allIntlLanguages['fr_FR'],
        ];
        $expectedWithoutTranslations = array_diff_key(
            $allIntlLanguages,
            $expectedWithTranslations,
            $installedLanguages
        );

        self::assertSame(
            array_keys($expectedWithTranslations),
            array_values($choices['oro.translation.language.form.select.group.crowdin'])
        );
        self::assertSame(
            array_keys($expectedWithoutTranslations),
            array_values($choices['oro.translation.language.form.select.group.intl'])
        );
    }

    protected function getExtensions(): array
    {
        $choiceType = $this->getMockBuilder(OroChoiceType::class)
            ->onlyMethods(['configureOptions', 'getParent'])
            ->disableOriginalConstructor()
            ->getMock();
        $choiceType->expects(self::any())
            ->method('getParent')
            ->willReturn(ChoiceType::class);

        return [
            new PreloadedExtension(
                [$this->formType, OroChoiceType::class => $choiceType],
                []
            )
        ];
    }
}
