<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

use Oro\Bundle\LocaleBundle\Form\Type\LanguageType;
use Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface;

class LanguageTypeTest extends FormIntegrationTestCase
{
    /** @var LanguageType */
    protected $formType;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $cmMock;

    /**
     * @var string
     */
    protected $locale;

    protected function setUp()
    {
        $this->locale = \Locale::getDefault();
        parent::setUp();
        $this->cmMock   = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->formType = new LanguageType($this->cmMock);
    }

    protected function tearDown()
    {
        \Locale::setDefault($this->locale);
        parent::tearDown();
        unset($this->cmMock, $this->formType);
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

        $this->cmMock->expects($this->at(0))->method('get')
            ->with($this->equalTo(LanguageType::CONFIG_KEY), $this->equalTo(true))
            ->will($this->returnValue($defaultLang));

        $this->cmMock->expects($this->at(1))->method('get')
            ->with($this->equalTo(TranslationStatusInterface::CONFIG_KEY))
            ->will($this->returnValue($configData));

        $form    = $this->factory->create($this->formType);
        $choices = $form->getConfig()->getOption('choices');
        $this->assertEquals($choicesKeysExpected, array_keys($choices));
    }

    /**
     * @return array
     */
    public function buildFormProvider()
    {
        return [
            'only default language available'       => [
                [],
                'en',
                ['en']
            ],
            'disabled language should not appears' => [
                ['fr' => TranslationStatusInterface::STATUS_DOWNLOADED],
                'en',
                ['en']
            ],
            'enabled languages are appeared'        => [
                ['uk' => TranslationStatusInterface::STATUS_ENABLED],
                'en',
                ['en', 'uk']
            ]
        ];
    }
}
