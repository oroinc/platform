<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form;

use Oro\Bundle\ApiBundle\Form\FormExtension;
use Oro\Bundle\ApiBundle\Form\Type\BooleanType;
use Oro\Bundle\ApiBundle\Tests\Unit\Stub\AbstractFormTypeExtensionStub;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\FormTypeGuesserChain;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\FormTypeInterface;

class FormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    private function getExtension(array $types, array $typeExtensions, array $guessers): FormExtension
    {
        return new FormExtension($this->container, $types, $typeExtensions, $guessers);
    }

    private function getFormTypeExtension(string $extendedType): AbstractFormTypeExtensionStub
    {
        return AbstractFormTypeExtensionStub::createUniqueInstance($extendedType);
    }

    public function testGetType(): void
    {
        $extension = $this->getExtension(
            ['test' => 'test_type_service', BooleanType::class => null],
            [],
            []
        );

        $testType = $this->createMock(FormTypeInterface::class);

        $services = [
            'test_type_service' => $testType
        ];

        $this->container->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($id) use ($services) {
                if (isset($services[$id])) {
                    return $services[$id];
                }
                throw new ServiceNotFoundException($id);
            });

        self::assertTrue($extension->hasType('test'));
        self::assertTrue($extension->hasType(BooleanType::class));
        self::assertFalse($extension->hasType('unknown'));

        self::assertSame($testType, $extension->getType('test'));
        self::assertInstanceOf(BooleanType::class, $extension->getType(BooleanType::class));
    }

    public function testGetTypeShouldThrowExceptionForUnknownType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The form type "Oro\Bundle\ApiBundle\Form\Type\BooleanType" is not registered.');

        $extension = $this->getExtension([], [], []);

        $extension->getType(BooleanType::class);
    }

    public function testGetTypeShouldThrowExceptionForUnknownTypeClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not load form type "Test\UnknownClass": class does not exist.');

        $extension = $this->getExtension(['Test\UnknownClass' => null], [], []);

        $extension->getType('Test\UnknownClass');
    }

    public function testGetTypeShouldThrowExceptionForInvalidTypeClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Could not load form type "stdClass": class does not implement "Symfony\Component\Form\FormTypeInterface".'
        );

        $extension = $this->getExtension([\stdClass::class => null], [], []);

        $extension->getType(\stdClass::class);
    }

    public function testGetTypeExtensions(): void
    {
        $extension = $this->getExtension(
            [],
            ['test' => ['extension1', 'extension2'], 'other' => ['extension3']],
            []
        );

        $typeExtension1 = $this->getFormTypeExtension('test');
        $typeExtension2 = $this->getFormTypeExtension('test');
        $typeExtension3 = $this->getFormTypeExtension('other');

        $services = [
            'extension1' => $typeExtension1,
            'extension2' => $typeExtension2,
            'extension3' => $typeExtension3
        ];

        $this->container->expects(self::any())
            ->method('get')
            ->willReturnCallback(function ($id) use ($services) {
                if (isset($services[$id])) {
                    return $services[$id];
                }
                throw new ServiceNotFoundException($id);
            });

        self::assertTrue($extension->hasTypeExtensions('test'));
        self::assertFalse($extension->hasTypeExtensions('unknown'));

        self::assertSame([$typeExtension1, $typeExtension2], $extension->getTypeExtensions('test'));
    }

    public function testGetTypeExtensionsShouldThrowExceptionForInvalidExtendedType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The extended type specified for the service "extension" does not match the actual extended type.'
            . ' Expected "test", given "unmatched".'
        );

        $extension = $this->getExtension([], ['test' => ['extension']], []);
        $formTypeExtension = $this->getFormTypeExtension('unmatched');

        $this->container->expects(self::any())
            ->method('get')
            ->with('extension')
            ->willReturn($formTypeExtension);

        $extensions = $extension->getTypeExtensions('test');

        self::assertCount(1, $extensions);
        self::assertSame($formTypeExtension, $extensions[0]);
    }

    public function testGetTypeGuesser(): void
    {
        $extension = $this->getExtension([], [], ['foo']);

        $this->container->expects(self::once())
            ->method('get')
            ->with('foo')
            ->willReturn($this->createMock(FormTypeGuesserInterface::class));

        self::assertInstanceOf(FormTypeGuesserChain::class, $extension->getTypeGuesser());
    }

    public function testGetTypeGuesserReturnsNullWhenNoTypeGuessersHaveBeenConfigured(): void
    {
        $extension = $this->getExtension([], [], []);

        self::assertNull($extension->getTypeGuesser());
    }
}
