<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form;

use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\ResolvedFormTypeFactoryInterface;

use Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension;
use Oro\Bundle\ApiBundle\Form\FormExtensionState;
use Oro\Bundle\ApiBundle\Form\SwitchableFormRegistry;

class SwitchableFormRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected only one form extension.
     */
    public function testConstructorWithSeveralFormExtensions()
    {
        $extensions = [
            $this->getMock(FormExtensionInterface::class),
            $this->getMock(FormExtensionInterface::class),
        ];

        new SwitchableFormRegistry(
            $extensions,
            $this->getMock(ResolvedFormTypeFactoryInterface::class),
            $this->getMock(FormExtensionState::class)
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected type of form extension is "Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension"
     */
    // @codingStandardsIgnoreEnd
    public function testConstructorWithUnexpectedFormExtensions()
    {
        $extensions = [
            $this->getMock(FormExtensionInterface::class),
        ];

        new SwitchableFormRegistry(
            $extensions,
            $this->getMock(ResolvedFormTypeFactoryInterface::class),
            $this->getMock(FormExtensionState::class)
        );
    }

    public function testShouldBePossibleToSetTypesAndGuesser()
    {
        $extension = $this->getMockBuilder(SwitchableDependencyInjectionExtension::class)
            ->disableOriginalConstructor()
            ->getMock();

        $formRegistry = new SwitchableFormRegistry(
            [$extension],
            $this->getMock(ResolvedFormTypeFactoryInterface::class),
            $this->getMock(FormExtensionState::class)
        );

        $this->setPrivatePropertyValue($formRegistry, 'types', null);
        $this->setPrivatePropertyValue($formRegistry, 'guesser', null);
        $this->assertAttributeEquals(null, 'types', $formRegistry);
        $this->assertAttributeEquals(null, 'guesser', $formRegistry);
    }

    public function testSwitchToDefaultFormExtensionWhenThisExtensionIsAlreadyActive()
    {
        $extension = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension')
            ->disableOriginalConstructor()
            ->getMock();
        $formExtensionState = $this->getMock(FormExtensionState::class);

        $formRegistry = new SwitchableFormRegistry(
            [$extension],
            $this->getMock(ResolvedFormTypeFactoryInterface::class),
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
        $extension = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension')
            ->disableOriginalConstructor()
            ->getMock();
        $formExtensionState = $this->getMock(FormExtensionState::class);

        $formRegistry = new SwitchableFormRegistry(
            [$extension],
            $this->getMock(ResolvedFormTypeFactoryInterface::class),
            $formExtensionState
        );

        $extension->expects(self::at(0))
            ->method('switchFormExtension')
            ->with(SwitchableFormRegistry::API_EXTENSION);
        $formExtensionState->expects(self::at(0))
            ->method('switchToApiFormExtension');
        $extension->expects(self::at(1))
            ->method('switchFormExtension')
            ->with(SwitchableFormRegistry::DEFAULT_EXTENSION);
        $formExtensionState->expects(self::at(1))
            ->method('switchToDefaultFormExtension');

        // should switch to api form extension
        $this->setPrivatePropertyValue($formRegistry, 'types', null);
        $this->setPrivatePropertyValue($formRegistry, 'guesser', null);
        $formRegistry->switchToApiFormExtension();
        $this->assertAttributeEquals([], 'types', $formRegistry);
        $this->assertAttributeEquals(false, 'guesser', $formRegistry);

        // should switch to default form extension
        $this->setPrivatePropertyValue($formRegistry, 'types', null);
        $this->setPrivatePropertyValue($formRegistry, 'guesser', null);
        $formRegistry->switchToDefaultFormExtension();
        self::assertAttributeEquals([], 'types', $formRegistry);
        self::assertAttributeEquals(false, 'guesser', $formRegistry);
    }

    public function testSeveralSwitchToApiAndThenToDefaultFormExtension()
    {
        $extension = $this->getMockBuilder(SwitchableDependencyInjectionExtension::class)
            ->disableOriginalConstructor()
            ->getMock();
        $formExtensionState = $this->getMock(FormExtensionState::class);

        $formRegistry = new SwitchableFormRegistry(
            [$extension],
            $this->getMock(ResolvedFormTypeFactoryInterface::class),
            $formExtensionState
        );

        $extension->expects(self::at(0))
            ->method('switchFormExtension')
            ->with(SwitchableFormRegistry::API_EXTENSION);
        $formExtensionState->expects(self::at(0))
            ->method('switchToApiFormExtension');
        $extension->expects(self::at(1))
            ->method('switchFormExtension')
            ->with(SwitchableFormRegistry::DEFAULT_EXTENSION);
        $formExtensionState->expects(self::at(1))
            ->method('switchToDefaultFormExtension');

        // the first "ToApi" switch should switch to api form extension
        $this->setPrivatePropertyValue($formRegistry, 'types', null);
        $this->setPrivatePropertyValue($formRegistry, 'guesser', null);
        $formRegistry->switchToApiFormExtension();
        $this->assertAttributeEquals([], 'types', $formRegistry);
        $this->assertAttributeEquals(false, 'guesser', $formRegistry);

        // the second "ToApi" switch should do nothing
        $this->setPrivatePropertyValue($formRegistry, 'types', null);
        $this->setPrivatePropertyValue($formRegistry, 'guesser', null);
        $formRegistry->switchToApiFormExtension();
        $this->assertAttributeEquals(null, 'types', $formRegistry);
        $this->assertAttributeEquals(null, 'guesser', $formRegistry);

        // the first "ToDefault" switch should do nothing
        $formRegistry->switchToDefaultFormExtension();
        $this->assertAttributeEquals(null, 'types', $formRegistry);
        $this->assertAttributeEquals(null, 'guesser', $formRegistry);

        // the second "ToDefault" switch should switch to default form extension
        $formRegistry->switchToDefaultFormExtension();
        $this->assertAttributeEquals([], 'types', $formRegistry);
        $this->assertAttributeEquals(false, 'guesser', $formRegistry);
    }

    /**
     * @param SwitchableFormRegistry $formRegistry
     * @param string                 $propertyName
     * @param mixed                  $value
     */
    protected function setPrivatePropertyValue(SwitchableFormRegistry $formRegistry, $propertyName, $value)
    {
        $r = new \ReflectionClass(FormRegistry::class);
        if (!$r->hasProperty($propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" property does not exist.', $propertyName));
        }
        $p = $r->getProperty($propertyName);
        $p->setAccessible(true);
        $p->setValue($formRegistry, $value);
    }
}
