<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\PhoneType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;

class PhoneTypeTest extends TestCase
{
    private PhoneType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new PhoneType();
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(3))
            ->method('add')
            ->withConsecutive(
                ['id', HiddenType::class],
                ['phone', TextType::class],
                ['primary', RadioType::class]
            )
            ->willReturnSelf();

        $this->type->buildForm($builder, []);
    }
}
