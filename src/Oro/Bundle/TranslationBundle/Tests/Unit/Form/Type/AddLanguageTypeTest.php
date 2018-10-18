<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Form\Type\AddLanguageType;
use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;

class AddLanguageTypeTest extends FormIntegrationTestCase
{
    /** @var AddLanguageType */
    protected $formType;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LanguageRepository */
    protected $repository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LocaleSettings */
    protected $localeSettings;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslationStatisticProvider */
    protected $translationStatisticProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    protected $translator;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(LanguageRepository::class)->disableOriginalConstructor()->getMock();

        $this->localeSettings = $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock();

        $this->translationStatisticProvider = $this->getMockBuilder(TranslationStatisticProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMockForAbstractClass(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return $key;
            });

        $this->formType = new AddLanguageType(
            $this->repository,
            $this->localeSettings,
            $this->translationStatisticProvider,
            $this->translator
        );
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
        unset(
            $this->repository,
            $this->localeSettings,
            $this->formType,
            $this->translationStatisticProvider,
            $this->translator
        );
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_translation_add_language', $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(OroChoiceType::class, $this->formType->getParent());
    }

    /**
     * @dataProvider buildFormProvider
     *
     * @param array $currentCodes
     * @param array $crowdinLangs
     * @param array $codesExpected
     */
    public function testBuildForm(array $currentCodes, array $crowdinLangs, array $codesExpected)
    {
        //        IntlTestHelper::requireIntl($this);

        $this->repository->expects($this->once())->method('getAvailableLanguageCodes')->willReturn($currentCodes);
        $this->localeSettings->expects($this->once())->method('getLanguage')->willReturn('de');
        $this->translationStatisticProvider->expects($this->once())->method('get')->willReturn($crowdinLangs);

        $form = $this->factory->create(AddLanguageType::class);
        $choices = $form->getConfig()->getOption('choices');

        foreach ($codesExpected as $value) {
            $this->assertContains($value, $choices['oro.translation.language.form.select.group.crowdin']);
            $this->assertNotContains($value, $choices['oro.translation.language.form.select.group.intl']);
        }

        foreach ($currentCodes as $value) {
            $this->assertNotContains($value, $choices['oro.translation.language.form.select.group.crowdin']);
            $this->assertNotContains($value, $choices['oro.translation.language.form.select.group.intl']);
        }
    }

    /**
     * @return array
     */
    public function buildFormProvider()
    {
        $crowdInLangs = [
            ['code' => 'en_US', 'realCode' => 'en'],
            ['code' => 'fr_FR', 'realCode' => 'fr'],
            ['code' => 'pl_PL', 'realCode' => 'pl'],
        ];

        return [
            'no current languages' => [
                'currentCodes' => [],
                'crowdinLangs' => $crowdInLangs,
                'codesExpected' => ['en_US', 'fr_FR', 'pl_PL'],
            ],
            '1 current language' => [
                'currentCodes' => ['en_US'],
                'crowdinLangs' => $crowdInLangs,
                'codesExpected' => ['fr_FR', 'pl_PL'],
            ],
            '2 current languages' => [
                'currentCodes' => ['fr_FR', 'pl_PL'],
                'crowdinLangs' => $crowdInLangs,
                'codesExpected' => ['en_US'],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $choiceType = $this->getMockBuilder(OroChoiceType::class)
            ->setMethods(['configureOptions', 'getParent'])
            ->disableOriginalConstructor()
            ->getMock();
        $choiceType->expects($this->any())->method('getParent')->willReturn(ChoiceType::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    OroChoiceType::class => $choiceType,
                ],
                []
            )
        ];
    }
}
