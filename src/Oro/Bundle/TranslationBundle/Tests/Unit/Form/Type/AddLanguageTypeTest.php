<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Form\Type\AddLanguageType;
use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;

class AddLanguageTypeTest extends FormIntegrationTestCase
{
    /** @var AddLanguageType */
    protected $formType;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LanguageRepository */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LocaleSettings */
    protected $localeSettings;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslationStatisticProvider */
    protected $translationStatisticProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
    protected $translator;

    protected function setUp()
    {
        parent::setUp();

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

    public function testGetName()
    {
        $this->assertEquals('oro_translation_add_language', $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals('oro_translation_add_language', $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_choice', $this->formType->getParent());
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

        $form = $this->factory->create($this->formType);
        $choices = $form->getConfig()->getOption('choices');

        foreach ($codesExpected as $value) {
            $this->assertArrayHasKey($value, $choices['oro.translation.language.form.select.group.crowdin']);
            $this->assertArrayNotHasKey($value, $choices['oro.translation.language.form.select.group.intl']);
        }

        foreach ($currentCodes as $value) {
            $this->assertArrayNotHasKey($value, $choices['oro.translation.language.form.select.group.crowdin']);
            $this->assertArrayNotHasKey($value, $choices['oro.translation.language.form.select.group.intl']);
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
        $choiceType->expects($this->any())->method('getParent')->willReturn('choice');

        return [
            new PreloadedExtension(
                [
                    OroChoiceType::NAME => $choiceType,
                ],
                []
            )
        ];
    }
}
