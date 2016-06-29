<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form;

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
            $this->getMock('Symfony\Component\Form\FormExtensionInterface'),
            $this->getMock('Symfony\Component\Form\FormExtensionInterface'),
        ];

        new SwitchableFormRegistry(
            $extensions,
            $this->getMock('Symfony\Component\Form\ResolvedFormTypeFactoryInterface')
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
            $this->getMock('Symfony\Component\Form\FormExtensionInterface'),
        ];

        new SwitchableFormRegistry(
            $extensions,
            $this->getMock('Symfony\Component\Form\ResolvedFormTypeFactoryInterface')
        );
    }

    public function testSwitchToDefaultFormExtension()
    {
        $extension = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension')
            ->disableOriginalConstructor()
            ->getMock();

        $formRegistry = new SwitchableFormRegistry(
            [$extension],
            $this->getMock('Symfony\Component\Form\ResolvedFormTypeFactoryInterface')
        );
        $this->setPrivatePropertyValue($formRegistry, 'types', null);
        $this->setPrivatePropertyValue($formRegistry, 'guesser', null);
        $this->assertAttributeEquals(null, 'types', $formRegistry);
        $this->assertAttributeEquals(null, 'guesser', $formRegistry);

        $extension->expects($this->once())
            ->method('switchFormExtension')
            ->with(SwitchableFormRegistry::DEFAULT_EXTENSION);

        $formRegistry->switchToDefaultFormExtension();
        $this->assertAttributeEquals([], 'types', $formRegistry);
        $this->assertAttributeEquals(false, 'guesser', $formRegistry);
    }

    public function testSwitchToApiFormExtension()
    {
        $extension = $this
            ->getMockBuilder('Oro\Bundle\ApiBundle\Form\Extension\SwitchableDependencyInjectionExtension')
            ->disableOriginalConstructor()
            ->getMock();

        $formRegistry = new SwitchableFormRegistry(
            [$extension],
            $this->getMock('Symfony\Component\Form\ResolvedFormTypeFactoryInterface')
        );
        $this->setPrivatePropertyValue($formRegistry, 'types', null);
        $this->setPrivatePropertyValue($formRegistry, 'guesser', null);
        $this->assertAttributeEquals(null, 'types', $formRegistry);
        $this->assertAttributeEquals(null, 'guesser', $formRegistry);

        $extension->expects($this->once())
            ->method('switchFormExtension')
            ->with(SwitchableFormRegistry::API_EXTENSION);

        $formRegistry->switchToApiFormExtension();
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
        $r = new \ReflectionClass('Symfony\Component\Form\FormRegistry');
        if (!$r->hasProperty($propertyName)) {
            throw new \RuntimeException(sprintf('The "%s" property does not exist.', $propertyName));
        }
        $p = $r->getProperty($propertyName);
        $p->setAccessible(true);
        $p->setValue($formRegistry, $value);
    }
}
