<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Form\Type\LanguageType;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

class LanguageTypeTest extends FormIntegrationTestCase
{
    /** @var LanguageType */
    protected $formType;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $cmMock;

    /** @var LanguageProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $languageProvider;

    /** @var string */
    protected $locale;

    protected function setUp()
    {
        $this->locale = \Locale::getDefault();
        parent::setUp();
        $this->cmMock = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();

        $this->languageProvider = $this->getMockBuilder(LanguageProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new LanguageType($this->cmMock, $this->languageProvider);
    }

    protected function tearDown()
    {
        \Locale::setDefault($this->locale);
        parent::tearDown();
        unset($this->cmMock, $this->languageProvider, $this->formType);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_language', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('locale', $this->formType->getParent());
    }

    /**
     * @dataProvider buildFormProvider
     *
     * @param array $configData
     * @param array $choicesKeysExpected
     */
    public function testBuildForm(array $configData, array $choicesKeysExpected)
    {
        \Locale::setDefault('en');

        $this->languageProvider->expects($this->once())
            ->method('getEnabledLanguages')
            ->willReturn($configData);

        $form = $this->factory->create($this->formType);
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
                ['en'],
                ['English']
            ],
            'enabled languages are appeared' => [
                ['en', 'uk'],
                ['English', 'Ukrainian']
            ]
        ];
    }

    /**
     * @dataProvider buildViewProvider
     *
     * @param array $configData
     * @param $defaultLang
     * @param $useParentScopeValue
     */
    public function testBuildView(array $configData, $defaultLang, $useParentScopeValue)
    {
        $this->cmMock->expects($this->once())
            ->method('get')
            ->with(LanguageType::CONFIG_KEY, true)
            ->willReturn($defaultLang);

        $this->languageProvider->expects($this->once())
            ->method('getEnabledLanguages')
            ->willReturn($configData);

        /** @var FormView|\PHPUnit_Framework_MockObject_MockObject $mockFormView */
        $mockFormView = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mockParentForm */
        $mockParentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $mockFormInterface */
        $mockFormInterface = $this->getMock('Symfony\Component\Form\FormInterface');

        if (!$useParentScopeValue) {
            $mockParentForm->expects($this->once())->method('has')->with('use_parent_scope_value')->willReturn(true);
            $mockParentForm->expects($this->once())->method('remove')->with('use_parent_scope_value');
            $mockParentForm->expects($this->once())
                ->method('add')
                ->with('use_parent_scope_value', 'hidden', ['data' => 0]);
            $mockFormInterface->expects($this->any())->method('getParent')->willReturn($mockParentForm);
        }

        $this->formType->buildView($mockFormView, $mockFormInterface, []);
    }

    /**
     * @return array
     */
    public function buildViewProvider()
    {
        return [
            'default language enabled' => [
                ['en'],
                'en',
                true,
            ],
            'default language disabled' => [
                ['en', 'uk'],
                'ru',
                false
            ]
        ];
    }
}
