<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Inspector;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\SecurityPolicyInspector\EmailTemplateSecurityPolicyInspectionResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class EmailTemplateSecurityPolicyInspectionResultTest extends TestCase
{
    public function testGetTemplateReturnsInjectedTemplate(): void
    {
        $template = new EmailTemplate('test_template');
        $violations = new ConstraintViolationList();

        $result = new EmailTemplateSecurityPolicyInspectionResult($template, $violations);

        self::assertSame($template, $result->getEmailTemplate());
    }

    public function testGetViolationsReturnsInjectedViolations(): void
    {
        $template = new EmailTemplate('test_template');
        $violations = new ConstraintViolationList([
            $this->buildViolation('Some violation message'),
        ]);

        $result = new EmailTemplateSecurityPolicyInspectionResult($template, $violations);

        self::assertSame($violations, $result->getViolations());
    }

    public function testHasViolationsReturnsTrueWhenViolationsExist(): void
    {
        $template = new EmailTemplate('test_template');
        $violations = new ConstraintViolationList([
            $this->buildViolation('Some violation'),
        ]);

        $result = new EmailTemplateSecurityPolicyInspectionResult($template, $violations);

        self::assertTrue($result->hasViolations());
    }

    public function testHasViolationsReturnsFalseWhenViolationListIsEmpty(): void
    {
        $template = new EmailTemplate('test_template');
        $violations = new ConstraintViolationList();

        $result = new EmailTemplateSecurityPolicyInspectionResult($template, $violations);

        self::assertFalse($result->hasViolations());
    }

    private function buildViolation(string $message): ConstraintViolation
    {
        return new ConstraintViolation($message, $message, [], null, '', null);
    }
}
