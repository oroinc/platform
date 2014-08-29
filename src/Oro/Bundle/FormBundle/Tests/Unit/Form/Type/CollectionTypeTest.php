<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class CollectionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CollectionType
     */
    protected $type;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->type = new CollectionType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\FormBundle\Form\EventListener\CollectionTypeSubscriber'));

        $options = array();
        $this->type->buildForm($builder, $options);
    }

    /**
     * @dataProvider buildViewDataProvider
     */
    public function testBuildView($options, $expectedVars)
    {
        $form = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $view = new FormView();

        $this->type->buildView($view, $form, $options);

        foreach ($expectedVars as $key => $val) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($val, $view->vars[$key]);
        }
    }

    public function buildViewDataProvider()
    {
        return [
            [
                'options'      => [
                    'handle_primary'       => false,
                    'show_form_when_empty' => false
                ],
                'expectedVars' => [
                    'handle_primary'       => false,
                    'show_form_when_empty' => false
                ],
            ],
            [
                'options'      => [
                    'handle_primary'       => true,
                    'show_form_when_empty' => true
                ],
                'expectedVars' => [
                    'handle_primary'       => true,
                    'show_form_when_empty' => true
                ],
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "type" is missing.
     */
    public function testSetDefaultOptionsWithoutType()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->setDefaultOptions($resolver);
        $resolver->resolve([]);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->setDefaultOptions($resolver);

        $options = [
            'type' => 'test_type'
        ];
        $resolvedOptions = $resolver->resolve($options);
        $this->assertEquals(
            [
                'type'                 => 'test_type',
                'allow_add'            => true,
                'allow_delete'         => true,
                'by_reference'         => false,
                'prototype'            => true,
                'prototype_name'       => '__name__',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'handle_primary'       => true,
                'show_form_when_empty' => true
            ],
            $resolvedOptions
        );
    }

    public function testSetDefaultOptionsDisableAdd()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->setDefaultOptions($resolver);

        $options = [
            'type'      => 'test_type',
            'allow_add' => false
        ];
        $resolvedOptions = $resolver->resolve($options);
        $this->assertEquals(
            [
                'type'                 => 'test_type',
                'allow_add'            => false,
                'allow_delete'         => true,
                'by_reference'         => false,
                'prototype'            => true,
                'prototype_name'       => '__name__',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'handle_primary'       => true,
                'show_form_when_empty' => false
            ],
            $resolvedOptions
        );
    }

    public function testSetDefaultOptionsDisableShowFormWhenEmpty()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->setDefaultOptions($resolver);

        $options = [
            'type'                 => 'test_type',
            'show_form_when_empty' => false
        ];
        $resolvedOptions = $resolver->resolve($options);
        $this->assertEquals(
            [
                'type'                 => 'test_type',
                'allow_add'            => true,
                'allow_delete'         => true,
                'by_reference'         => false,
                'prototype'            => true,
                'prototype_name'       => '__name__',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'handle_primary'       => true,
                'show_form_when_empty' => false
            ],
            $resolvedOptions
        );
    }

    public function testGetParent()
    {
        $this->assertEquals('collection', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_collection', $this->type->getName());
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
            ]
        );

        return $resolver;
    }
}
