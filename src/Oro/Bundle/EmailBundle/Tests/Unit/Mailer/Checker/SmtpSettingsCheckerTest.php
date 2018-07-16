<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;

class SmtpSettingsCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|SmtpSettings */
    protected $smtpSettings;

    /** @var \PHPUnit\Framework\MockObject\MockObject|SmtpSettingsChecker */
    protected $smtpSettingsChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DirectMailer */
    protected $directMailer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject */
    protected $mailerTransport;

    public function setUp()
    {
        $this->smtpSettings = $this->getMockBuilder(SmtpSettings::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mailerTransport = $this->getMockBuilder(\Swift_Transport_EsmtpTransport::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directMailer = $this->getMockBuilder(DirectMailer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->smtpSettingsChecker = new SmtpSettingsChecker($this->directMailer);
    }

    public function testCheckConnectionWithNoError()
    {
        $this->directMailer->expects($this->once())
            ->method('afterPrepareSmtpTransport')
            ->with($this->smtpSettings);

        $this->directMailer->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($this->mailerTransport));

        $this->mailerTransport->expects($this->once())
            ->method('start');

        $this->assertEmpty($this->smtpSettingsChecker->checkConnection($this->smtpSettings));
    }

    public function testCheckConnectionWithError()
    {
        $this->directMailer->expects($this->once())
            ->method('afterPrepareSmtpTransport')
            ->with($this->smtpSettings);

        $this->directMailer->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($this->mailerTransport));

        $exception = new \Swift_TransportException('Test exception message');

        $this->mailerTransport->expects($this->once())
            ->method('start')
            ->will($this->throwException($exception));

        $this->assertSame($this->smtpSettingsChecker->checkConnection($this->smtpSettings), $exception->getMessage());
    }
}
