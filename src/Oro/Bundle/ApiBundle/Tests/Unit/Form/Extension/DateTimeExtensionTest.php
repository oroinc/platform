<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ApiBundle\Form\DataTransformer\DateTimeToLocalizedStringTransformer as Wrapper;
use Oro\Bundle\ApiBundle\Form\DataTransformer\NullValueTransformer;
use Oro\Bundle\ApiBundle\Form\Extension\DateTimeExtension;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\DateTimeToLocalizedStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;

class DateTimeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var DateTimeExtension */
    private $extension;

    protected function setUp()
    {
        $this->extension = new DateTimeExtension();
    }

    public function testGetExtendedType()
    {
        self::assertEquals(DateTimeType::class, $this->extension->getExtendedType());
    }

    public function testBuildFormShouldWrapDateTimeTransformerIfItIsWrappedWithNullValueTransformer()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $rfc3339Transformer = $this->createMock(DateTimeToLocalizedStringTransformer::class);

        $viewTransformers = [new NullValueTransformer($rfc3339Transformer)];

        $builder->expects(self::once())
            ->method('getViewTransformers')
            ->willReturn($viewTransformers);

        $this->extension->buildForm($builder, []);

        self::assertInstanceOf(NullValueTransformer::class, $viewTransformers[0]);
        self::assertNotSame($rfc3339Transformer, $viewTransformers[0]->getInnerTransformer());
        self::assertInstanceOf(Wrapper::class, $viewTransformers[0]->getInnerTransformer());
        self::assertAttributeSame(
            $rfc3339Transformer,
            'innerTransformer',
            $viewTransformers[0]->getInnerTransformer()
        );
    }

    public function testBuildFormShouldNotWrapDateTimeTransformerIfItIsNotWrappedWithNullValueTransformer()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $rfc3339Transformer = $this->createMock(DateTimeToLocalizedStringTransformer::class);

        $viewTransformers = [$rfc3339Transformer];

        $builder->expects(self::once())
            ->method('getViewTransformers')
            ->willReturn($viewTransformers);

        $this->extension->buildForm($builder, []);

        self::assertSame($rfc3339Transformer, $viewTransformers[0]);
    }

    public function testBuildFormShouldWrapOnlyDateTimeToLocalizedStringTransformer()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $anotherTransformer = $this->createMock(DataTransformerInterface::class);

        $viewTransformers = [new NullValueTransformer($anotherTransformer)];

        $builder->expects(self::once())
            ->method('getViewTransformers')
            ->willReturn($viewTransformers);

        $this->extension->buildForm($builder, []);

        self::assertInstanceOf(NullValueTransformer::class, $viewTransformers[0]);
        self::assertSame($anotherTransformer, $viewTransformers[0]->getInnerTransformer());
    }
}
