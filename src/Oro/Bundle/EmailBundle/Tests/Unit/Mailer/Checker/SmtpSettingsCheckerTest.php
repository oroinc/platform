<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;

class SmtpSettingsCheckerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|SmtpSettings */
    protected $smtpSettings;

    /** @var SmtpSettingsChecker */
    protected $smtpSettingsChecker;

    public function setUp()
    {
        $this->smtpSettings = $this->getMockBuilder(SmtpSettings::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->smtpSettingsChecker = new SmtpSettingsChecker();
    }

    public function testCheckConnectionWithNoError()
    {
//        $this->smtpSettingsChecker->checkConnection($this->smtpSettings);
    }
}
