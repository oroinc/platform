<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber;
use Oro\Bundle\TagBundle\Form\Type\TagApiType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagApiTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var TagApiType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new TagApiType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->configureOptions($resolver);
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(2))
            ->method('add')
            ->willReturnSelf();

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf(PatchSubscriber::class));

        $this->type->buildForm($builder, []);
    }
}
