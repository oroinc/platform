<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\SecurityBundle\Form\Extension\AclFormExtension;
use Oro\Bundle\SecurityBundle\Form\Extension\AclProtectedFieldTypeExtension;
use Oro\Bundle\SecurityBundle\Form\Extension\AclProtectedTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormTypeGuesserInterface;

class AclFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var AclProtectedFieldTypeExtension|\PHPUnit\Framework\MockObject\MockObject */
    private $aclFieldExtension;

    /** @var FormExtensionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerExtension;

    /** @var AclFormExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->aclFieldExtension = $this->createMock(AclProtectedFieldTypeExtension::class);
        $this->innerExtension = $this->createMock(FormExtensionInterface::class);

        $this->extension = new AclFormExtension($this->aclFieldExtension, $this->innerExtension);
    }

    public function testGetType(): void
    {
        $expectedType = new ChoiceType();
        $this->innerExtension->expects($this->once())
            ->method('getType')
            ->with('test')
            ->willReturn($expectedType);

        $this->assertSame($expectedType, $this->extension->getType('test'));
    }

    public function testHasType(): void
    {
        $this->innerExtension->expects($this->once())
            ->method('hasType')
            ->with('test')
            ->willReturn(true);

        $this->assertTrue($this->extension->hasType('test'));
    }

    public function testHasTypeExtensions(): void
    {
        $this->innerExtension->expects($this->once())
            ->method('hasTypeExtensions')
            ->with('test')
            ->willReturn(true);

        $this->assertTrue($this->extension->hasTypeExtensions('test'));
    }

    public function testGetTypeGuesser(): void
    {
        $guesser = $this->createMock(FormTypeGuesserInterface::class);
        $this->innerExtension->expects($this->once())
            ->method('getTypeGuesser')
            ->willReturn($guesser);

        $this->assertSame($guesser, $this->extension->getTypeGuesser());
    }

    public function testGetTypeExtensions(): void
    {
        $this->innerExtension->expects($this->exactly(2))
            ->method('getTypeExtensions')
            ->willReturn([AclProtectedTypeExtension::class]);

        $this->assertEquals(
            [$this->aclFieldExtension, AclProtectedTypeExtension::class],
            $this->extension->getTypeExtensions(ChoiceType::class)
        );

        $this->assertEquals(
            [AclProtectedTypeExtension::class],
            $this->extension->getTypeExtensions(FormType::class)
        );
    }
}
