<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Form;

use Oro\Bundle\ApiBundle\Form\ApiFormBuilder;
use Oro\Bundle\ApiBundle\Form\ApiResolvedFormType;
use Oro\Bundle\ApiBundle\Form\ApiResolvedFormTypeFactory;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ApiResolvedFormTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ResolvedFormTypeInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerType;

    /** @var ApiResolvedFormTypeFactory */
    private $type;

    protected function setUp(): void
    {
        $this->innerType = $this->createMock(ResolvedFormTypeInterface::class);

        $this->type = new ApiResolvedFormType($this->innerType);
    }

    public function testGetBlockPrefix(): void
    {
        $blockPrefix = 'test';

        $this->innerType->expects(self::once())
            ->method('getBlockPrefix')
            ->willReturn($blockPrefix);

        self::assertSame($blockPrefix, $this->type->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        $parent = $this->createMock(ResolvedFormTypeInterface::class);

        $this->innerType->expects(self::once())
            ->method('getParent')
            ->willReturn($parent);

        self::assertSame($parent, $this->type->getParent());
    }

    public function testGetInnerType(): void
    {
        $innerType = $this->createMock(FormTypeInterface::class);

        $this->innerType->expects(self::once())
            ->method('getInnerType')
            ->willReturn($innerType);

        self::assertSame($innerType, $this->type->getInnerType());
    }

    public function testGetTypeExtensions(): void
    {
        $typeExtensions = [];

        $this->innerType->expects(self::once())
            ->method('getTypeExtensions')
            ->willReturn($typeExtensions);

        self::assertSame($typeExtensions, $this->type->getTypeExtensions());
    }

    public function testGetOptionsResolver(): void
    {
        $optionsResolver = $this->createMock(OptionsResolver::class);

        $this->innerType->expects(self::once())
            ->method('getOptionsResolver')
            ->willReturn($optionsResolver);

        self::assertSame($optionsResolver, $this->type->getOptionsResolver());
    }

    public function testCreateBuilder(): void
    {
        $factory = $this->createMock(FormFactoryInterface::class);
        $name = 'test';
        $options = ['data_class' => \stdClass::class];
        $resolvedOptions = $options;
        $resolvedOptions['resolved'] = true;
        $optionsResolver = $this->createMock(OptionsResolver::class);

        $this->innerType->expects(self::once())
            ->method('getOptionsResolver')
            ->willReturn($optionsResolver);
        $optionsResolver->expects(self::once())
            ->method('resolve')
            ->with($options)
            ->willReturn($resolvedOptions);

        $expectedBuilder = new ApiFormBuilder(
            $name,
            $resolvedOptions['data_class'],
            new EventDispatcher(),
            $factory,
            $resolvedOptions
        );
        $expectedBuilder->setType($this->type);

        self::assertEquals(
            $expectedBuilder,
            $this->type->createBuilder($factory, $name, $options)
        );
    }

    public function testCreateBuilderWhenNoDataClass(): void
    {
        $factory = $this->createMock(FormFactoryInterface::class);
        $name = 'test';
        $options = ['option1' => 'val1'];
        $resolvedOptions = $options;
        $resolvedOptions['resolved'] = true;
        $optionsResolver = $this->createMock(OptionsResolver::class);

        $this->innerType->expects(self::once())
            ->method('getOptionsResolver')
            ->willReturn($optionsResolver);
        $optionsResolver->expects(self::once())
            ->method('resolve')
            ->with($options)
            ->willReturn($resolvedOptions);

        $expectedBuilder = new ApiFormBuilder(
            $name,
            null,
            new EventDispatcher(),
            $factory,
            $resolvedOptions
        );
        $expectedBuilder->setType($this->type);

        self::assertEquals(
            $expectedBuilder,
            $this->type->createBuilder($factory, $name, $options)
        );
    }

    public function testCreateBuilderWhenResolveOptionsFailed(): void
    {
        $optionsResolver = $this->createMock(OptionsResolver::class);
        $innerType = new TextType();
        $exception = new UndefinedOptionsException('some error');

        $this->innerType->expects(self::once())
            ->method('getOptionsResolver')
            ->willReturn($optionsResolver);
        $this->innerType->expects(self::once())
            ->method('getInnerType')
            ->willReturn($innerType);
        $optionsResolver->expects(self::once())
            ->method('resolve')
            ->willThrowException($exception);

        $this->expectException(UndefinedOptionsException::class);
        $this->expectExceptionMessage(sprintf(
            'An error has occurred resolving the options of the form "%s": some error',
            TextType::class
        ));

        $this->type->createBuilder($this->createMock(FormFactoryInterface::class), 'test', []);
    }

    public function testCreateView(): void
    {
        $form = $this->createMock(FormInterface::class);
        $parent = $this->createMock(FormView::class);
        $view = $this->createMock(FormView::class);

        $this->innerType->expects(self::once())
            ->method('createView')
            ->with(self::identicalTo($form), self::identicalTo($parent))
            ->willReturn($view);

        self::assertSame($view, $this->type->createView($form, $parent));
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = ['key' => 'value'];

        $this->innerType->expects(self::once())
            ->method('buildForm')
            ->with(self::identicalTo($builder), self::identicalTo($options));

        $this->type->buildForm($builder, $options);
    }

    public function testBuildView(): void
    {
        $view = $this->createMock(FormView::class);
        $form = $this->createMock(FormInterface::class);
        $options = ['key' => 'value'];

        $this->innerType->expects(self::once())
            ->method('buildView')
            ->with(self::identicalTo($view), self::identicalTo($form), self::identicalTo($options));

        $this->type->buildView($view, $form, $options);
    }

    public function testFinishView(): void
    {
        $view = $this->createMock(FormView::class);
        $form = $this->createMock(FormInterface::class);
        $options = ['key' => 'value'];

        $this->innerType->expects(self::once())
            ->method('finishView')
            ->with(self::identicalTo($view), self::identicalTo($form), self::identicalTo($options));

        $this->type->finishView($view, $form, $options);
    }
}
