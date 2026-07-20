<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class DebugEmailTagsCommandTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testExecuteContainsAllTags(): void
    {
        $output = self::runCommand('oro:debug:email:tags');

        $emailTemplateSecurityPolicy = self::getContainer()->get('oro_email.twig.email_security_policy');

        foreach ($emailTemplateSecurityPolicy->getTags() as $tag) {
            self::assertStringContainsString('* ' . $tag, $output);
        }
    }
}
