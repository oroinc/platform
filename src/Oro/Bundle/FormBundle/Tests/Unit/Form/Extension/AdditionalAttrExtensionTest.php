<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdditionalAttrExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const ID = 'test_id';

    /** @var AdditionalAttrExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new AdditionalAttrExtension();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['random_id' => true]);

        $this->extension->configureOptions($resolver);
    }

    /**
     * @dataProvider finishViewData
     */
    public function testFinishView(FormView $view, array $options, array $expectedVars)
    {
        $formMock = $this->createMock(Form::class);

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

    public function finishViewData(): array
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

    private function createView(array $vars, bool $hasParent = false): FormView
    {
        $result = new FormView();
        $result->vars = $vars;
        if ($hasParent) {
            $result->parent = new FormView();
        }

        return $result;
    }
}
