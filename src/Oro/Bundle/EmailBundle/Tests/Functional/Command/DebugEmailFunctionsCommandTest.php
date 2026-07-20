<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class DebugEmailFunctionsCommandTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testExecuteContainsAllFunctions(): void
    {
        $output = self::runCommand('oro:debug:email:functions');

        $emailTemplateSecurityPolicy = self::getContainer()->get('oro_email.twig.email_security_policy');

        foreach ($emailTemplateSecurityPolicy->getFunctions() as $function) {
            self::assertStringContainsString('* ' . $function, $output);
        }
    }
}
