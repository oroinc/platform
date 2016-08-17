<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

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
     * @param array  $configData
     * @param string $defaultLang
     * @param array  $choicesKeysExpected
     */
    public function testBuildForm(array $configData, $defaultLang, array $choicesKeysExpected)
    {
        IntlTestHelper::requireIntl($this);

        \Locale::setDefault($defaultLang);

        $this->cmMock->expects($this->once())
            ->method('get')
            ->with(LanguageType::CONFIG_KEY, true)
            ->willReturn($defaultLang);

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
}
