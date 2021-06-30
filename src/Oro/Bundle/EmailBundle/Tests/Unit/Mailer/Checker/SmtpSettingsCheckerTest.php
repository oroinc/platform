<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Checker;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\EmailBundle\Mailer\DirectMailer;

class SmtpSettingsCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|SmtpSettingsChecker */
    protected $smtpSettingsChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DirectMailer */
    protected $directMailer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\PHPUnit\Framework\MockObject\MockObject */
    protected $mailerTransport;

    protected function setUp(): void
    {
        $this->mailerTransport = $this->getMockBuilder(\Swift_Transport_EsmtpTransport::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directMailer = $this->getMockBuilder(DirectMailer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->smtpSettingsChecker = new SmtpSettingsChecker($this->directMailer);
    }

    public function testCheckConnectionWithNotEligibleSmtpSettings()
    {
        $this->directMailer->expects($this->never())
            ->method($this->anything());

        $this->mailerTransport->expects($this->never())
            ->method($this->anything());

        $this->assertNotEmpty(
            'Not eligible SmtpSettings are given',
            $this->smtpSettingsChecker->checkConnection(new SmtpSettings())
        );
    }

    public function testCheckConnectionWithNoError()
    {
        $smtpSettings = new SmtpSettings('smtp.host', 25, 'ssl');
        $this->directMailer->expects($this->once())
            ->method('afterPrepareSmtpTransport')
            ->with($smtpSettings);

        $this->directMailer->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($this->mailerTransport));

        $this->mailerTransport->expects($this->once())
            ->method('start');

        $this->assertEmpty($this->smtpSettingsChecker->checkConnection($smtpSettings));
    }

    public function testCheckConnectionWithError()
    {
        $smtpSettings = new SmtpSettings('smtp.host', 25, 'ssl');
        $this->directMailer->expects($this->once())
            ->method('afterPrepareSmtpTransport')
            ->with($smtpSettings);

        $this->directMailer->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($this->mailerTransport));

        $exception = new \Swift_TransportException('Test exception message');

        $this->mailerTransport->expects($this->once())
            ->method('start')
            ->will($this->throwException($exception));

        $this->assertSame($this->smtpSettingsChecker->checkConnection($smtpSettings), $exception->getMessage());
    }
}
