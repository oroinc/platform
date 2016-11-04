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

    public function testSwitchToDefaultFormExtension()
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
        $this->setPrivatePropertyValue($formRegistry, 'types', null);
        $this->setPrivatePropertyValue($formRegistry, 'guesser', null);
        $this->assertAttributeEquals(null, 'types', $formRegistry);
        $this->assertAttributeEquals(null, 'guesser', $formRegistry);

        $extension->expects(self::once())
            ->method('switchFormExtension')
            ->with(SwitchableFormRegistry::DEFAULT_EXTENSION);
        $formExtensionState->expects(self::once())
            ->method('switchToDefaultFormExtension');

        $formRegistry->switchToDefaultFormExtension();
        self::assertAttributeEquals([], 'types', $formRegistry);
        self::assertAttributeEquals(false, 'guesser', $formRegistry);
    }

    public function testSwitchToApiFormExtension()
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
        $this->setPrivatePropertyValue($formRegistry, 'types', null);
        $this->setPrivatePropertyValue($formRegistry, 'guesser', null);
        $this->assertAttributeEquals(null, 'types', $formRegistry);
        $this->assertAttributeEquals(null, 'guesser', $formRegistry);

        $extension->expects(self::once())
            ->method('switchFormExtension')
            ->with(SwitchableFormRegistry::API_EXTENSION);
        $formExtensionState->expects(self::once())
            ->method('switchToApiFormExtension');

        $formRegistry->switchToApiFormExtension();
        self::assertAttributeEquals([], 'types', $formRegistry);
        self::assertAttributeEquals(false, 'guesser', $formRegistry);
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
