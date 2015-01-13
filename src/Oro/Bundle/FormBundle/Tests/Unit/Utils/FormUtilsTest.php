<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Utils;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StubTransformer;

class FormUtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider optionsProvider
     *
     * @param array $expectedOptions
     * @param array $modifyOptions
     * @param array $unsetOptions
     */
    public function testReplaceField($expectedOptions = [], $modifyOptions = [], $unsetOptions = [])
    {
        $testFieldName = 'testField';
        $testTypeName  = 'testType';
        $testOptions   = ['required' => true, 'auto_initialize' => true];

        $rootForm   = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $childForm  = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $formConfig = $this->getMock('Symfony\Component\Form\FormConfigInterface');
        $formType   = $this->getMock('Symfony\Component\Form\ResolvedFormTypeInterface');

        $rootForm->expects($this->once())->method('get')->with($testFieldName)
            ->will($this->returnValue($childForm));

        $childForm->expects($this->once())->method('getConfig')
            ->will($this->returnValue($formConfig));

        $formConfig->expects($this->once())->method('getType')
            ->will($this->returnValue($formType));

        $formConfig->expects($this->once())->method('getOptions')
            ->will($this->returnValue($testOptions));

        $formType->expects($this->once())->method('getName')
            ->will($this->returnValue($testTypeName));

        $rootForm->expects($this->once())->method('add')
            ->with($testFieldName, $testTypeName, $expectedOptions);

        FormUtils::replaceField($rootForm, $testFieldName, $modifyOptions, $unsetOptions);
    }

    /**
     * @return array
     */
    public function optionsProvider()
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

    /**
     * @dataProvider viewVariablesProvider
     *
     * @param array        $vars
     * @param string|array $classToAppend
     * @param array        $expectedVars
     */
    public function testAddClass($vars, $classToAppend, $expectedVars)
    {
        $formView       = new FormView();
        $formView->vars = $vars;

        FormUtils::appendClass($formView, $classToAppend);
        $this->assertSame($expectedVars, $formView->vars);
    }

    /**
     * @return array
     */
    public function viewVariablesProvider()
    {
        return [
            'add single class'            => [
                '$vars'          => [],
                '$classToAppend' => 'singleClass',
                '$expectedVars'  => ['attr' => ['class' => 'singleClass']]
            ],
            'add multiple classes'        => [
                '$vars'          => [],
                '$classToAppend' => ['1stClass', '2ndClass'],
                '$expectedVars'  => ['attr' => ['class' => '1stClass 2ndClass']]
            ],
            'should append, not override' => [
                '$vars'          => ['attr' => ['class' => '1stClass'], 'another' => 'not overridden'],
                '$classToAppend' => ['2ndClass'],
                '$expectedVars'  => ['attr' => ['class' => '1stClass 2ndClass'], 'another' => 'not overridden']
            ]
        ];
    }

    /**
     * @dataProvider transformerProvider
     *
     * @param array                    $existingTransformers
     * @param string                   $type
     * @param DataTransformerInterface $toReplace
     * @param array                    $expected
     */
    public function testReplaceTransformer(array $existingTransformers, $type, $toReplace, array $expected)
    {
        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');

        $model = 'model' === $type;
        $builder->expects($this->once())->method($model ? 'getModelTransformers' : 'getViewTransformers')
            ->willReturn($existingTransformers);
        $builder->expects($this->once())->method($model ? 'resetModelTransformers' : 'resetViewTransformers');

        $newTransformers = [];
        $builder->expects($this->any())->method($model ? 'addModelTransformer' : 'addViewTransformer')
            ->willReturnCallback(
                function ($transformer) use (&$newTransformers) {
                    $newTransformers [] = $transformer;
                }
            );


        FormUtils::replaceTransformer($builder, $toReplace, $type);

        $this->assertSame($expected, $newTransformers);
    }

    /**
     * @return array
     */
    public function transformerProvider()
    {
        $newTransformer = new StubTransformer();

        $transformerOrigin = new StubTransformer();
        $transformer1      = $this->getMock('Symfony\Component\Form\DataTransformerInterface');
        $transformer3      = $this->getMock('Symfony\Component\Form\DataTransformerInterface');

        return [
            'should append view transformer'                => [
                '$existingTransformers' => [],
                '$type'                 => 'view',
                '$toReplace'            => $newTransformer,
                '$expected'             => [$newTransformer],
            ],
            'should append model transformer'               => [
                '$existingTransformers' => [],
                '$type'                 => 'model',
                '$toReplace'            => $newTransformer,
                '$expected'             => [$newTransformer],
            ],
            'should replace view transformer'               => [
                '$existingTransformers' => [$transformerOrigin, $transformer1],
                '$type'                 => 'view',
                '$toReplace'            => $newTransformer,
                '$expected'             => [$newTransformer, $transformer1],
            ],
            'should replace model transformer keep sotring' => [
                '$existingTransformers' => [$transformer1, $transformerOrigin, $transformer3],
                '$type'                 => 'model',
                '$toReplace'            => $newTransformer,
                '$expected'             => [$transformer1, $newTransformer, $transformer3],
            ],
        ];
    }
}
