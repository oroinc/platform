<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig;

use Oro\Bundle\EmailBundle\Tests\Unit\Stub\SecurityPolicyWithExtraMethodStub;
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
    private EmailTemplateSecurityPolicy $sut;

    #[\Override]
    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(TemplateRendererConfigProviderInterface::class);
        $this->sut = new EmailTemplateSecurityPolicy(
            new SecurityPolicy(),
            $this->configProvider
        );
    }

    public function testGetTagsReturnsEmptyArrayByDefault(): void
    {
        self::assertSame([], $this->sut->getTags());
    }

    public function testGetFunctionsReturnsEmptyArrayByDefault(): void
    {
        self::assertSame([], $this->sut->getFunctions());
    }

    public function testGetFiltersReturnsEmptyArrayByDefault(): void
    {
        self::assertSame([], $this->sut->getFilters());
    }

    public function testSetAllowedTagsStoresTagsAndDelegatesToInnerSecurityPolicy(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedTags(['if', 'for', 'apply']);

        self::assertSame(['if', 'for', 'apply'], $this->sut->getTags());

        $this->sut->checkSecurity(['if'], [], []);
    }

    public function testSetAllowedTagsDelegatesToInnerSecurityPolicyRejectsTagNotInAllowedList(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedTags(['if', 'for']);

        $this->expectException(SecurityNotAllowedTagError::class);

        $this->sut->checkSecurity(['disallowed_tag'], [], []);
    }

    public function testSetAllowedTagsOverridesPreviouslyStoredTags(): void
    {
        $this->sut->setAllowedTags(['if']);
        $this->sut->setAllowedTags(['for', 'set']);

        self::assertSame(['for', 'set'], $this->sut->getTags());
    }

    public function testSetAllowedFunctionsStoresFunctionsAndDelegatesToInnerSecurityPolicy(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedFunctions(['date', '_entity_var']);

        self::assertSame(['date', '_entity_var'], $this->sut->getFunctions());

        $this->sut->checkSecurity([], [], ['date']);
    }

    public function testSetAllowedFunctionsDelegatesToInnerSecurityPolicyRejectsFunctionNotInAllowedList(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedFunctions(['date']);

        $this->expectException(SecurityNotAllowedFunctionError::class);

        $this->sut->checkSecurity([], [], ['disallowed_function']);
    }

    public function testSetAllowedFiltersStoresFiltersAndDelegatesToInnerSecurityPolicy(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedFilters(['upper', 'lower', 'trim']);

        self::assertSame(['upper', 'lower', 'trim'], $this->sut->getFilters());

        $this->sut->checkSecurity([], ['upper'], []);
    }

    public function testSetAllowedFiltersDelegatesToInnerSecurityPolicyRejectsFilterNotInAllowedList(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedFilters(['upper']);

        $this->expectException(SecurityNotAllowedFilterError::class);

        $this->sut->checkSecurity([], ['disallowed_filter'], []);
    }

    public function testSetAllowedMethodsNormalizesArrayMethodNamesToLowercase(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedMethods(['SomeEntity' => ['GetName', 'GetId']]);

        self::assertSame(['SomeEntity' => ['getname', 'getid']], $this->sut->getMethods());
    }

    public function testSetAllowedMethodsNormalizesStringMethodNameToLowercaseArray(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedMethods(['SomeEntity' => 'GetName']);

        self::assertSame(['SomeEntity' => ['getname']], $this->sut->getMethods());
    }

    public function testSetAllowedPropertiesStoresProperties(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedProperties(['SomeEntity' => ['name', 'email']]);

        self::assertSame(['SomeEntity' => ['name', 'email']], $this->sut->getProperties());
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

        $this->sut->setAllowedMethods(['SomeEntity' => ['GetName']]);

        self::assertSame(['SomeEntity' => ['getname']], $this->sut->getMethods());
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

        $this->sut->getMethods();
        $this->sut->getMethods();
        $this->sut->getMethods();
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

        $this->sut->setAllowedProperties(['SomeEntity' => ['name', 'email']]);

        self::assertSame(['SomeEntity' => ['name', 'email']], $this->sut->getProperties());
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

        $this->sut->getProperties();
        $this->sut->getProperties();
    }

    public function testCheckSecurityPassesForAllConfiguredAllowedTagsFiltersAndFunctions(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedTags(['if', 'for']);
        $this->sut->setAllowedFilters(['upper', 'lower']);
        $this->sut->setAllowedFunctions(['date', '_entity_var']);

        $this->sut->checkSecurity(['if', 'for'], ['upper', 'lower'], ['date', '_entity_var']);
    }

    public function testCheckSecurityThrowsForTagNotInAllowedList(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedTags(['if']);

        $this->expectException(SecurityNotAllowedTagError::class);
        $this->sut->checkSecurity(['disallowed_tag'], [], []);
    }

    public function testCheckSecurityThrowsForFilterNotInAllowedList(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedFilters(['upper']);

        $this->expectException(SecurityNotAllowedFilterError::class);
        $this->sut->checkSecurity([], ['disallowed_filter'], []);
    }

    public function testCheckSecurityThrowsForFunctionNotInAllowedList(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [],
            ]);

        $this->sut->setAllowedFunctions(['date']);

        $this->expectException(SecurityNotAllowedFunctionError::class);
        $this->sut->checkSecurity([], [], ['disallowed_function']);
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

        $this->sut->checkSecurity([], [], []);
        $this->sut->checkSecurity([], [], []);
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

        // Must not throw regardless of object type
        $this->sut->checkMethodAllowed(new \stdClass(), $method);
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

        $this->sut->checkMethodAllowed(new \stdClass(), 'getName');
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

        $this->sut->checkMethodAllowed(new \stdClass(), 'getForbiddenMethod');
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

        $this->sut->checkMethodAllowed(new \stdClass(), 'getName');
        $this->sut->checkMethodAllowed(new \stdClass(), 'getName');
    }

    public function testCheckPropertyAllowedPassesForPropertyAllowedViaConfiguration(): void
    {
        $this->configProvider
            ->method('getConfiguration')
            ->willReturn([
                TemplateRendererConfigProviderInterface::METHODS => [],
                TemplateRendererConfigProviderInterface::PROPERTIES => [\stdClass::class => ['name']],
            ]);

        $this->sut->checkPropertyAllowed(new \stdClass(), 'name');
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

        $this->sut->checkPropertyAllowed(new \stdClass(), 'forbiddenProperty');
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

        $this->sut->checkPropertyAllowed(new \stdClass(), 'name');
        $this->sut->checkPropertyAllowed(new \stdClass(), 'name');
    }

    public function testCallDelegatesToInnerSecurityPolicy(): void
    {
        $stub = new SecurityPolicyWithExtraMethodStub();
        $configProvider = $this->createMock(TemplateRendererConfigProviderInterface::class);
        $sut = new EmailTemplateSecurityPolicy($stub, $configProvider);

        // Calling a method not defined on EmailTemplateSecurityPolicy is forwarded via __call
        $result = $sut->extraMethod('test_argument');

        self::assertSame('stub_result_test_argument', $result);
    }
}
