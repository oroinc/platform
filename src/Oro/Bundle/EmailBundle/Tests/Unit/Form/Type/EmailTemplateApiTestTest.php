<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateApiType;

class EmailTemplateApiTestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailTemplateApiType
     */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $localeSettings;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    public function setUp()
    {
        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\UserConfigManager')
            ->disableOriginalConstructor()->getMock();

        $this->type = new EmailTemplateApiType(
            [['name' => 'test']],
            $this->configManager,
            $this->localeSettings
        );
    }

    public function tearDown()
    {
        unset($this->type);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_emailtemplate_api', $this->type->getName());
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber'));

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_locale.languages')
            ->will($this->returnValue(['en', 'fr_FR']));

        $this->localeSettings->expects($this->once())
            ->method('getLanguage')
            ->will($this->returnValue('ru_UA'));

        $this->localeSettings->expects($this->once())
            ->method('getLocalesByCodes')
            ->will($this->returnValue(['en', 'fr_FR']));

        $this->type->buildForm($builder, array());
    }
}
