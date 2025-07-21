<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\NotificationBundle\Form\Type\RecipientListType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipientListTypeTest extends TestCase
{
    private RecipientListType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new RecipientListType();
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(3))
            ->method('add');

        $this->type->buildForm($builder, []);
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->configureOptions($resolver);
    }
}
