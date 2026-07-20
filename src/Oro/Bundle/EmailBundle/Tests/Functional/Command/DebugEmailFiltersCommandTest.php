<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class DebugEmailFiltersCommandTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testExecuteContainsAllFilters(): void
    {
        $output = self::runCommand('oro:debug:email:filters');

        $emailTemplateSecurityPolicy = self::getContainer()->get('oro_email.twig.email_security_policy');

        foreach ($emailTemplateSecurityPolicy->getFilters() as $filter) {
            self::assertStringContainsString('* ' . $filter, $output);
        }
    }
}
