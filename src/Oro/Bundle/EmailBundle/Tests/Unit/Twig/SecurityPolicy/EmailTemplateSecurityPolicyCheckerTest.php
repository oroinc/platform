<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig\SecurityPolicy;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate as EmailTemplateEntity;
use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyCheckBefore;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateMetadataProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Stub\UnknownSecurityPolicyViolationStub;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\EmailTemplateSecurityPolicyChecker;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyFilterViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyFunctionViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyMethodViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyPropertyViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyTagViolation;
use Oro\Bundle\EmailBundle\Twig\SecurityPolicy\Violation\EmailTemplateSecurityPolicyViolationInterface;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\TemplateSecurityPolicyChecker;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyFilterViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyFunctionViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyMethodViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyPropertyViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyTagViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyViolationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class EmailTemplateSecurityPolicyCheckerTest extends TestCase
{
    private TemplateSecurityPolicyChecker&MockObject $templateSecurityPolicyChecker;
    private EmailTemplateMetadataProvider&MockObject $emailTemplateMetadataProvider;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private EmailTemplateSecurityPolicyChecker $checker;

    #[\Override]
    protected function setUp(): void
    {
        $this->templateSecurityPolicyChecker = $this->createMock(TemplateSecurityPolicyChecker::class);
        $this->emailTemplateMetadataProvider = $this->createMock(EmailTemplateMetadataProvider::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->checker = new EmailTemplateSecurityPolicyChecker(
            $this->templateSecurityPolicyChecker,
            $this->emailTemplateMetadataProvider,
            $this->eventDispatcher
        );
    }

    public function testCheckSecurityPolicyReturnsEmptyArrayWhenNoViolationsFound(): void
    {
        $entityClass = \stdClass::class;

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName(null);
        $emailTemplate->setSubject('Hello');
        $emailTemplate->setContent('<p>Body</p>');

        $this->emailTemplateMetadataProvider->expects(self::once())
            ->method('getEmailTemplateMetadata')
            ->with($emailTemplate)
            ->willReturn([
                EmailTemplateMetadataProvider::ENTITY_NAME => $entityClass,
                EmailTemplateMetadataProvider::IS_SYSTEM => true,
            ]);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(EmailTemplateSecurityPolicyCheckBefore::class));

        $this->templateSecurityPolicyChecker->expects(self::exactly(2))
            ->method('checkSecurityPolicy')
            ->willReturn([]);

        $result = $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertSame([], $result);
    }

    public function testCheckSecurityPolicyResolvesEntityClassFromMetadataAndPassesToInnerChecker(): void
    {
        $entityClass = \stdClass::class;

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('order_confirmation');
        $emailTemplate->setSubject('Your order');
        $emailTemplate->setContent('<p>Details</p>');

        $this->emailTemplateMetadataProvider->expects(self::once())
            ->method('getEmailTemplateMetadata')
            ->with($emailTemplate)
            ->willReturn([
                EmailTemplateMetadataProvider::ENTITY_NAME => $entityClass,
                EmailTemplateMetadataProvider::IS_SYSTEM => true,
            ]);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(
                    static function (EmailTemplateSecurityPolicyCheckBefore $event) use ($entityClass): bool {
                        return $event->getVariableTypes() === ['entity' => $entityClass];
                    }
                )
            );

        $this->templateSecurityPolicyChecker->expects(self::exactly(2))
            ->method('checkSecurityPolicy')
            ->willReturn([]);

        $result = $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertSame([], $result);
    }

    public function testCheckSecurityPolicyPassesEmptyVariableTypesWhenMetadataNotFound(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('unknown_template');
        $emailTemplate->setSubject('Hi');
        $emailTemplate->setContent('Body');

        $this->emailTemplateMetadataProvider->expects(self::once())
            ->method('getEmailTemplateMetadata')
            ->with($emailTemplate)
            ->willReturn(null);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(static function (EmailTemplateSecurityPolicyCheckBefore $event): bool {
                    return $event->getVariableTypes() === [];
                })
            );

        $this->templateSecurityPolicyChecker->expects(self::exactly(2))
            ->method('checkSecurityPolicy')
            ->willReturn([]);

        $result = $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertSame([], $result);
    }

    public function testCheckSecurityPolicyPassesEmptyVariableTypesWhenMetadataHasNoEntityName(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('generic_template');
        $emailTemplate->setSubject('Hi');
        $emailTemplate->setContent('Body');

        $this->emailTemplateMetadataProvider->expects(self::once())
            ->method('getEmailTemplateMetadata')
            ->with($emailTemplate)
            ->willReturn([
                EmailTemplateMetadataProvider::IS_SYSTEM => false,
                EmailTemplateMetadataProvider::IS_EDITABLE => true,
            ]);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(static function (EmailTemplateSecurityPolicyCheckBefore $event): bool {
                    return $event->getVariableTypes() === [];
                })
            );

        $this->templateSecurityPolicyChecker->expects(self::exactly(2))
            ->method('checkSecurityPolicy')
            ->willReturn([]);

        $result = $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertSame([], $result);
    }

    public function testCheckSecurityPolicyUsesEventModifiedVariableTypes(): void
    {
        $modifiedVariableTypes = ['order' => \stdClass::class, 'customer' => \ArrayObject::class];

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName(null);
        $emailTemplate->setSubject('Hi');
        $emailTemplate->setContent('Body');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (EmailTemplateSecurityPolicyCheckBefore $event) use ($modifiedVariableTypes): object {
                    $event->setVariableTypes($modifiedVariableTypes);

                    return $event;
                }
            );

        $this->templateSecurityPolicyChecker->expects(self::exactly(2))
            ->method('checkSecurityPolicy')
            ->with(self::isType('string'), $modifiedVariableTypes)
            ->willReturn([]);

        $result = $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertSame([], $result);
    }

    /**
     * @dataProvider violationWrappingDataProvider
     */
    public function testCheckSecurityPolicyWrapsViolationFromInnerChecker(
        SecurityPolicyViolationInterface $innerViolation,
        string $expectedClass,
        string $expectedName,
        int $expectedTemplateLine,
        ?string $expectedVariableName,
        ?string $expectedEntityClass,
        \Throwable $expectedCause,
    ): void {
        $this->checker->setEmailTemplateFields(['content']);

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName(null);
        $emailTemplate->setContent('{{ entity.value }}');

        $this->templateSecurityPolicyChecker->expects(self::once())
            ->method('checkSecurityPolicy')
            ->willReturn([$innerViolation]);

        $result = $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertCount(1, $result);

        $wrapped = $result[0];

        self::assertInstanceOf($expectedClass, $wrapped);
        self::assertInstanceOf(EmailTemplateSecurityPolicyViolationInterface::class, $wrapped);
        self::assertSame('content', $wrapped->getTemplateField());
        self::assertSame($expectedName, $wrapped->getName());
        self::assertSame($expectedTemplateLine, $wrapped->getTemplateLine());
        self::assertSame($expectedVariableName, $wrapped->getVariableName());
        self::assertSame($expectedEntityClass, $wrapped->getEntityClass());
        self::assertSame($expectedCause, $wrapped->getCause());
    }

    public static function violationWrappingDataProvider(): iterable
    {
        $cause = new \RuntimeException('test violation cause');

        yield 'tag violation' => [
            'innerViolation' => new SecurityPolicyTagViolation(name: 'block', templateLine: 5, cause: $cause),
            'expectedClass' => EmailTemplateSecurityPolicyTagViolation::class,
            'expectedName' => 'block',
            'expectedTemplateLine' => 5,
            'expectedVariableName' => null,
            'expectedEntityClass' => null,
            'expectedCause' => $cause,
        ];

        yield 'filter violation' => [
            'innerViolation' => new SecurityPolicyFilterViolation(name: 'raw', templateLine: 2, cause: $cause),
            'expectedClass' => EmailTemplateSecurityPolicyFilterViolation::class,
            'expectedName' => 'raw',
            'expectedTemplateLine' => 2,
            'expectedVariableName' => null,
            'expectedEntityClass' => null,
            'expectedCause' => $cause,
        ];

        yield 'function violation' => [
            'innerViolation' => new SecurityPolicyFunctionViolation(name: 'constant', templateLine: 7, cause: $cause),
            'expectedClass' => EmailTemplateSecurityPolicyFunctionViolation::class,
            'expectedName' => 'constant',
            'expectedTemplateLine' => 7,
            'expectedVariableName' => null,
            'expectedEntityClass' => null,
            'expectedCause' => $cause,
        ];

        yield 'property violation' => [
            'innerViolation' => new SecurityPolicyPropertyViolation(
                name: 'secret',
                variableName: 'entity',
                entityClass: \stdClass::class,
                templateLine: 4,
                cause: $cause
            ),
            'expectedClass' => EmailTemplateSecurityPolicyPropertyViolation::class,
            'expectedName' => 'secret',
            'expectedTemplateLine' => 4,
            'expectedVariableName' => 'entity',
            'expectedEntityClass' => \stdClass::class,
            'expectedCause' => $cause,
        ];

        yield 'method violation' => [
            'innerViolation' => new SecurityPolicyMethodViolation(
                name: 'dangerousMethod',
                variableName: 'entity',
                entityClass: \stdClass::class,
                templateLine: 9,
                cause: $cause
            ),
            'expectedClass' => EmailTemplateSecurityPolicyMethodViolation::class,
            'expectedName' => 'dangerousMethod',
            'expectedTemplateLine' => 9,
            'expectedVariableName' => 'entity',
            'expectedEntityClass' => \stdClass::class,
            'expectedCause' => $cause,
        ];

        yield 'property violation with null variable and entity class' => [
            'innerViolation' => new SecurityPolicyPropertyViolation(
                name: 'attr',
                variableName: null,
                entityClass: null,
                templateLine: 12,
                cause: $cause
            ),
            'expectedClass' => EmailTemplateSecurityPolicyPropertyViolation::class,
            'expectedName' => 'attr',
            'expectedTemplateLine' => 12,
            'expectedVariableName' => null,
            'expectedEntityClass' => null,
            'expectedCause' => $cause,
        ];
    }

    public function testCheckSecurityPolicyThrowsOnUnexpectedViolationType(): void
    {
        $this->checker->setEmailTemplateFields(['content']);

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName(null);
        $emailTemplate->setContent('{{ foo }}');

        $unknownViolation = new UnknownSecurityPolicyViolationStub();

        $this->templateSecurityPolicyChecker->expects(self::once())
            ->method('checkSecurityPolicy')
            ->willReturn([$unknownViolation]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/Unexpected security policy violation type/');

        $this->checker->checkSecurityPolicy($emailTemplate);
    }

    public function testCheckSecurityPolicyCollectsViolationsFromAllDefaultFields(): void
    {
        $cause = new \RuntimeException();

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName(null);
        $emailTemplate->setSubject('Subject text');
        $emailTemplate->setContent('Content text');

        $subjectViolation = new SecurityPolicyTagViolation(name: 'block', templateLine: 1, cause: $cause);
        $contentViolation = new SecurityPolicyFilterViolation(name: 'raw', templateLine: 3, cause: $cause);

        $this->templateSecurityPolicyChecker->expects(self::exactly(2))
            ->method('checkSecurityPolicy')
            ->willReturnMap([
                ['Subject text', [], [$subjectViolation]],
                ['Content text', [], [$contentViolation]],
            ]);

        $result = $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertCount(2, $result);

        self::assertInstanceOf(EmailTemplateSecurityPolicyTagViolation::class, $result[0]);
        self::assertSame('subject', $result[0]->getTemplateField());
        self::assertSame('block', $result[0]->getName());

        self::assertInstanceOf(EmailTemplateSecurityPolicyFilterViolation::class, $result[1]);
        self::assertSame('content', $result[1]->getTemplateField());
        self::assertSame('raw', $result[1]->getName());
    }

    public function testCheckSecurityPolicyCollectsMultipleViolationsFromSameField(): void
    {
        $cause = new \RuntimeException();

        $this->checker->setEmailTemplateFields(['content']);

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName(null);
        $emailTemplate->setContent('{{ entity.secret }} {{ entity.forbidden }}');

        $propertyViolation1 = new SecurityPolicyPropertyViolation('secret', 'entity', \stdClass::class, 1, $cause);
        $propertyViolation2 = new SecurityPolicyPropertyViolation('forbidden', 'entity', \stdClass::class, 1, $cause);

        $this->templateSecurityPolicyChecker->expects(self::once())
            ->method('checkSecurityPolicy')
            ->willReturn([$propertyViolation1, $propertyViolation2]);

        $result = $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(EmailTemplateSecurityPolicyPropertyViolation::class, $result);
        self::assertSame('content', $result[0]->getTemplateField());
        self::assertSame('content', $result[1]->getTemplateField());
        self::assertSame('secret', $result[0]->getName());
        self::assertSame('forbidden', $result[1]->getName());
    }

    public function testSetEmailTemplateFieldsReplacesDefaultFields(): void
    {
        $cause = new \RuntimeException();

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName(null);
        $emailTemplate->setSubject('Subject');
        $emailTemplate->setContent('Content');

        $contentViolation = new SecurityPolicyTagViolation(name: 'block', templateLine: 1, cause: $cause);

        $this->templateSecurityPolicyChecker->expects(self::once())
            ->method('checkSecurityPolicy')
            ->with('Content', [])
            ->willReturn([$contentViolation]);

        $this->checker->setEmailTemplateFields(['content']);

        $result = $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertCount(1, $result);
        self::assertInstanceOf(EmailTemplateSecurityPolicyTagViolation::class, $result[0]);
        self::assertSame('content', $result[0]->getTemplateField());
    }

    public function testCheckSecurityPolicyThrowsWhenEmailTemplateHasNoGetterForConfiguredField(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName(null);

        $this->templateSecurityPolicyChecker->expects(self::never())
            ->method('checkSecurityPolicy');

        $this->checker->setEmailTemplateFields(['nonExistentField']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Email template does not have a getter for field "nonExistentField"/');
        $this->expectExceptionMessageMatches('/getNonExistentField\(\)/');

        $this->checker->checkSecurityPolicy($emailTemplate);
    }

    public function testCheckSecurityPolicyTreatsNullGetterResultAsEmptyString(): void
    {
        $this->checker->setEmailTemplateFields(['content']);

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName(null);
        $emailTemplate->setContent(null);

        $this->templateSecurityPolicyChecker->expects(self::once())
            ->method('checkSecurityPolicy')
            ->with('', [])
            ->willReturn([]);

        $result = $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertSame([], $result);
    }

    public function testCheckSecurityPolicyDispatchesBeforeEventWithCorrectEmailTemplate(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName(null);
        $emailTemplate->setSubject('Hi');
        $emailTemplate->setContent('Body');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(
                    static function (EmailTemplateSecurityPolicyCheckBefore $event) use ($emailTemplate): bool {
                        return $event->getEmailTemplate() === $emailTemplate;
                    }
                )
            );

        $this->templateSecurityPolicyChecker
            ->method('checkSecurityPolicy')
            ->willReturn([]);

        $this->checker->checkSecurityPolicy($emailTemplate);
    }

    public function testCheckSecurityPolicyPassesEntityClassAsInitialVariableTypeBeforeEventDispatch(): void
    {
        $entityClass = \ArrayObject::class;

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('my_template');
        $emailTemplate->setSubject('Hi');
        $emailTemplate->setContent('Body');

        $this->emailTemplateMetadataProvider->expects(self::once())
            ->method('getEmailTemplateMetadata')
            ->with($emailTemplate)
            ->willReturn([
                EmailTemplateMetadataProvider::ENTITY_NAME => $entityClass,
            ]);

        $capturedVariableTypes = null;
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (EmailTemplateSecurityPolicyCheckBefore $event) use (&$capturedVariableTypes): object {
                    $capturedVariableTypes = $event->getVariableTypes();

                    return $event;
                }
            );

        $this->templateSecurityPolicyChecker
            ->method('checkSecurityPolicy')
            ->willReturn([]);

        $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertSame(['entity' => $entityClass], $capturedVariableTypes);
    }

    public function testCheckSecurityPolicyResolvesEntityClassFromModelEntityNameWithoutMetadataProvider(): void
    {
        $entityClass = \ArrayObject::class;

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('my_template');
        $emailTemplate->setEntityName($entityClass);
        $emailTemplate->setSubject('Hi');
        $emailTemplate->setContent('Body');

        $this->emailTemplateMetadataProvider->expects(self::never())
            ->method('getEmailTemplateMetadata');

        $capturedVariableTypes = null;
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (EmailTemplateSecurityPolicyCheckBefore $event) use (&$capturedVariableTypes): object {
                    $capturedVariableTypes = $event->getVariableTypes();

                    return $event;
                }
            );

        $this->templateSecurityPolicyChecker
            ->method('checkSecurityPolicy')
            ->willReturn([]);

        $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertSame(['entity' => $entityClass], $capturedVariableTypes);
    }

    public function testCheckSecurityPolicyFallsBackToMetadataProviderWhenModelEntityNameIsNull(): void
    {
        $entityClass = \stdClass::class;

        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName('my_template');
        $emailTemplate->setEntityName(null);
        $emailTemplate->setSubject('Hi');
        $emailTemplate->setContent('Body');

        $this->emailTemplateMetadataProvider->expects(self::once())
            ->method('getEmailTemplateMetadata')
            ->with($emailTemplate)
            ->willReturn([
                EmailTemplateMetadataProvider::ENTITY_NAME => $entityClass,
            ]);

        $capturedVariableTypes = null;
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (EmailTemplateSecurityPolicyCheckBefore $event) use (&$capturedVariableTypes): object {
                    $capturedVariableTypes = $event->getVariableTypes();

                    return $event;
                }
            );

        $this->templateSecurityPolicyChecker
            ->method('checkSecurityPolicy')
            ->willReturn([]);

        $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertSame(['entity' => $entityClass], $capturedVariableTypes);
    }

    public function testCheckSecurityPolicyResolvesEntityClassFromEntityEntityNameWithoutMetadataProvider(): void
    {
        $entityClass = \ArrayObject::class;

        $emailTemplate = new EmailTemplateEntity();
        $emailTemplate->setEntityName($entityClass);
        $emailTemplate->setSubject('Hi');
        $emailTemplate->setContent('Body');

        $this->emailTemplateMetadataProvider->expects(self::never())
            ->method('getEmailTemplateMetadata');

        $capturedVariableTypes = null;
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (EmailTemplateSecurityPolicyCheckBefore $event) use (&$capturedVariableTypes): object {
                    $capturedVariableTypes = $event->getVariableTypes();

                    return $event;
                }
            );

        $this->templateSecurityPolicyChecker
            ->method('checkSecurityPolicy')
            ->willReturn([]);

        $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertSame(['entity' => $entityClass], $capturedVariableTypes);
    }

    public function testCheckSecurityPolicyFallsBackToMetadataProviderWhenEntityEntityNameIsNull(): void
    {
        $entityClass = \stdClass::class;

        $emailTemplate = new EmailTemplateEntity();
        $emailTemplate->setEntityName(null);
        $emailTemplate->setSubject('Hi');
        $emailTemplate->setContent('Body');

        $this->emailTemplateMetadataProvider->expects(self::once())
            ->method('getEmailTemplateMetadata')
            ->with($emailTemplate)
            ->willReturn([
                EmailTemplateMetadataProvider::ENTITY_NAME => $entityClass,
            ]);

        $capturedVariableTypes = null;
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (EmailTemplateSecurityPolicyCheckBefore $event) use (&$capturedVariableTypes): object {
                    $capturedVariableTypes = $event->getVariableTypes();

                    return $event;
                }
            );

        $this->templateSecurityPolicyChecker
            ->method('checkSecurityPolicy')
            ->willReturn([]);

        $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertSame(['entity' => $entityClass], $capturedVariableTypes);
    }

    public function testCheckSecurityPolicyChecksFieldsWithCorrectContents(): void
    {
        $emailTemplate = new EmailTemplate();
        $emailTemplate->setName(null);
        $emailTemplate->setSubject('subject_content_value');
        $emailTemplate->setContent('content_html_value');

        $this->templateSecurityPolicyChecker->expects(self::exactly(2))
            ->method('checkSecurityPolicy')
            ->withConsecutive(
                ['subject_content_value', []],
                ['content_html_value', []]
            )
            ->willReturn([]);

        $result = $this->checker->checkSecurityPolicy($emailTemplate);

        self::assertSame([], $result);
    }
}
