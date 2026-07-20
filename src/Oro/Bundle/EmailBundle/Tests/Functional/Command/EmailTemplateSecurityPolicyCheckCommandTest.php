<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Command;

use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplatesForSecurityPolicyCheckData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;
use Symfony\Component\Console\Command\Command;

class EmailTemplateSecurityPolicyCheckCommandTest extends WebTestCase
{
    use CommandTestingTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadEmailTemplatesForSecurityPolicyCheckData::class]);
    }

    public function testExecuteChecksNonExistentTemplateAndReturnsFailure(): void
    {
        $tester = $this->doExecuteCommand(
            'oro:email:template:security-policy-check',
            ['template' => 'this_template_does_not_exist_xyz']
        );

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertOutputContains($tester, 'Email template "this_template_does_not_exist_xyz" not found.');
    }

    public function testExecuteChecksSingleCleanTemplateAndReportsNoViolations(): void
    {
        $tester = $this->doExecuteCommand(
            'oro:email:template:security-policy-check',
            ['template' => LoadEmailTemplatesForSecurityPolicyCheckData::CLEAN_TEMPLATE]
        );

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertOutputContains(
            $tester,
            sprintf(
                'No security policy violations found in template "%s".',
                LoadEmailTemplatesForSecurityPolicyCheckData::CLEAN_TEMPLATE
            )
        );
    }

    public function testExecuteChecksSingleTemplateWithViolationsAndReturnsSuccess(): void
    {
        $tester = $this->doExecuteCommand(
            'oro:email:template:security-policy-check',
            ['template' => LoadEmailTemplatesForSecurityPolicyCheckData::VIOLATION_TEMPLATE]
        );

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertOutputContains($tester, LoadEmailTemplatesForSecurityPolicyCheckData::VIOLATION_TEMPLATE);
    }

    public function testExecuteChecksAllTemplatesAndDisplaysViolationsWithSummary(): void
    {
        $tester = $this->doExecuteCommand('oro:email:template:security-policy-check');

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertOutputContains($tester, LoadEmailTemplatesForSecurityPolicyCheckData::VIOLATION_TEMPLATE);
        $this->assertOutputContains($tester, 'template(s) have security policy violations');
    }

    public function testExecuteChecksAllTemplatesAndDoesNotIncludeCleanTemplatesInTable(): void
    {
        $tester = $this->doExecuteCommand('oro:email:template:security-policy-check');

        $this->assertOutputNotContains($tester, LoadEmailTemplatesForSecurityPolicyCheckData::CLEAN_TEMPLATE);
    }
}
