<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\SecurityPolicy;

use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessAnalyzer;
use Oro\Bundle\EntityBundle\Twig\Analyzer\TemplateAccessEntry;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\TemplateSecurityPolicyChecker;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyFilterViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyFunctionViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyMethodViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyPropertyViolation;
use Oro\Bundle\EntityBundle\Twig\SecurityPolicy\Violation\SecurityPolicyTagViolation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Extension\SandboxExtension;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Template;
use Twig\TemplateWrapper;

final class TemplateSecurityPolicyCheckerTest extends TestCase
{
    private Environment&MockObject $twigEnvironment;
    private TemplateAccessAnalyzer&MockObject $templateAccessAnalyzer;
    private TemplateSecurityPolicyChecker $checker;

    #[\Override]
    protected function setUp(): void
    {
        $this->twigEnvironment = $this->createMock(Environment::class);
        $this->templateAccessAnalyzer = $this->createMock(TemplateAccessAnalyzer::class);

        $this->checker = new TemplateSecurityPolicyChecker(
            $this->twigEnvironment,
            $this->templateAccessAnalyzer,
        );
    }

    public function testCheckSecurityPolicyReturnsEmptyArrayWhenSandboxExtensionNotRegistered(): void
    {
        $this->twigEnvironment
            ->expects(self::once())
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(false);

        $this->twigEnvironment
            ->expects(self::never())
            ->method('createTemplate');

        $this->templateAccessAnalyzer
            ->expects(self::never())
            ->method('analyzeTemplate');

        $result = $this->checker->checkSecurityPolicy('{{ foo }}', ['entity' => \stdClass::class]);

        self::assertSame([], $result);
    }

    public function testCheckSecurityPolicyReturnsEmptyArrayWhenNoViolationsAndVariableTypesEmpty(): void
    {
        $templateWrapper = new TemplateWrapper(
            $this->twigEnvironment,
            $this->createMock(Template::class),
        );

        $this->twigEnvironment
            ->expects(self::once())
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('createTemplate')
            ->with('{{ foo }}')
            ->willReturn($templateWrapper);

        $this->templateAccessAnalyzer
            ->expects(self::never())
            ->method('analyzeTemplate');

        $result = $this->checker->checkSecurityPolicy('{{ foo }}');

        self::assertSame([], $result);
    }

    public function testCheckSecurityPolicyReturnsEmptyArrayWhenVariableTypesProvidedButAnalyzerReturnsNoEntries(): void
    {
        $securityPolicy = $this->createMock(SecurityPolicyInterface::class);
        $sandboxExtension = new SandboxExtension($securityPolicy, true);
        $templateWrapper = new TemplateWrapper(
            $this->twigEnvironment,
            $this->createMock(Template::class),
        );

        $this->twigEnvironment
            ->expects(self::once())
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('createTemplate')
            ->with('{{ entity.name }}')
            ->willReturn($templateWrapper);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('getExtension')
            ->with(SandboxExtension::class)
            ->willReturn($sandboxExtension);

        $this->templateAccessAnalyzer
            ->expects(self::once())
            ->method('analyzeTemplate')
            ->with('{{ entity.name }}', ['entity' => \stdClass::class])
            ->willReturn([]);

        $securityPolicy
            ->expects(self::never())
            ->method('checkPropertyAllowed');

        $securityPolicy
            ->expects(self::never())
            ->method('checkMethodAllowed');

        $result = $this->checker->checkSecurityPolicy('{{ entity.name }}', ['entity' => \stdClass::class]);

        self::assertSame([], $result);
    }

    /**
     * @dataProvider sandboxViolationDataProvider
     */
    public function testCheckSecurityPolicyReturnsSandboxViolationWhenDisallowedElementUsed(
        \Throwable $exceptionToThrow,
        string $expectedViolationClass,
        string $expectedName,
        int $expectedTemplateLine,
    ): void {
        $this->twigEnvironment
            ->expects(self::once())
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('createTemplate')
            ->with('{{ foo }}')
            ->willThrowException($exceptionToThrow);

        $this->templateAccessAnalyzer
            ->expects(self::never())
            ->method('analyzeTemplate');

        $result = $this->checker->checkSecurityPolicy('{{ foo }}');

        self::assertCount(1, $result);
        self::assertInstanceOf($expectedViolationClass, $result[0]);
        self::assertSame($expectedName, $result[0]->getName());
        self::assertSame($expectedTemplateLine, $result[0]->getTemplateLine());
        self::assertSame($exceptionToThrow, $result[0]->getCause());
        self::assertNull($result[0]->getVariableName());
        self::assertNull($result[0]->getEntityClass());
    }

    public static function sandboxViolationDataProvider(): iterable
    {
        $tagError = new SecurityNotAllowedTagError('Tag "block" is not allowed.', 'block');
        yield 'disallowed tag produces tag violation' => [
            'exceptionToThrow' => $tagError,
            'expectedViolationClass' => SecurityPolicyTagViolation::class,
            'expectedName' => 'block',
            'expectedTemplateLine' => -1,
        ];

        $filterError = new SecurityNotAllowedFilterError('Filter "raw" is not allowed.', 'raw');
        yield 'disallowed filter produces filter violation' => [
            'exceptionToThrow' => $filterError,
            'expectedViolationClass' => SecurityPolicyFilterViolation::class,
            'expectedName' => 'raw',
            'expectedTemplateLine' => -1,
        ];

        $functionError = new SecurityNotAllowedFunctionError('Function "dump" is not allowed.', 'dump');
        yield 'disallowed function produces function violation' => [
            'exceptionToThrow' => $functionError,
            'expectedViolationClass' => SecurityPolicyFunctionViolation::class,
            'expectedName' => 'dump',
            'expectedTemplateLine' => -1,
        ];
    }

    public function testCheckSecurityPolicyPropagatesSyntaxErrorWhenTemplateSourceIsInvalid(): void
    {
        $syntaxError = new SyntaxError('Unexpected token.', 3);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('createTemplate')
            ->willThrowException($syntaxError);

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessageMatches('/Unexpected token/');

        $this->checker->checkSecurityPolicy('{% invalid syntax %}');
    }

    public function testCheckSecurityPolicyReturnsPropertyViolationWhenPropertyAccessIsDisallowed(): void
    {
        $securityPolicy = $this->createMock(SecurityPolicyInterface::class);
        $sandboxExtension = new SandboxExtension($securityPolicy, true);
        $templateWrapper = new TemplateWrapper(
            $this->twigEnvironment,
            $this->createMock(Template::class),
        );
        $propertyError = new SecurityNotAllowedPropertyError(
            sprintf('Property "%s::secret" is not allowed.', \stdClass::class),
            \stdClass::class,
            'secret',
        );
        $accessEntry = new TemplateAccessEntry(
            \stdClass::class,
            'entity',
            'secret',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            5,
        );

        $this->twigEnvironment
            ->expects(self::once())
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('createTemplate')
            ->with('{{ entity.secret }}')
            ->willReturn($templateWrapper);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('getExtension')
            ->with(SandboxExtension::class)
            ->willReturn($sandboxExtension);

        $this->templateAccessAnalyzer
            ->expects(self::once())
            ->method('analyzeTemplate')
            ->with('{{ entity.secret }}', ['entity' => \stdClass::class])
            ->willReturn([$accessEntry]);

        $securityPolicy
            ->expects(self::once())
            ->method('checkPropertyAllowed')
            ->willThrowException($propertyError);

        $result = $this->checker->checkSecurityPolicy('{{ entity.secret }}', ['entity' => \stdClass::class]);

        self::assertCount(1, $result);
        self::assertInstanceOf(SecurityPolicyPropertyViolation::class, $result[0]);
        self::assertSame('secret', $result[0]->getName());
        self::assertSame('entity', $result[0]->getVariableName());
        self::assertSame(\stdClass::class, $result[0]->getEntityClass());
        self::assertSame(5, $result[0]->getTemplateLine());
        self::assertSame($propertyError, $result[0]->getCause());
    }

    public function testCheckSecurityPolicyReturnsMethodViolationWhenMethodCallIsDisallowed(): void
    {
        $securityPolicy = $this->createMock(SecurityPolicyInterface::class);
        $sandboxExtension = new SandboxExtension($securityPolicy, true);
        $templateWrapper = new TemplateWrapper(
            $this->twigEnvironment,
            $this->createMock(Template::class),
        );
        $methodError = new SecurityNotAllowedMethodError(
            sprintf('Method "%s::execute" is not allowed.', \stdClass::class),
            \stdClass::class,
            'execute',
        );
        $accessEntry = new TemplateAccessEntry(
            \stdClass::class,
            'service',
            'execute',
            TemplateAccessEntry::ACCESS_TYPE_METHOD,
            12,
        );

        $this->twigEnvironment
            ->expects(self::once())
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('createTemplate')
            ->with('{{ service.execute() }}')
            ->willReturn($templateWrapper);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('getExtension')
            ->with(SandboxExtension::class)
            ->willReturn($sandboxExtension);

        $this->templateAccessAnalyzer
            ->expects(self::once())
            ->method('analyzeTemplate')
            ->with('{{ service.execute() }}', ['service' => \stdClass::class])
            ->willReturn([$accessEntry]);

        $securityPolicy
            ->expects(self::once())
            ->method('checkMethodAllowed')
            ->willThrowException($methodError);

        $result = $this->checker->checkSecurityPolicy('{{ service.execute() }}', ['service' => \stdClass::class]);

        self::assertCount(1, $result);
        self::assertInstanceOf(SecurityPolicyMethodViolation::class, $result[0]);
        self::assertSame('execute', $result[0]->getName());
        self::assertSame('service', $result[0]->getVariableName());
        self::assertSame(\stdClass::class, $result[0]->getEntityClass());
        self::assertSame(12, $result[0]->getTemplateLine());
        self::assertSame($methodError, $result[0]->getCause());
    }

    public function testCheckSecurityPolicyDoesNotReturnViolationWhenPropertyAccessIsAllowed(): void
    {
        $securityPolicy = $this->createMock(SecurityPolicyInterface::class);
        $sandboxExtension = new SandboxExtension($securityPolicy, true);
        $templateWrapper = new TemplateWrapper(
            $this->twigEnvironment,
            $this->createMock(Template::class),
        );
        $accessEntry = new TemplateAccessEntry(
            \stdClass::class,
            'entity',
            'name',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            3,
        );

        $this->twigEnvironment
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->method('createTemplate')
            ->willReturn($templateWrapper);

        $this->twigEnvironment
            ->method('getExtension')
            ->with(SandboxExtension::class)
            ->willReturn($sandboxExtension);

        $this->templateAccessAnalyzer
            ->method('analyzeTemplate')
            ->willReturn([$accessEntry]);

        $securityPolicy
            ->expects(self::once())
            ->method('checkPropertyAllowed');
        // does not throw - property access is allowed

        $result = $this->checker->checkSecurityPolicy('{{ entity.name }}', ['entity' => \stdClass::class]);

        self::assertSame([], $result);
    }

    public function testCheckSecurityPolicyDoesNotReturnViolationWhenMethodCallIsAllowed(): void
    {
        $securityPolicy = $this->createMock(SecurityPolicyInterface::class);
        $sandboxExtension = new SandboxExtension($securityPolicy, true);
        $templateWrapper = new TemplateWrapper(
            $this->twigEnvironment,
            $this->createMock(Template::class),
        );
        $accessEntry = new TemplateAccessEntry(
            \stdClass::class,
            'entity',
            'getName',
            TemplateAccessEntry::ACCESS_TYPE_METHOD,
            7,
        );

        $this->twigEnvironment
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->method('createTemplate')
            ->willReturn($templateWrapper);

        $this->twigEnvironment
            ->method('getExtension')
            ->with(SandboxExtension::class)
            ->willReturn($sandboxExtension);

        $this->templateAccessAnalyzer
            ->method('analyzeTemplate')
            ->willReturn([$accessEntry]);

        $securityPolicy
            ->expects(self::once())
            ->method('checkMethodAllowed');
        // does not throw - method call is allowed

        $result = $this->checker->checkSecurityPolicy('{{ entity.getName() }}', ['entity' => \stdClass::class]);

        self::assertSame([], $result);
    }

    public function testCheckSecurityPolicySkipsAccessEntryWithUnknownAccessType(): void
    {
        $securityPolicy = $this->createMock(SecurityPolicyInterface::class);
        $sandboxExtension = new SandboxExtension($securityPolicy, true);
        $templateWrapper = new TemplateWrapper(
            $this->twigEnvironment,
            $this->createMock(Template::class),
        );
        $accessEntry = new TemplateAccessEntry(
            \stdClass::class,
            'entity',
            'attribute',
            'unknown_access_type',
            2,
        );

        $this->twigEnvironment
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->method('createTemplate')
            ->willReturn($templateWrapper);

        $this->twigEnvironment
            ->method('getExtension')
            ->with(SandboxExtension::class)
            ->willReturn($sandboxExtension);

        $this->templateAccessAnalyzer
            ->method('analyzeTemplate')
            ->willReturn([$accessEntry]);

        $securityPolicy
            ->expects(self::never())
            ->method('checkPropertyAllowed');

        $securityPolicy
            ->expects(self::never())
            ->method('checkMethodAllowed');

        $result = $this->checker->checkSecurityPolicy('{{ entity.attribute }}', ['entity' => \stdClass::class]);

        self::assertSame([], $result);
    }

    public function testCheckSecurityPolicyReturnsMultipleViolationsForMultipleDisallowedAccesses(): void
    {
        $securityPolicy = $this->createMock(SecurityPolicyInterface::class);
        $sandboxExtension = new SandboxExtension($securityPolicy, true);
        $templateWrapper = new TemplateWrapper(
            $this->twigEnvironment,
            $this->createMock(Template::class),
        );
        $propertyError1 = new SecurityNotAllowedPropertyError(
            'Property "stdClass::secret" is not allowed.',
            \stdClass::class,
            'secret',
        );
        $propertyError2 = new SecurityNotAllowedPropertyError(
            'Property "stdClass::password" is not allowed.',
            \stdClass::class,
            'password',
        );
        $accessEntry1 = new TemplateAccessEntry(
            \stdClass::class,
            'entity',
            'secret',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            1,
        );
        $accessEntry2 = new TemplateAccessEntry(
            \stdClass::class,
            'entity',
            'password',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            2,
        );

        $this->twigEnvironment
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->method('createTemplate')
            ->willReturn($templateWrapper);

        $this->twigEnvironment
            ->method('getExtension')
            ->with(SandboxExtension::class)
            ->willReturn($sandboxExtension);

        $this->templateAccessAnalyzer
            ->method('analyzeTemplate')
            ->willReturn([$accessEntry1, $accessEntry2]);

        $errors = [$propertyError1, $propertyError2];
        $callIndex = 0;
        $securityPolicy
            ->expects(self::exactly(2))
            ->method('checkPropertyAllowed')
            ->willReturnCallback(static function () use (&$callIndex, $errors): void {
                throw $errors[$callIndex++];
            });

        $result = $this->checker->checkSecurityPolicy(
            '{{ entity.secret }} {{ entity.password }}',
            ['entity' => \stdClass::class],
        );

        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(SecurityPolicyPropertyViolation::class, $result);
        self::assertSame('secret', $result[0]->getName());
        self::assertSame(1, $result[0]->getTemplateLine());
        self::assertSame($propertyError1, $result[0]->getCause());
        self::assertSame('password', $result[1]->getName());
        self::assertSame(2, $result[1]->getTemplateLine());
        self::assertSame($propertyError2, $result[1]->getCause());
    }

    public function testCheckSecurityPolicyReturnsBothSandboxAndAccessViolationsWhenBothArePresent(): void
    {
        $securityPolicy = $this->createMock(SecurityPolicyInterface::class);
        $sandboxExtension = new SandboxExtension($securityPolicy, true);
        $tagError = new SecurityNotAllowedTagError('Tag "block" is not allowed.', 'block');
        $propertyError = new SecurityNotAllowedPropertyError(
            'Property "stdClass::secret" is not allowed.',
            \stdClass::class,
            'secret',
        );
        $accessEntry = new TemplateAccessEntry(
            \stdClass::class,
            'entity',
            'secret',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            3,
        );

        $this->twigEnvironment
            ->expects(self::once())
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('createTemplate')
            ->willThrowException($tagError);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('getExtension')
            ->with(SandboxExtension::class)
            ->willReturn($sandboxExtension);

        $this->templateAccessAnalyzer
            ->expects(self::once())
            ->method('analyzeTemplate')
            ->willReturn([$accessEntry]);

        $securityPolicy
            ->expects(self::once())
            ->method('checkPropertyAllowed')
            ->willThrowException($propertyError);

        $result = $this->checker->checkSecurityPolicy(
            '{% block header %}{% endblock %}{{ entity.secret }}',
            ['entity' => \stdClass::class],
        );

        self::assertCount(2, $result);
        self::assertInstanceOf(SecurityPolicyTagViolation::class, $result[0]);
        self::assertSame('block', $result[0]->getName());
        self::assertSame($tagError, $result[0]->getCause());
        self::assertInstanceOf(SecurityPolicyPropertyViolation::class, $result[1]);
        self::assertSame('secret', $result[1]->getName());
        self::assertSame(3, $result[1]->getTemplateLine());
        self::assertSame($propertyError, $result[1]->getCause());
    }

    public function testCheckSecurityPolicyOnlySandboxViolationReturnedWhenVariableTypesAreEmpty(): void
    {
        $tagError = new SecurityNotAllowedTagError('Tag "block" is not allowed.', 'block');

        $this->twigEnvironment
            ->expects(self::once())
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->expects(self::once())
            ->method('createTemplate')
            ->willThrowException($tagError);

        $this->templateAccessAnalyzer
            ->expects(self::never())
            ->method('analyzeTemplate');

        $result = $this->checker->checkSecurityPolicy('{% block header %}{% endblock %}');

        self::assertCount(1, $result);
        self::assertInstanceOf(SecurityPolicyTagViolation::class, $result[0]);
        self::assertSame('block', $result[0]->getName());
    }

    public function testCheckSecurityPolicyChecksPropertyWhenAccessTypeIsPropertyAndMethodWhenAccessTypeIsMethod(): void
    {
        $securityPolicy = $this->createMock(SecurityPolicyInterface::class);
        $sandboxExtension = new SandboxExtension($securityPolicy, true);
        $templateWrapper = new TemplateWrapper(
            $this->twigEnvironment,
            $this->createMock(Template::class),
        );
        $propertyEntry = new TemplateAccessEntry(
            \stdClass::class,
            'entity',
            'name',
            TemplateAccessEntry::ACCESS_TYPE_PROPERTY,
            1,
        );
        $methodEntry = new TemplateAccessEntry(
            \stdClass::class,
            'entity',
            'getId',
            TemplateAccessEntry::ACCESS_TYPE_METHOD,
            2,
        );

        $this->twigEnvironment
            ->method('hasExtension')
            ->with(SandboxExtension::class)
            ->willReturn(true);

        $this->twigEnvironment
            ->method('createTemplate')
            ->willReturn($templateWrapper);

        $this->twigEnvironment
            ->method('getExtension')
            ->with(SandboxExtension::class)
            ->willReturn($sandboxExtension);

        $this->templateAccessAnalyzer
            ->method('analyzeTemplate')
            ->willReturn([$propertyEntry, $methodEntry]);

        $securityPolicy
            ->expects(self::once())
            ->method('checkPropertyAllowed');
        // does not throw - property allowed

        $securityPolicy
            ->expects(self::once())
            ->method('checkMethodAllowed');
        // does not throw - method allowed

        $result = $this->checker->checkSecurityPolicy(
            '{{ entity.name }} {{ entity.getId() }}',
            ['entity' => \stdClass::class],
        );

        self::assertSame([], $result);
    }
}
