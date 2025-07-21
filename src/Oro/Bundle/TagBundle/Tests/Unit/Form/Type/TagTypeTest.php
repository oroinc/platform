<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\Type;

use Oro\Bundle\TagBundle\Form\Type\TagType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagTypeTest extends TestCase
{
    private TagType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new TagType();
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->configureOptions($resolver);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(2))
            ->method('add')
            ->willReturnSelf();

        $this->type->buildForm($builder, []);
    }
}
