<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig;

use Oro\Bundle\EmailBundle\Tests\Unit\Stub\SecurityPolicyWithExtraMethodStub;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateEntityAccessChecker;
use Oro\Bundle\EmailBundle\Twig\EmailTemplateSecurityPolicy;
use Oro\Bundle\EntityBundle\Twig\Sandbox\TemplateRendererConfigProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityPolicy;

final class EmailTemplateSecurityPolicyTest extends TestCase
{
    private TemplateRendererConfigProviderInterface&MockObject $configProvider;
    private EmailTemplateEntityAccessChecker&MockObject $entityAccessChecker;
    private EmailTemplateSecurityPolicy $securityPolicy;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(TemplateRendererConfigProviderInterface::class);
        $this->entityAccessChecker = $this->createMock(EmailTemplateEntityAccessChecker::class);
        $this->securityPolicy = new EmailTemplateSecurityPolicy(
            new SecurityPolicy(),
            $this->configProvider,
            $this->entityAccessChecker
        );
    }

    public function testGetTagsReturnsEmptyArrayByDefault(): void
    {
        self::assertSame([], $this->securityPolicy->getTags());
    }

    public function testGetFunctionsReturnsEmptyArrayByDefault(): void
    {
        self::assertSame([], $this->securityPolicy->getFunctions());
    }

    public function testGetFiltersReturnsEmptyArrayByDefault(): void
    {
        self::assertSame([], $this->securityPolicy->getFilters());
    }

    public function testSetAllowedTagsStoresTagsAndDelegatesToInnerSecurityPolicy(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedTags(['if', 'for', 'apply']);

        self::assertSame(['if', 'for', 'apply'], $this->securityPolicy->getTags());

        $this->securityPolicy->checkSecurity(['if'], [], []);
    }

    public function testSetAllowedTagsDelegatesToInnerSecurityPolicyRejectsTagNotInAllowedList(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedTags(['if', 'for']);

        $this->expectException(SecurityNotAllowedTagError::class);

        $this->securityPolicy->checkSecurity(['disallowed_tag'], [], []);
    }

    public function testSetAllowedTagsOverridesPreviouslyStoredTags(): void
    {
        $this->securityPolicy->setAllowedTags(['if']);
        $this->securityPolicy->setAllowedTags(['for', 'set']);

        self::assertSame(['for', 'set'], $this->securityPolicy->getTags());
    }

    public function testSetAllowedFunctionsStoresFunctionsAndDelegatesToInnerSecurityPolicy(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedFunctions(['date', '_entity_var']);

        self::assertSame(['date', '_entity_var'], $this->securityPolicy->getFunctions());

        $this->securityPolicy->checkSecurity([], [], ['date']);
    }

    public function testSetAllowedFunctionsDelegatesToInnerSecurityPolicyRejectsFunctionNotInAllowedList(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedFunctions(['date']);

        $this->expectException(SecurityNotAllowedFunctionError::class);

        $this->securityPolicy->checkSecurity([], [], ['disallowed_function']);
    }

    public function testSetAllowedFiltersStoresFiltersAndDelegatesToInnerSecurityPolicy(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedFilters(['upper', 'lower', 'trim']);

        self::assertSame(['upper', 'lower', 'trim'], $this->securityPolicy->getFilters());

        $this->securityPolicy->checkSecurity([], ['upper'], []);
    }

    public function testSetAllowedFiltersDelegatesToInnerSecurityPolicyRejectsFilterNotInAllowedList(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedFilters(['upper']);

        $this->expectException(SecurityNotAllowedFilterError::class);

        $this->securityPolicy->checkSecurity([], ['disallowed_filter'], []);
    }

    public function testSetAllowedMethodsNormalizesArrayMethodNamesToLowercase(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedMethods(['SomeEntity' => ['GetName', 'GetId']]);

        self::assertSame(['SomeEntity' => ['getname', 'getid']], $this->securityPolicy->getMethods());
    }

    public function testSetAllowedMethodsNormalizesStringMethodNameToLowercaseArray(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedMethods(['SomeEntity' => 'GetName']);

        self::assertSame(['SomeEntity' => ['getname']], $this->securityPolicy->getMethods());
    }

    public function testSetAllowedPropertiesStoresProperties(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedProperties(['SomeEntity' => ['name', 'email']]);

        self::assertSame(['SomeEntity' => ['name', 'email']], $this->securityPolicy->getProperties());
    }

    public function testGetMethodsTriggersInitializationOnFirstCallAndReturnsNormalizedMethods(): void
    {
        $this->configProvider
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => ['ConfigEntity' => ['configMethod']],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedMethods(['SomeEntity' => ['GetName']]);

        self::assertSame(['SomeEntity' => ['getname']], $this->securityPolicy->getMethods());
    }

    public function testGetMethodsDoesNotReinitializeOnSubsequentCalls(): void
    {
        $this->configProvider
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->getMethods();
        $this->securityPolicy->getMethods();
        $this->securityPolicy->getMethods();
    }

    public function testGetPropertiesTriggersInitializationOnFirstCallAndReturnsStoredProperties(): void
    {
        $this->configProvider
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => ['ConfigEntity' => ['configProp']],
            ]);

        $this->securityPolicy->setAllowedProperties(['SomeEntity' => ['name', 'email']]);

        self::assertSame(['SomeEntity' => ['name', 'email']], $this->securityPolicy->getProperties());
    }

    public function testGetPropertiesDoesNotReinitializeOnSubsequentCalls(): void
    {
        $this->configProvider
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->getProperties();
        $this->securityPolicy->getProperties();
    }

    public function testCheckSecurityPassesForAllConfiguredAllowedTagsFiltersAndFunctions(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedTags(['if', 'for']);
        $this->securityPolicy->setAllowedFilters(['upper', 'lower']);
        $this->securityPolicy->setAllowedFunctions(['date', '_entity_var']);

        $this->securityPolicy->checkSecurity(['if', 'for'], ['upper', 'lower'], ['date', '_entity_var']);
    }

    public function testCheckSecurityThrowsForTagNotInAllowedList(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedTags(['if']);

        $this->expectException(SecurityNotAllowedTagError::class);
        $this->securityPolicy->checkSecurity(['disallowed_tag'], [], []);
    }

    public function testCheckSecurityThrowsForFilterNotInAllowedList(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedFilters(['upper']);

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->securityPolicy->checkSecurity([], ['disallowed_filter'], []);
    }

    public function testCheckSecurityThrowsForFunctionNotInAllowedList(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->setAllowedFunctions(['date']);

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $this->securityPolicy->checkSecurity([], [], ['disallowed_function']);
    }

    public function testCheckSecurityDoesNotReinitializeOnSubsequentCalls(): void
    {
        $this->configProvider
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->checkSecurity([], [], []);
        $this->securityPolicy->checkSecurity([], [], []);
    }

    /**
     * @dataProvider toStringMethodNameVariantsProvider
     */
    public function testCheckMethodAllowedSkipsPolicyCheckForToStringMagicMethodVariants(string $method): void
    {
        // The __toString check occurs before ensureInitialized(), so no config is needed
        $this->configProvider
            ->expects(self::never())
            ->method('getConfiguration');

        $object = new \stdClass();

        // __toString is exempt from the allowlist, but not from the entity access check
        $this->entityAccessChecker
            ->expects(self::once())
            ->method('assertMethodAccessGranted')
            ->with($object, $method);

        // Must not throw regardless of object type
        $this->securityPolicy->checkMethodAllowed($object, $method);
    }

    public static function toStringMethodNameVariantsProvider(): iterable
    {
        yield 'standard PHP convention' => ['__toString'];
        yield 'all lowercase' => ['__tostring'];
        yield 'all uppercase' => ['__TOSTRING'];
        yield 'mixed case' => ['__ToString'];
    }

    public function testCheckMethodAllowedPassesForMethodAllowedViaConfiguration(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [\stdClass::class => ['getname']],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->checkMethodAllowed(new \stdClass(), 'getName');
    }

    public function testCheckMethodAllowedThrowsForMethodNotAllowedViaConfiguration(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [\stdClass::class => ['getname']],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->expectException(SecurityNotAllowedMethodError::class);

        $this->securityPolicy->checkMethodAllowed(new \stdClass(), 'getForbiddenMethod');
    }

    public function testCheckMethodAllowedDoesNotReinitializeOnSubsequentCalls(): void
    {
        $this->configProvider
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [\stdClass::class => ['getname']],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->securityPolicy->checkMethodAllowed(new \stdClass(), 'getName');
        $this->securityPolicy->checkMethodAllowed(new \stdClass(), 'getName');
    }

    public function testCheckPropertyAllowedPassesForPropertyAllowedViaConfiguration(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [\stdClass::class => ['name']],
            ]);

        $this->securityPolicy->checkPropertyAllowed(new \stdClass(), 'name');
    }

    public function testCheckPropertyAllowedThrowsForPropertyNotAllowedViaConfiguration(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [\stdClass::class => ['name']],
            ]);

        $this->expectException(SecurityNotAllowedPropertyError::class);

        $this->securityPolicy->checkPropertyAllowed(new \stdClass(), 'forbiddenProperty');
    }

    public function testCheckPropertyAllowedDoesNotReinitializeOnSubsequentCalls(): void
    {
        $this->configProvider
            ->expects(self::once())
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [\stdClass::class => ['name']],
            ]);

        $this->securityPolicy->checkPropertyAllowed(new \stdClass(), 'name');
        $this->securityPolicy->checkPropertyAllowed(new \stdClass(), 'name');
    }

    public function testCheckMethodAllowedThrowsWhenEntityAccessCheckerDeniesToStringMagicMethod(): void
    {
        $object = new \stdClass();
        $error = new SecurityNotAllowedMethodError('Access denied.', \stdClass::class, '__toString');

        $this->entityAccessChecker
            ->expects(self::once())
            ->method('assertMethodAccessGranted')
            ->with($object, '__toString')
            ->willThrowException($error);

        $this->expectExceptionObject($error);

        $this->securityPolicy->checkMethodAllowed($object, '__toString');
    }

    public function testCheckMethodAllowedThrowsWhenEntityAccessCheckerDeniesAllowedMethod(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [\stdClass::class => ['getname']],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $object = new \stdClass();
        $error = new SecurityNotAllowedMethodError('Access denied.', \stdClass::class, 'getName');

        $this->entityAccessChecker
            ->expects(self::once())
            ->method('assertMethodAccessGranted')
            ->with($object, 'getName')
            ->willThrowException($error);

        $this->expectExceptionObject($error);

        $this->securityPolicy->checkMethodAllowed($object, 'getName');
    }

    public function testCheckMethodAllowedDoesNotCheckEntityAccessForMethodNotAllowedViaConfiguration(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [\stdClass::class => ['getname']],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        // An attribute that is not allowlisted must be reported as an allowlist miss, not as an access denial
        $this->entityAccessChecker
            ->expects(self::never())
            ->method('assertMethodAccessGranted');

        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->expectExceptionMessage(
            sprintf('Calling "getforbiddenmethod" method on a "%s" object is not allowed.', \stdClass::class)
        );

        $this->securityPolicy->checkMethodAllowed(new \stdClass(), 'getForbiddenMethod');
    }

    public function testCheckPropertyAllowedThrowsWhenEntityAccessCheckerDeniesAllowedProperty(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [\stdClass::class => ['name']],
            ]);

        $object = new \stdClass();
        $error = new SecurityNotAllowedPropertyError('Access denied.', \stdClass::class, 'name');

        $this->entityAccessChecker
            ->expects(self::once())
            ->method('assertPropertyAccessGranted')
            ->with($object, 'name')
            ->willThrowException($error);

        $this->expectExceptionObject($error);

        $this->securityPolicy->checkPropertyAllowed($object, 'name');
    }

    public function testCheckPropertyAllowedDoesNotCheckEntityAccessForPropertyNotAllowedViaConfiguration(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [\stdClass::class => ['name']],
            ]);

        // An attribute that is not allowlisted must be reported as an allowlist miss, not as an access denial
        $this->entityAccessChecker
            ->expects(self::never())
            ->method('assertPropertyAccessGranted');

        $this->expectException(SecurityNotAllowedPropertyError::class);
        $this->expectExceptionMessage(
            sprintf('Calling "forbiddenProperty" property on a "%s" object is not allowed.', \stdClass::class)
        );

        $this->securityPolicy->checkPropertyAllowed(new \stdClass(), 'forbiddenProperty');
    }

    public function testCallDelegatesToInnerSecurityPolicy(): void
    {
        $stub = new SecurityPolicyWithExtraMethodStub();
        $configProvider = $this->createMock(TemplateRendererConfigProviderInterface::class);
        $securityPolicy = new EmailTemplateSecurityPolicy($stub, $configProvider, $this->entityAccessChecker);

        // Calling a method not defined on EmailTemplateSecurityPolicy is forwarded via __call
        $result = $securityPolicy->extraMethod('test_argument');

        self::assertSame('stub_result_test_argument', $result);
    }
}
