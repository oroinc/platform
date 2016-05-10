<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormView;

use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;

class AdditionalAttrExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ID = 'test_id';
    /**
     * @var AdditionalAttrExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new AdditionalAttrExtension();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())->method('setDefaults')
            ->with(['random_id' => true]);

        $this->extension->setDefaultOptions($resolver);
    }

    /**
     * @dataProvider finishViewData
     * @param FormView $view
     * @param array $options
     * @param array $expectedVars
     */
    public function testFinishView(FormView $view, array $options, array  $expectedVars)
    {
        $formMock = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension->finishView($view, $formMock, $options);

        $this->assertSameSize($expectedVars, $view->vars);
        foreach ($expectedVars as $name => $value) {
            $this->assertArrayHasKey($name, $view->vars);
            if ($options['random_id'] && $name === 'id') {
                $this->assertNotEmpty($view->vars[$name]);
            } else {
                $this->assertEquals($value, $view->vars[$name]);
            }
        }
    }

    /**
     * @return array
     */
    public function finishViewData()
    {
        return [
            'add random hash' => [
                'view'   => $this->createView(['id' => self::ID]),
                'option' => ['random_id' => true],
                'expectedVars' => [
                    'id'    => self::ID,
                    'attr'  => ['data-ftid'=> self::ID]
                ]
            ],
            'without random hash' => [
                'view'   => $this->createView(['id' => self::ID]),
                'option' => ['random_id' => false],
                'expectedVars' => [
                    'id'=> self::ID
                ]
            ],
            'without id' => [
                'view'   => $this->createView([]),
                'option' => ['random_id' => false],
                'expectedVars' => []
            ],
            'with camel case name' =>  [
                'view'   => $this->createView(['name' => 'camelCaseName']),
                'option' => ['random_id' => false],
                'expectedVars' => [
                    'name'      => 'camelCaseName',
                    'attr'      => ['data-name' => 'form__camel-case-name']
                ]
            ],
            'with snake case name'=>  [
                'view'   => $this->createView(['name' => 'snake_case_name']),
                'option' => ['random_id' => false],
                'expectedVars' => [
                    'name'      => 'snake_case_name',
                    'attr'      => ['data-name' => 'form__snake-case-name']
                ]
            ],
            'with name and parent'=>  [
                'view'   => $this->createView(['name' => 'formname'], true),
                'option' => ['random_id' => false],
                'expectedVars' => [
                    'name'      => 'formname',
                    'attr'      => ['data-name' => 'field__formname']
                ]
            ],
        ];
    }

    /**
     * @param array $vars
     * @param bool $hasParent
     * @return FormView
     */
    protected function createView(array $vars, $hasParent = false)
    {
        $result = new FormView();
        $result->vars = $vars;
        if ($hasParent) {
            $result->parent = new FormView();
        }

        return $result;
    }
}
