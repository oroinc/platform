<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Intl\Util\IntlTestHelper;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Form\Type\LanguageSelectType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class LanguageSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $cmMock;

    /**
     * @var AbstractType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver
     */
    protected $optionsResolverMock;

    public function setUp()
    {
        parent::setUp();

        $this->cmMock = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->optionsResolverMock = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new LanguageSelectType($this->cmMock);

    }

    public function tearDown()
    {
        unset($this->cmMock, $this->formType, $this->optionsResolver);

        parent::tearDown();
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(LanguageSelectType::NAME, $this->formType->getName());
    }

    /**
     * @dataProvider buildFormProvider
     *
     * @param string $defaultLang
     * @param array $choicesExpected
     */
    public function testBuildForm($defaultLang, array $choicesExpected)
    {
        IntlTestHelper::requireIntl($this);

        $this->cmMock->expects($this->at(0))->method('get')
            ->with($this->equalTo(LanguageSelectType::CONFIG_KEY_DEFAULT_LANGUAGE))
            ->will($this->returnValue($defaultLang));

        $form = $this->factory->create($this->formType);
        $choices = $form->getConfig()->getOption('choices');
        $this->assertEquals($choicesExpected, $choices);
    }

    /**
     * @return array
     */
    public function buildFormProvider()
    {
        return [
            'english' => [
                'en',
                Intl::getLanguageBundle()->getLanguageNames('en'),
            ],
            'russian' => [
                'ru',
                Intl::getLanguageBundle()->getLanguageNames('ru'),
            ],
        ];
    }
}
