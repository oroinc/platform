<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityExtendBundle\Form\EntityExtendFormExtension;
use Oro\Bundle\EntityExtendBundle\Form\Extension\DynamicFieldsOptionsExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;

class EntityExtendFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityExtendFormExtension */
    private $formExtension;

    /** @var FormExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerFormExtension;

    /** @var FormTypeExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $typeExtension;

    protected function setUp(): void
    {
        $this->innerFormExtension = $this->createMock(FormExtensionInterface::class);
        $this->typeExtension =  $this->createMock(FormTypeExtensionInterface::class);

        $this->formExtension = new EntityExtendFormExtension($this->typeExtension, $this->innerFormExtension);
    }

    public function testGetType(): void
    {
        $expectedType = new ChoiceType();
        $this->innerFormExtension
            ->expects($this->once())
            ->method('getType')
            ->willReturn($expectedType);

        $this->assertSame($expectedType, $this->formExtension->getType('acme'));
    }

    public function testHasType(): void
    {
        $this->innerFormExtension
            ->expects($this->once())
            ->method('hasType')
            ->willReturn(true);

        $this->assertTrue($this->formExtension->hasType('acme'));
    }

    public function testHasTypeExtensions(): void
    {
        $this->innerFormExtension
            ->expects($this->once())
            ->method('hasTypeExtensions')
            ->willReturn(true);

        $this->assertTrue($this->formExtension->hasTypeExtensions('acme'));
    }

    public function testGetTypeGuesser(): void
    {
        $guesser = $this->createMock(FormTypeGuesserInterface::class);
        $this->innerFormExtension
            ->expects($this->once())
            ->method('getTypeGuesser')
            ->willReturn($guesser);

        $this->assertSame($guesser, $this->formExtension->getTypeGuesser());
    }

    public function testGetTypeExtensions(): void
    {
        $this->innerFormExtension
            ->expects($this->exactly(2))
            ->method('getTypeExtensions')
            ->willReturn([DynamicFieldsOptionsExtension::class]);

        $this->assertEquals(
            [DynamicFieldsOptionsExtension::class, $this->typeExtension],
            $this->formExtension->getTypeExtensions(ChoiceType::class)
        );

        $this->assertEquals(
            [DynamicFieldsOptionsExtension::class],
            $this->formExtension->getTypeExtensions(FormType::class)
        );
    }
}
