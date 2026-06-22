<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Inspector;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\SecurityPolicyInspector\EmailTemplateSecurityPolicyInspectionResult;
use Oro\Bundle\EmailBundle\SecurityPolicyInspector\EmailTemplateSecurityPolicyInspector;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailTemplateSecurityPolicy;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class EmailTemplateSecurityPolicyInspectorTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private ValidatorInterface&MockObject $validator;
    private EmailTemplateRepository&MockObject $emailTemplateRepository;
    private EmailTemplateSecurityPolicyInspector $inspector;

    #[\Override]
    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->emailTemplateRepository = $this->createMock(EmailTemplateRepository::class);
        $this->inspector = new EmailTemplateSecurityPolicyInspector(
            $doctrine,
            $this->validator
        );

        $doctrine
            ->method('getRepository')
            ->with(EmailTemplate::class)
            ->willReturn($this->emailTemplateRepository);

        $this->setUpLoggerMock($this->inspector);
    }

    public function testInspectByNameReturnsNullWhenNoResultExceptionThrownWithString(): void
    {
        $this->assertLoggerWarningMethodCalled();

        $this->emailTemplateRepository
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with(new EmailTemplateCriteria('nonexistent'))
            ->willThrowException(new NoResultException());

        $result = $this->inspector->inspectByName('nonexistent');

        self::assertNull($result);
    }

    public function testInspectByNameReturnsNullWhenNoResultExceptionThrownWithCriteria(): void
    {
        $this->assertLoggerWarningMethodCalled();

        $criteria = new EmailTemplateCriteria('nonexistent', 'Acme\Entity\User');

        $this->emailTemplateRepository
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with($criteria)
            ->willThrowException(new NoResultException());

        $result = $this->inspector->inspectByName($criteria);

        self::assertNull($result);
    }

    public function testInspectByNameReturnsNullWhenNonUniqueResultExceptionThrownWithString(): void
    {
        $this->assertLoggerWarningMethodCalled();

        $this->emailTemplateRepository
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with(new EmailTemplateCriteria('duplicate'))
            ->willThrowException(new NonUniqueResultException());

        $result = $this->inspector->inspectByName('duplicate');

        self::assertNull($result);
    }

    public function testInspectByNameReturnsNullWhenNonUniqueResultExceptionThrownWithCriteria(): void
    {
        $this->assertLoggerWarningMethodCalled();

        $criteria = new EmailTemplateCriteria('duplicate', 'Acme\Entity\User');

        $this->emailTemplateRepository
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with($criteria)
            ->willThrowException(new NonUniqueResultException());

        $result = $this->inspector->inspectByName($criteria);

        self::assertNull($result);
    }

    public function testInspectByNameReturnsResultWithNoViolationsWhenTemplateIsCleanWithString(): void
    {
        $this->assertLoggerDebugMethodCalled();

        $template = new EmailTemplate('clean_template');
        $violations = new ConstraintViolationList();

        $this->emailTemplateRepository
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with(new EmailTemplateCriteria('clean_template'))
            ->willReturn($template);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($template, self::isInstanceOf(EmailTemplateSecurityPolicy::class))
            ->willReturn($violations);

        $result = $this->inspector->inspectByName('clean_template');

        self::assertInstanceOf(EmailTemplateSecurityPolicyInspectionResult::class, $result);
        self::assertSame($template, $result->getEmailTemplate());
        self::assertFalse($result->hasViolations());
    }

    public function testInspectByNameReturnsResultWithNoViolationsWhenTemplateIsCleanWithCriteria(): void
    {
        $this->assertLoggerDebugMethodCalled();

        $template = new EmailTemplate('clean_template');
        $violations = new ConstraintViolationList();
        $criteria = new EmailTemplateCriteria('clean_template', 'Acme\Entity\User');

        $this->emailTemplateRepository
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with($criteria)
            ->willReturn($template);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($template, self::isInstanceOf(EmailTemplateSecurityPolicy::class))
            ->willReturn($violations);

        $result = $this->inspector->inspectByName($criteria);

        self::assertInstanceOf(EmailTemplateSecurityPolicyInspectionResult::class, $result);
        self::assertSame($template, $result->getEmailTemplate());
        self::assertFalse($result->hasViolations());
    }

    public function testInspectByNameReturnsResultWithViolationsWhenTemplateHasViolationsWithString(): void
    {
        $template = new EmailTemplate('bad_template');
        $violations = new ConstraintViolationList();
        $violations->add(
            new ConstraintViolation('Disallowed filter "json_encode".', '', [], null, '', null)
        );

        $this->emailTemplateRepository
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with(new EmailTemplateCriteria('bad_template'))
            ->willReturn($template);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($template, self::isInstanceOf(EmailTemplateSecurityPolicy::class))
            ->willReturn($violations);

        $result = $this->inspector->inspectByName('bad_template');

        self::assertNotNull($result);
        self::assertSame($template, $result->getEmailTemplate());
        self::assertTrue($result->hasViolations());
        self::assertCount(1, $result->getViolations());
    }

    public function testInspectByNameReturnsResultWithViolationsWhenTemplateHasViolationsWithCriteria(): void
    {
        $template = new EmailTemplate('bad_template');
        $violations = new ConstraintViolationList();
        $violations->add(
            new ConstraintViolation('Disallowed filter "json_encode".', '', [], null, '', null)
        );
        $criteria = new EmailTemplateCriteria('bad_template', 'Acme\Entity\User');

        $this->emailTemplateRepository
            ->expects(self::once())
            ->method('findWithLocalizations')
            ->with($criteria)
            ->willReturn($template);

        $this->validator
            ->expects(self::once())
            ->method('validate')
            ->with($template, self::isInstanceOf(EmailTemplateSecurityPolicy::class))
            ->willReturn($violations);

        $result = $this->inspector->inspectByName($criteria);

        self::assertNotNull($result);
        self::assertSame($template, $result->getEmailTemplate());
        self::assertTrue($result->hasViolations());
        self::assertCount(1, $result->getViolations());
    }

    public function testInspectAllReturnsEmptyArrayWhenNoTemplatesExist(): void
    {
        $this->assertLoggerDebugMethodCalled();

        $this->emailTemplateRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([]);

        $results = $this->inspector->inspectAll();

        self::assertSame([], $results);
    }

    public function testInspectAllReturnsOneResultPerTemplate(): void
    {
        $template1 = new EmailTemplate('template1');
        $template2 = new EmailTemplate('template2');

        $this->emailTemplateRepository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$template1, $template2]);

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $results = $this->inspector->inspectAll();

        self::assertCount(2, $results);
        self::assertSame($template1, $results[0]->getEmailTemplate());
        self::assertSame($template2, $results[1]->getEmailTemplate());
    }

    public function testInspectAllValidatesEachTemplateWithSecurityPolicyConstraint(): void
    {
        $template1 = new EmailTemplate('template1');
        $template2 = new EmailTemplate('template2');

        $this->emailTemplateRepository
            ->method('findAll')
            ->willReturn([$template1, $template2]);

        $this->validator
            ->expects(self::exactly(2))
            ->method('validate')
            ->with(
                self::isInstanceOf(EmailTemplate::class),
                self::isInstanceOf(EmailTemplateSecurityPolicy::class)
            )
            ->willReturn(new ConstraintViolationList());

        $this->inspector->inspectAll();
    }

    public function testInspectAllPreservesViolationsPerTemplate(): void
    {
        $cleanTemplate = new EmailTemplate('clean');
        $dirtyTemplate = new EmailTemplate('dirty');

        $noViolations = new ConstraintViolationList();
        $withViolations = new ConstraintViolationList();
        $withViolations->add(
            new ConstraintViolation('Disallowed filter "json_encode".', '', [], null, '', null)
        );

        $this->emailTemplateRepository
            ->method('findAll')
            ->willReturn([$cleanTemplate, $dirtyTemplate]);

        $this->validator
            ->method('validate')
            ->willReturnCallback(
                static fn (EmailTemplate $template) => $template === $cleanTemplate
                    ? $noViolations
                    : $withViolations
            );

        $results = $this->inspector->inspectAll();

        self::assertFalse($results[0]->hasViolations());
        self::assertTrue($results[1]->hasViolations());
    }
}
