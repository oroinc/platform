<?php

namespace Oro\Bundle\LoggerBundle\Tests\Functional\Command;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class LoggerEmailNotificationCommandTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testRunCommand()
    {
        $params = ['--recipients="recipient1@example.com;recipient2@example.com"'];
        $result = $this->runCommand('oro:logger:email-notification', $params);

        static::assertStringContainsString('Error logs notification will be sent to listed email addresses', $result);
    }

    public function testRunCommandWithFailedValidation()
    {
        $params = ['--recipients="recipient1@example.com;not_valid_email'];
        $result = $this->runCommand('oro:logger:email-notification', $params);

        static::assertStringContainsString('not_valid_email - This value is not a valid email address.', $result);
    }

    public function testRunCommandToDisableNotifications()
    {
        $configGlobal = self::getConfigManager('global');
        $configGlobal->set('oro_logger.email_notification_recipients', 'recipient1@example.com');
        $params = ['--disable'];

        $result = $this->runCommand('oro:logger:email-notification', $params);
        $expectedContent = "Error logs notification successfully disabled.";
        static::assertStringContainsString($expectedContent, $result);
        $this->assertEquals('', $configGlobal->get('oro_logger.email_notification_recipients'));

        $result = $this->runCommand('oro:logger:email-notification', $params);
        $expectedContent = "Error logs notification already disabled.";
        static::assertStringContainsString($expectedContent, $result);
        $this->assertEquals('', $configGlobal->get('oro_logger.email_notification_recipients'));
    }

    public function testCommandContainsHelp()
    {
        $result = $this->runCommand('oro:logger:email-notification', ['--help']);

        static::assertStringContainsString("Usage: oro:logger:email-notification [options]", $result);
    }
}
