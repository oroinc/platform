<?php

namespace EmailBundle\Tests\Unit\Util;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Provider\SmtpSettingsProvider;
use Oro\Bundle\EmailBundle\Util\ConfigurableTransport;

class ConfigurableTransportTest extends \PHPUnit_Framework_TestCase
{
    /** @var SmtpSettingsProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $provider;

    /** @var ConfigurableTransport */
    private $configurableTransport;

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder(SmtpSettingsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testUpdateSmtpSettings()
    {
        /** @var \Swift_Transport_EsmtpTransport|\PHPUnit_Framework_MockObject_MockObject $transport */
        $transport = $this->createMock('\Swift_Transport_EsmtpTransport');
        $transport->expects($this->once())
            ->method('setHost')
            ->with('mockHost');
        $transport->expects($this->once())
            ->method('setPort')
            ->with('mockPort');
        $transport->expects($this->once())
            ->method('setEncryption')
            ->with('mockEncryption');
        $transport->expects($this->exactly(2))
            ->method('__call')
            ->willReturnMap([
                ['setUserName', 'mockUserName'],
                ['setPassword', 'mockPassword'],
            ]);

        $this->provider->expects($this->once())
            ->method('getSmtpSettings')
            ->willReturn(new SmtpSettings(
                'mockHost',
                'mockPort',
                'mockEncryption',
                'mockUserName',
                'mockPassword'
            ));

        $this->configurableTransport = new ConfigurableTransport($this->provider, $transport);

        $this->configurableTransport->getDefaultTransport();
    }

    public function testUpdateSmtpSettingsIsNotPossible()
    {
        // NullTransport doesn't have methods setHost(), setPort() etc...
        $transport = \Swift_NullTransport::newInstance();

        $this->provider->expects($this->never())
            ->method('getSmtpSettings');

        $this->configurableTransport = new ConfigurableTransport($this->provider, $transport);

        $this->configurableTransport->getDefaultTransport();
    }
}
