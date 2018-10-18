<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Form\Type\LanguageType;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class LanguageTypeTest extends FormIntegrationTestCase
{
    /** @var LanguageType */
    protected $formType;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $cmMock;

    /** @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $languageProvider;

    /** @var string */
    protected $locale;

    protected function setUp()
    {
        $this->locale = \Locale::getDefault();
        $this->cmMock = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();

        $this->languageProvider = $this->getMockBuilder(LanguageProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new LanguageType($this->cmMock, $this->languageProvider);
        parent::setUp();
    }

    protected function tearDown()
    {
        \Locale::setDefault($this->locale);
        parent::tearDown();
        unset($this->cmMock, $this->languageProvider, $this->formType);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroChoiceType::class, $this->formType->getParent());
    }

    /**
     * @dataProvider buildFormProvider
     *
     * @param array $configData
     * @param string $defaultLang
     * @param array $choicesKeysExpected
     */
    public function testBuildForm(array $configData, $defaultLang, array $choicesKeysExpected)
    {
        \Locale::setDefault($defaultLang);

        $this->cmMock->expects($this->once())
            ->method('get')
            ->with(LanguageType::CONFIG_KEY, true)
            ->willReturn($defaultLang);

        $this->languageProvider->expects($this->once())->method('getEnabledLanguages')->willReturn($configData);

        $form = $this->factory->create(LanguageType::class);
        $choices = $form->getConfig()->getOption('choices');
        $this->assertEquals($choicesKeysExpected, array_keys($choices));
    }

    /**
     * @return array
     */
    public function buildFormProvider()
    {
        return [
            'only default language available' => [
                [],
                'en',
                ['English']
            ],
            'enabled languages are appeared' => [
                ['uk'],
                'en',
                ['English', 'Ukrainian']
            ]
        ];
    }

    /**
     * @dataProvider finishViewProvider
     *
     * @param string $defaultLang
     * @param array $expected
     * @param array $options
     */
    public function testFinishView($defaultLang, array $expected, array $options = [])
    {
        \Locale::setDefault($defaultLang);

        $this->cmMock->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    [LanguageType::CONFIG_KEY, true, false, null, $defaultLang],
                    ['oro_locale.languages', false, false, null, ['fr', 'nl']]
                ]
            );

        $this->languageProvider->expects($this->any())
            ->method('getEnabledLanguages')
            ->willReturn(['fr', 'nl', 'uk', 'jp']);

        $view = new FormView();
        $view->vars['choices'] = [
            new ChoiceView('en', 'en', 'English'),
            new ChoiceView('de', 'de', 'German'),
            new ChoiceView('fr', 'fr', 'French'),
            new ChoiceView('uk', 'uk', 'Ukrainian')
        ];

        $this->formType->finishView($view, $this->createMock('Symfony\Component\Form\Test\FormInterface'), $options);

        $this->assertCount(count($expected), $view->vars['choices']);

        foreach ($expected as $key => $data) {
            $this->assertEquals($data['label'], $view->vars['choices'][$key]->label);
            $this->assertEquals($data['value'], $view->vars['choices'][$key]->value);
            $this->assertEquals($data['data'], $view->vars['choices'][$key]->data);
        }
    }

    /**
     * @return array
     */
    public function finishViewProvider()
    {
        return [
            'show all' => [
                'defaultLang' => 'en',
                'expected' => [
                    0 => ['label' => 'English', 'value' => 'en', 'data' => 'en'],
                    2 => ['label' => 'French', 'value' => 'fr', 'data' => 'fr'],
                    3 => ['label' => 'Ukrainian', 'value' => 'uk', 'data' => 'uk']
                ],
                'options' => ['show_all' => true]
            ],
            'not show all' => [
                'defaultLang' => 'de',
                'expected' => [
                    2 => ['label' => 'French', 'value' => 'fr', 'data' => 'fr']
                ],
                'options' => ['show_all' => false]
            ]
        ];
    }

    /**
     * {@inheritdoc}
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
                    LanguageType::class => $this->formType,
                    OroChoiceType::class => $choiceType
                ],
                []
            )
        ];
    }
}
