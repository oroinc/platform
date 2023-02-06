<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Utils;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\DataTransformer\DataTransformerChain;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\Form\Test\FormBuilderInterface;
use Symfony\Component\Form\Test\FormInterface;

class FormUtilsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider optionsProvider
     */
    public function testReplaceField(
        array $expectedOptions = [],
        array $modifyOptions = [],
        array $unsetOptions = []
    ): void {
        $testFieldName = 'testField';
        $testOptions = ['required' => true, 'auto_initialize' => true];

        $rootForm = $this->createMock(FormInterface::class);
        $childForm = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formType = $this->createMock(ResolvedFormTypeInterface::class);
        $formType->expects(self::any())
            ->method('getInnerType')
            ->willReturn(new EntityTypeStub());

        $rootForm->expects(self::once())
            ->method('get')
            ->with($testFieldName)
            ->willReturn($childForm);

        $childForm->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig->expects(self::once())
            ->method('getType')
            ->willReturn($formType);
        $formConfig->expects(self::once())
            ->method('getOptions')
            ->willReturn($testOptions);

        $rootForm->expects(self::once())
            ->method('add')
            ->with($testFieldName, EntityTypeStub::class, $expectedOptions);

        FormUtils::replaceField($rootForm, $testFieldName, $modifyOptions, $unsetOptions);
    }

    public function optionsProvider(): array
    {
        return [
            'should pass original options except auto_initialize' => [
                ['required' => true, 'auto_initialize' => false],
                [],
                []
            ],
            'should override options'                             => [
                ['required' => false, 'auto_initialize' => false],
                ['required' => false],
                []
            ],
            'should unset options'                                => [
                ['auto_initialize' => false],
                [],
                ['required']
            ]
        ];
    }

    public function testAppendClassForSingleClass(): void
    {
        $formView = new FormView();
        $formView->vars = [];

        FormUtils::appendClass($formView, 'singleClass');
        self::assertSame(['attr' => ['class' => 'singleClass']], $formView->vars);
    }

    /**
     * @dataProvider viewVariablesProvider
     */
    public function testAppendClassForMultipleClasses(array $vars, array $classToAppend, array $expectedVars): void
    {
        $formView = new FormView();
        $formView->vars = $vars;

        FormUtils::appendClass($formView, $classToAppend);
        self::assertSame($expectedVars, $formView->vars);
    }

    public function viewVariablesProvider(): array
    {
        return [
            'add multiple classes'        => [
                'vars'          => [],
                'classToAppend' => ['1stClass', '2ndClass'],
                'expectedVars'  => ['attr' => ['class' => '1stClass 2ndClass']]
            ],
            'should append, not override' => [
                'vars'          => ['attr' => ['class' => '1stClass'], 'another' => 'not overridden'],
                'classToAppend' => ['2ndClass'],
                'expectedVars'  => ['attr' => ['class' => '1stClass 2ndClass'], 'another' => 'not overridden']
            ]
        ];
    }

    /**
     * @dataProvider transformerProvider
     */
    public function testReplaceTransformer(
        array $existingTransformers,
        string $type,
        DataTransformerInterface $toReplace,
        array $expected
    ): void {
        $builder = $this->createMock(FormBuilderInterface::class);

        $model = 'model' === $type;
        $builder->expects(self::once())
            ->method($model ? 'getModelTransformers' : 'getViewTransformers')
            ->willReturn($existingTransformers);
        $builder->expects(self::once())
            ->method($model ? 'resetModelTransformers' : 'resetViewTransformers');

        $newTransformers = [];
        $builder->expects(self::any())
            ->method($model ? 'addModelTransformer' : 'addViewTransformer')
            ->willReturnCallback(function ($transformer) use (&$newTransformers) {
                $newTransformers [] = $transformer;
            });

        FormUtils::replaceTransformer($builder, $toReplace, $type);

        self::assertSame($expected, $newTransformers);
    }

    public function transformerProvider(): array
    {
        $newTransformer = new DataTransformerChain([]);
        $transformerOrigin = new DataTransformerChain([]);
        $transformer1 = $this->createMock(DataTransformerInterface::class);
        $transformer3 = $this->createMock(DataTransformerInterface::class);

        return [
            'should append view transformer'                => [
                'existingTransformers' => [],
                'type'                 => 'view',
                'toReplace'            => $newTransformer,
                'expected'             => [$newTransformer],
            ],
            'should append model transformer'               => [
                'existingTransformers' => [],
                'type'                 => 'model',
                'toReplace'            => $newTransformer,
                'expected'             => [$newTransformer],
            ],
            'should replace view transformer'               => [
                'existingTransformers' => [$transformerOrigin, $transformer1],
                'type'                 => 'view',
                'toReplace'            => $newTransformer,
                'expected'             => [$newTransformer, $transformer1],
            ],
            'should replace model transformer keep sotring' => [
                'existingTransformers' => [$transformer1, $transformerOrigin, $transformer3],
                'type'                 => 'model',
                'toReplace'            => $newTransformer,
                'expected'             => [$transformer1, $newTransformer, $transformer3],
            ],
        ];
    }

    /**
     * @dataProvider replaceOptionsDataProvider
     */
    public function testReplaceFieldOptionsRecursive(
        array $fieldOptions = [],
        array $replaceOptions = [],
        array $expectedOptions = []
    ): void {
        $testFieldName = 'testField';

        $typeStub = new EntityTypeStub();
        $rootForm = $this->createMock(FormInterface::class);
        $childForm = $this->createMock(FormInterface::class);
        $formConfig = $this->createMock(FormConfigInterface::class);
        $formType = $this->createMock(ResolvedFormTypeInterface::class);
        $formType->expects(self::any())
            ->method('getInnerType')
            ->willReturn($typeStub);

        $rootForm->expects(self::once())
            ->method('get')
            ->with($testFieldName)
            ->willReturn($childForm);

        $childForm->expects(self::once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig->expects(self::once())
            ->method('getType')
            ->willReturn($formType);
        $formConfig->expects(self::once())
            ->method('getOptions')
            ->willReturn($fieldOptions);

        $rootForm->expects(self::once())
            ->method('add')
            ->with($testFieldName, EntityTypeStub::class, $expectedOptions);

        FormUtils::replaceFieldOptionsRecursive($rootForm, $testFieldName, $replaceOptions);
    }

    public function replaceOptionsDataProvider(): array
    {
        return [
            'no options modified'                                      => [
                ['required' => true, 'attr' => ['readonly' => true]],
                [],
                ['required' => true, 'attr' => ['readonly' => true]]
            ],
            'disabled option is merged and replaces existing option'   => [
                ['attr' => ['disabled' => true]],
                ['attr' => ['disabled' => false]],
                ['attr' => ['disabled' => false]]
            ],
            'string option is replaced'                                => [
                ['required' => true],
                ['required' => false],
                ['required' => false]
            ],
            'readonly option is merged and added to attr array option' => [
                ['attr' => []],
                ['attr' => ['readonly' => true]],
                ['attr' => ['readonly' => true]],
            ]
        ];
    }
}
