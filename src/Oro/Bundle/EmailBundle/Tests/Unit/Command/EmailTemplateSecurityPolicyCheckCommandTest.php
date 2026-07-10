<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Command;

use Oro\Bundle\EmailBundle\Command\EmailTemplateSecurityPolicyCheckCommand;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\SecurityPolicyInspector\EmailTemplateSecurityPolicyInspectionResult;
use Oro\Bundle\EmailBundle\SecurityPolicyInspector\EmailTemplateSecurityPolicyInspector;
use Oro\Bundle\EntityExtendBundle\Test\ExtendedEntityTestTrait;
use Oro\Component\Testing\Command\CommandTestingTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class EmailTemplateSecurityPolicyCheckCommandTest extends TestCase
{
    use CommandTestingTrait;
    use ExtendedEntityTestTrait;

    private EmailTemplateSecurityPolicyInspector&MockObject $emailTemplateSecurityPolicyInspector;
    private EmailTemplateSecurityPolicyCheckCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->emailTemplateSecurityPolicyInspector = $this->createMock(EmailTemplateSecurityPolicyInspector::class);
        $this->command = new EmailTemplateSecurityPolicyCheckCommand($this->emailTemplateSecurityPolicyInspector);

        $this->entityFieldTestExtension->addExpectation(
            EmailTemplate::class,
            'getWebsite',
            function (array $arguments, object $object, mixed &$result): bool {
                $result = null;

                return true;
            }
        );
    }

    public function testExecuteReturnsFailureWhenNamedTemplateNotFound(): void
    {
        $this->emailTemplateSecurityPolicyInspector
            ->expects(self::once())
            ->method('inspectByName')
            ->with('nonexistent')
            ->willReturn(null);

        $tester = $this->doExecuteCommand($this->command, ['template' => 'nonexistent']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertOutputContains($tester, 'Email template "nonexistent" not found.');
    }

    public function testExecuteShowsSuccessMessageWhenNamedTemplateHasNoViolations(): void
    {
        $result = new EmailTemplateSecurityPolicyInspectionResult(
            new EmailTemplate('clean_template'),
            new ConstraintViolationList()
        );

        $this->emailTemplateSecurityPolicyInspector
            ->expects(self::once())
            ->method('inspectByName')
            ->with('clean_template')
            ->willReturn($result);

        $tester = $this->doExecuteCommand($this->command, ['template' => 'clean_template']);

        $this->assertSuccessReturnCode($tester);
        $this->assertOutputContains($tester, 'No security policy violations found in template "clean_template".');
    }

    public function testExecuteRendersViolationsTableForNamedTemplateWithViolations(): void
    {
        $violation = new ConstraintViolation('Disallowed filter "json_encode".', '', [], null, '', null);
        $template = (new EmailTemplate('bad_template'))->setEntityName('Acme\Entity\Order');
        $result = new EmailTemplateSecurityPolicyInspectionResult(
            $template,
            new ConstraintViolationList([$violation])
        );

        $this->emailTemplateSecurityPolicyInspector
            ->expects(self::once())
            ->method('inspectByName')
            ->with('bad_template')
            ->willReturn($result);

        $tester = $this->doExecuteCommand($this->command, ['template' => 'bad_template']);

        $this->assertSuccessReturnCode($tester);
        $this->assertOutputContains($tester, 'bad_template');
        $this->assertOutputContains($tester, 'Acme\Entity\Order');
        $this->assertOutputContains($tester, 'Disallowed filter "json_encode".');
    }

    public function testExecuteRendersEmptyEntityColumnInTableWhenTemplateEntityNameIsNull(): void
    {
        $violation = new ConstraintViolation('Disallowed filter "json_encode".', '', [], null, '', null);
        $result = new EmailTemplateSecurityPolicyInspectionResult(
            new EmailTemplate('no_entity_template'),
            new ConstraintViolationList([$violation])
        );

        $this->emailTemplateSecurityPolicyInspector
            ->expects(self::once())
            ->method('inspectByName')
            ->with('no_entity_template')
            ->willReturn($result);

        $tester = $this->doExecuteCommand($this->command, ['template' => 'no_entity_template']);

        $this->assertSuccessReturnCode($tester);
        $this->assertOutputContains($tester, 'no_entity_template');
        $this->assertOutputContains($tester, 'Entity');
        $this->assertOutputContains($tester, 'Disallowed filter "json_encode".');
    }

    public function testExecuteReturnsSuccessForNamedTemplateWithViolations(): void
    {
        $violation = new ConstraintViolation('Some violation.', '', [], null, '', null);
        $result = new EmailTemplateSecurityPolicyInspectionResult(
            new EmailTemplate('some_template'),
            new ConstraintViolationList([$violation])
        );

        $this->emailTemplateSecurityPolicyInspector
            ->expects(self::once())
            ->method('inspectByName')
            ->willReturn($result);

        $tester = $this->doExecuteCommand($this->command, ['template' => 'some_template']);

        $this->assertSuccessReturnCode($tester);
    }

    public function testExecuteShowsSuccessMessageWhenAllTemplatesAreClean(): void
    {
        $result = new EmailTemplateSecurityPolicyInspectionResult(
            new EmailTemplate('clean'),
            new ConstraintViolationList()
        );

        $this->emailTemplateSecurityPolicyInspector
            ->expects(self::once())
            ->method('inspectAll')
            ->willReturn([$result]);

        $tester = $this->doExecuteCommand($this->command);

        $this->assertSuccessReturnCode($tester);
        $this->assertOutputContains($tester, 'No security policy violations found in any email template.');
    }

    public function testExecuteShowsSuccessMessageWhenDatabaseHasNoTemplates(): void
    {
        $this->emailTemplateSecurityPolicyInspector
            ->expects(self::once())
            ->method('inspectAll')
            ->willReturn([]);

        $tester = $this->doExecuteCommand($this->command);

        $this->assertSuccessReturnCode($tester);
        $this->assertOutputContains($tester, 'No security policy violations found in any email template.');
    }

    public function testExecuteRendersViolationsTableWhenSomeTemplatesHaveViolations(): void
    {
        $violation = new ConstraintViolation('Disallowed filter "json_encode".', '', [], null, '', null);
        $dirtyTemplate = (new EmailTemplate('dirty_template'))->setEntityName('Acme\Entity\Product');
        $dirtyResult = new EmailTemplateSecurityPolicyInspectionResult(
            $dirtyTemplate,
            new ConstraintViolationList([$violation])
        );
        $cleanResult = new EmailTemplateSecurityPolicyInspectionResult(
            new EmailTemplate('clean_template'),
            new ConstraintViolationList()
        );

        $this->emailTemplateSecurityPolicyInspector
            ->expects(self::once())
            ->method('inspectAll')
            ->willReturn([$dirtyResult, $cleanResult]);

        $tester = $this->doExecuteCommand($this->command);

        $this->assertSuccessReturnCode($tester);
        $this->assertOutputContains($tester, 'dirty_template');
        $this->assertOutputContains($tester, 'Acme\Entity\Product');
        $this->assertOutputContains($tester, 'Disallowed filter "json_encode".');
        $this->assertOutputNotContains($tester, 'clean_template');
    }

    public function testExecutePrintsSummaryNoteWithViolatingAndTotalCounts(): void
    {
        $violation = new ConstraintViolation('Some violation.', '', [], null, '', null);
        $dirtyResult = new EmailTemplateSecurityPolicyInspectionResult(
            new EmailTemplate('dirty_template'),
            new ConstraintViolationList([$violation])
        );
        $cleanResult = new EmailTemplateSecurityPolicyInspectionResult(
            new EmailTemplate('clean_template'),
            new ConstraintViolationList()
        );

        $this->emailTemplateSecurityPolicyInspector
            ->expects(self::once())
            ->method('inspectAll')
            ->willReturn([$dirtyResult, $cleanResult]);

        $tester = $this->doExecuteCommand($this->command);

        $this->assertOutputContains($tester, '1 of 2 template(s) have security policy violations.');
    }

    public function testExecuteAlwaysReturnsSuccessEvenWhenAllTemplatesCheckFindsViolations(): void
    {
        $violation = new ConstraintViolation('Some violation.', '', [], null, '', null);
        $result = new EmailTemplateSecurityPolicyInspectionResult(
            new EmailTemplate('template'),
            new ConstraintViolationList([$violation])
        );

        $this->emailTemplateSecurityPolicyInspector
            ->expects(self::once())
            ->method('inspectAll')
            ->willReturn([$result]);

        $tester = $this->doExecuteCommand($this->command);

        $this->assertSuccessReturnCode($tester);
    }

    public function testExecuteRendersMultipleViolationsAsMultipleRowsInTable(): void
    {
        $violation1 = new ConstraintViolation('Disallowed filter "json_encode".', '', [], null, '', null);
        $violation2 = new ConstraintViolation('Disallowed function "random".', '', [], null, '', null);
        $result = new EmailTemplateSecurityPolicyInspectionResult(
            new EmailTemplate('multi_violation_template'),
            new ConstraintViolationList([$violation1, $violation2])
        );

        $this->emailTemplateSecurityPolicyInspector
            ->expects(self::once())
            ->method('inspectByName')
            ->willReturn($result);

        $tester = $this->doExecuteCommand($this->command, ['template' => 'multi_violation_template']);

        $this->assertOutputContains($tester, 'Disallowed filter "json_encode".');
        $this->assertOutputContains($tester, 'Disallowed function "random".');
    }
}
