<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateApiType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

class EmailTemplateApiTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailTemplateApiType */
    private $type;

    /** @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $localeSettings;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp()
    {
        $this->localeSettings = $this->createMock(LocaleSettings::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->type = new EmailTemplateApiType($this->configManager, $this->localeSettings);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->configureOptions($resolver);
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
            ->with('oro_email.sanitize_html', false, false, null)
            ->willReturn(true);

        $this->localeSettings->expects($this->once())
            ->method('getLanguage')
            ->will($this->returnValue('ru_UA'));

        $this->localeSettings->expects($this->once())
            ->method('getLocalesByCodes')
            ->will($this->returnValue(['en', 'fr_FR']));

        $this->type->buildForm($builder, []);
    }
}
