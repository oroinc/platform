<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\SystemVariablesProvider;

class SystemVariablesProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dateTimeFormatter;

    /** @var SystemVariablesProvider */
    protected $provider;

    protected function setUp()
    {
        $translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('trans')
            ->will($this->returnArgument(0));

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dateTimeFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new SystemVariablesProvider(
            $translator,
            $this->configManager,
            $this->dateTimeFormatter
        );
    }

    public function testGetVariableDefinitions()
    {
        $result = $this->provider->getVariableDefinitions();
        $this->assertEquals(
            [
                'currentDate'  => ['type' => 'string', 'label' => 'oro.email.emailtemplate.current_date'],
                'currentTime'  => ['type' => 'string', 'label' => 'oro.email.emailtemplate.current_time'],
                'appURL'       => ['type' => 'string', 'label' => 'oro.email.emailtemplate.app_url'],
            ],
            $result
        );
    }

    public function testGetVariableValues()
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_ui.application_name', false, false, null, ''],
                        ['oro_ui.application_title', false, false, null, ''],
                        ['oro_ui.application_url', false, false, null, 'http://localhost/'],
                    ]
                )
            );
        $this->dateTimeFormatter->expects($this->once())
            ->method('formatDate')
            ->with($this->isInstanceOf('\DateTime'))
            ->will($this->returnValue('date'));
        $this->dateTimeFormatter->expects($this->once())
            ->method('formatTime')
            ->with($this->isInstanceOf('\DateTime'))
            ->will($this->returnValue('time'));

        $result = $this->provider->getVariableValues();
        $this->assertEquals(
            [
                'appShortName' => '',
                'appFullName'  => '',
                'appURL'       => 'http://localhost/',
                'currentDate'  => 'date',
                'currentTime'  => 'time',
            ],
            $result
        );
    }
}
