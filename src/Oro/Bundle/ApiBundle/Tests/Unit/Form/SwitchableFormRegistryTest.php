<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form;

use Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension;
use Oro\Bundle\ApiBundle\Form\FormExtensionState;
use Oro\Bundle\ApiBundle\Form\SwitchableFormRegistry;
use Oro\Bundle\ApiBundle\Form\Type\BooleanType;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\ResolvedFormTypeFactoryInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

class SwitchableFormRegistryTest extends \PHPUnit\Framework\TestCase
{
    private function expectSwitchFormExtension(
        array &$switchCalls,
        SwitchableDependencyInjectionExtension|\PHPUnit\Framework\MockObject\MockObject $extension,
        FormExtensionState|\PHPUnit\Framework\MockObject\MockObject $formExtensionState
    ): void {
        $switchCalls = [];
        $extension->expects(self::exactly(2))
            ->method('switchFormExtension')
            ->willReturnCallback(function ($extensionName) use (&$switchCalls) {
                $switchCalls[] = 'extension::switchFormExtension - ' . $extensionName;
            });
        $formExtensionState->expects(self::once())
            ->method('switchToApiFormExtension')
            ->willReturnCallback(function () use (&$switchCalls) {
                $switchCalls[] = 'formExtensionState::switchToApiFormExtension';
            });
        $formExtensionState->expects(self::once())
            ->method('switchToDefaultFormExtension')
            ->willReturnCallback(function () use (&$switchCalls) {
                $switchCalls[] = 'formExtensionState::switchToDefaultFormExtension';
            });
    }

    public function testConstructorWithSeveralFormExtensions()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected only one form extension.');

        $extensions = [
            $this->createMock(FormExtensionInterface::class),
            $this->createMock(FormExtensionInterface::class)
        ];

        new SwitchableFormRegistry(
            $extensions,
            $this->createMock(ResolvedFormTypeFactoryInterface::class),
            $this->createMock(FormExtensionState::class)
        );
    }

    public function testConstructorWithUnexpectedFormExtensions()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Expected type of form extension is "%s"',
            SwitchableDependencyInjectionExtension::class
        ));

        $extensions = [
            $this->createMock(FormExtensionInterface::class)
        ];

        new SwitchableFormRegistry(
            $extensions,
            $this->createMock(ResolvedFormTypeFactoryInterface::class),
            $this->createMock(FormExtensionState::class)
        );
    }

    public function testShouldBePossibleToSetTypesAndGuesser()
    {
        $extension = $this->createMock(SwitchableDependencyInjectionExtension::class);

        $formRegistry = new SwitchableFormRegistry(
            [$extension],
            $this->createMock(ResolvedFormTypeFactoryInterface::class),
            $this->createMock(FormExtensionState::class)
        );

        ReflectionUtil::setPropertyValue($formRegistry, 'types', null);
        ReflectionUtil::setPropertyValue($formRegistry, 'guesser', null);
        self::assertNull(ReflectionUtil::getPropertyValue($formRegistry, 'types'));
        self::assertNull(ReflectionUtil::getPropertyValue($formRegistry, 'guesser'));
    }

    public function testSwitchToDefaultFormExtensionWhenThisExtensionIsAlreadyActive()
    {
        $extension = $this->createMock(SwitchableDependencyInjectionExtension::class);
        $formExtensionState = $this->createMock(FormExtensionState::class);

        $formRegistry = new SwitchableFormRegistry(
            [$extension],
            $this->createMock(ResolvedFormTypeFactoryInterface::class),
            $formExtensionState
        );

        $extension->expects(self::never())
            ->method('switchFormExtension')
            ->with(SwitchableFormRegistry::DEFAULT_EXTENSION);
        $formExtensionState->expects(self::never())
            ->method('switchToDefaultFormExtension');

        // this switch should do nothing
        $formRegistry->switchToDefaultFormExtension();
        // the additional switch should do nothing as well
        $formRegistry->switchToDefaultFormExtension();
    }

    public function testSwitchToApiAndThenToDefaultFormExtension()
    {
        $extension = $this->createMock(SwitchableDependencyInjectionExtension::class);
        $formExtensionState = $this->createMock(FormExtensionState::class);

        $formRegistry = new SwitchableFormRegistry(
            [$extension],
            $this->createMock(ResolvedFormTypeFactoryInterface::class),
            $formExtensionState
        );

        $switchCalls = [];
        $this->expectSwitchFormExtension($switchCalls, $extension, $formExtensionState);

        // should switch to api form extension
        ReflectionUtil::setPropertyValue($formRegistry, 'types', null);
        ReflectionUtil::setPropertyValue($formRegistry, 'guesser', null);
        $formRegistry->switchToApiFormExtension();
        self::assertEquals([], ReflectionUtil::getPropertyValue($formRegistry, 'types'));
        self::assertFalse(ReflectionUtil::getPropertyValue($formRegistry, 'guesser'));

        // should switch to default form extension
        ReflectionUtil::setPropertyValue($formRegistry, 'types', null);
        ReflectionUtil::setPropertyValue($formRegistry, 'guesser', null);
        $formRegistry->switchToDefaultFormExtension();
        self::assertEquals([], ReflectionUtil::getPropertyValue($formRegistry, 'types'));
        self::assertFalse(ReflectionUtil::getPropertyValue($formRegistry, 'guesser'));

        self::assertEquals(
            [
                'extension::switchFormExtension - ' . SwitchableFormRegistry::API_EXTENSION,
                'formExtensionState::switchToApiFormExtension',
                'extension::switchFormExtension - ' . SwitchableFormRegistry::DEFAULT_EXTENSION,
                'formExtensionState::switchToDefaultFormExtension'
            ],
            $switchCalls
        );
    }

    public function testSeveralSwitchToApiAndThenToDefaultFormExtension()
    {
        $extension = $this->createMock(SwitchableDependencyInjectionExtension::class);
        $formExtensionState = $this->createMock(FormExtensionState::class);

        $formRegistry = new SwitchableFormRegistry(
            [$extension],
            $this->createMock(ResolvedFormTypeFactoryInterface::class),
            $formExtensionState
        );

        $switchCalls = [];
        $this->expectSwitchFormExtension($switchCalls, $extension, $formExtensionState);

        // the first "ToApi" switch should switch to api form extension
        ReflectionUtil::setPropertyValue($formRegistry, 'types', null);
        ReflectionUtil::setPropertyValue($formRegistry, 'guesser', null);
        $formRegistry->switchToApiFormExtension();
        self::assertEquals([], ReflectionUtil::getPropertyValue($formRegistry, 'types'));
        self::assertFalse(ReflectionUtil::getPropertyValue($formRegistry, 'guesser'));

        // the second "ToApi" switch should do nothing
        ReflectionUtil::setPropertyValue($formRegistry, 'types', null);
        ReflectionUtil::setPropertyValue($formRegistry, 'guesser', null);
        $formRegistry->switchToApiFormExtension();
        self::assertNull(ReflectionUtil::getPropertyValue($formRegistry, 'types'));
        self::assertNull(ReflectionUtil::getPropertyValue($formRegistry, 'guesser'));

        // the first "ToDefault" switch should do nothing
        $formRegistry->switchToDefaultFormExtension();
        self::assertNull(ReflectionUtil::getPropertyValue($formRegistry, 'types'));
        self::assertNull(ReflectionUtil::getPropertyValue($formRegistry, 'guesser'));

        // the second "ToDefault" switch should switch to default form extension
        $formRegistry->switchToDefaultFormExtension();
        self::assertEquals([], ReflectionUtil::getPropertyValue($formRegistry, 'types'));
        self::assertFalse(ReflectionUtil::getPropertyValue($formRegistry, 'guesser'));

        self::assertEquals(
            [
                'extension::switchFormExtension - ' . SwitchableFormRegistry::API_EXTENSION,
                'formExtensionState::switchToApiFormExtension',
                'extension::switchFormExtension - ' . SwitchableFormRegistry::DEFAULT_EXTENSION,
                'formExtensionState::switchToDefaultFormExtension'
            ],
            $switchCalls
        );
    }

    public function testGetTypeShouldReturnKnownApiFormType()
    {
        $extension = $this->createMock(SwitchableDependencyInjectionExtension::class);
        $resolvedTypeFactory = $this->createMock(ResolvedFormTypeFactoryInterface::class);
        $formRegistry = new SwitchableFormRegistry([$extension], $resolvedTypeFactory, new FormExtensionState());

        $formRegistry->switchToApiFormExtension();

        $type = $this->createMock(BooleanType::class);
        $resolvedType = $this->createMock(ResolvedFormTypeInterface::class);

        $extension->expects(self::any())
            ->method('hasType')
            ->with(BooleanType::class)
            ->willReturn(true);
        $extension->expects(self::once())
            ->method('getType')
            ->with(BooleanType::class)
            ->willReturn($type);
        $resolvedTypeFactory->expects(self::once())
            ->method('createResolvedType')
            ->with(self::identicalTo($type))
            ->willReturn($resolvedType);
        $extension->expects(self::any())
            ->method('getTypeExtensions')
            ->willReturn([]);

        self::assertSame(
            $resolvedType,
            $formRegistry->getType(BooleanType::class)
        );
    }

    public function testGetTypeShouldThrowExceptionForNotKnownApiFormType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The form type "Oro\Bundle\ApiBundle\Form\Type\BooleanType" is not configured to be used in API.'
        );

        $extension = $this->createMock(SwitchableDependencyInjectionExtension::class);
        $resolvedTypeFactory = $this->createMock(ResolvedFormTypeFactoryInterface::class);
        $formRegistry = new SwitchableFormRegistry([$extension], $resolvedTypeFactory, new FormExtensionState());

        $formRegistry->switchToApiFormExtension();

        $extension->expects(self::any())
            ->method('hasType')
            ->with(BooleanType::class)
            ->willReturn(false);
        $extension->expects(self::never())
            ->method('getType');

        $formRegistry->getType(BooleanType::class);
    }

    public function testGetTypeShouldReturnAnyDefaultFormType()
    {
        $extension = $this->createMock(SwitchableDependencyInjectionExtension::class);
        $resolvedTypeFactory = $this->createMock(ResolvedFormTypeFactoryInterface::class);
        $formRegistry = new SwitchableFormRegistry([$extension], $resolvedTypeFactory, new FormExtensionState());

        $resolvedType = $this->createMock(ResolvedFormTypeInterface::class);
        $parentType = $this->createMock(FormType::class);
        $parentResolvedType = $this->createMock(ResolvedFormTypeInterface::class);

        $extension->expects(self::any())
            ->method('hasType')
            ->willReturnMap([
                [BooleanType::class, false],
                [FormType::class, true]
            ]);
        $extension->expects(self::once())
            ->method('getType')
            ->with(FormType::class)
            ->willReturn($parentType);
        $resolvedTypeFactory->expects(self::exactly(2))
            ->method('createResolvedType')
            ->withConsecutive([self::identicalTo($parentType)], [self::isInstanceOf(BooleanType::class)])
            ->willReturnOnConsecutiveCalls($parentResolvedType, $resolvedType);
        $extension->expects(self::any())
            ->method('getTypeExtensions')
            ->willReturn([]);

        self::assertSame(
            $resolvedType,
            $formRegistry->getType(BooleanType::class)
        );
    }
}
