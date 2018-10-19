<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType as BaseCollectionType;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionTypeTest extends \PHPUnit\Framework\TestCase
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
        $builder = $this->createMock('Symfony\Component\Form\Test\FormBuilderInterface');

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\FormBundle\Form\EventListener\CollectionTypeSubscriber'));

        $options = array();
        $this->type->buildForm($builder, $options);
    }

    /**
     * @dataProvider buildViewDataProvider
     * @param array $options
     * @param array $expectedVars
     */
    public function testBuildView(array $options, array $expectedVars)
    {
        $form = $this->createMock('Symfony\Component\Form\Test\FormInterface');
        $view = new FormView();

        $this->type->buildView($view, $form, $options);

        foreach ($expectedVars as $key => $val) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($val, $view->vars[$key]);
        }
    }

    /**
     * @return array
     */
    public function buildViewDataProvider()
    {
        return [
            [
                'options'      => [
                    'handle_primary'       => false,
                    'show_form_when_empty' => false,
                    'prototype_name'       => '__name__',
                    'add_label'            => 'Add',
                    'allow_add_after'      => false,
                    'row_count_add'        => 1,
                    'row_count_initial'    => 1,
                ],
                'expectedVars' => [
                    'handle_primary'       => false,
                    'show_form_when_empty' => false,
                    'prototype_name'       => '__name__',
                    'add_label'            => 'Add',
                    'row_count_initial'    => 1,
                ],
            ],
            [
                'options'      => [
                    'handle_primary'       => true,
                    'show_form_when_empty' => true,
                    'prototype_name'       => '__custom_name__',
                    'add_label'            => 'Test Label',
                    'allow_add_after'      => false,
                    'row_count_add'        => 1,
                    'row_count_initial'    => 5,
                ],
                'expectedVars' => [
                    'handle_primary'       => true,
                    'show_form_when_empty' => true,
                    'prototype_name'       => '__custom_name__',
                    'add_label'            => 'Test Label',
                    'row_count_initial'    => 5,
                ],
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "entry_type" is missing.
     */
    public function testConfigureOptionsWithoutType()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);
        $resolver->resolve([]);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

        $options = [
            'entry_type' => 'test_type'
        ];
        $resolvedOptions = $resolver->resolve($options);
        $this->assertEquals(
            [
                'entry_type'           => 'test_type',
                'allow_add'            => true,
                'allow_delete'         => true,
                'by_reference'         => false,
                'prototype'            => true,
                'prototype_name'       => '__name__',
                'handle_primary'       => true,
                'show_form_when_empty' => true,
                'add_label'            => '',
                'allow_add_after'      => false,
                'row_count_add'        => 1,
                'row_count_initial'    => 1,
            ],
            $resolvedOptions
        );
    }

    public function testConfigureOptionsDisableAdd()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

        $options = [
            'entry_type' => 'test_type',
            'allow_add' => false
        ];
        $resolvedOptions = $resolver->resolve($options);
        $this->assertEquals(
            [
                'entry_type'           => 'test_type',
                'allow_add'            => false,
                'allow_delete'         => true,
                'by_reference'         => false,
                'prototype'            => true,
                'prototype_name'       => '__name__',
                'handle_primary'       => true,
                'show_form_when_empty' => false,
                'add_label'            => '',
                'allow_add_after'      => false,
                'row_count_add'        => 1,
                'row_count_initial'    => 1,
            ],
            $resolvedOptions
        );
    }

    public function testConfigureOptionsDisableShowFormWhenEmpty()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

        $options = [
            'entry_type'           => 'test_type',
            'show_form_when_empty' => false
        ];
        $resolvedOptions = $resolver->resolve($options);
        $this->assertEquals(
            [
                'entry_type'           => 'test_type',
                'allow_add'            => true,
                'allow_delete'         => true,
                'by_reference'         => false,
                'prototype'            => true,
                'prototype_name'       => '__name__',
                'handle_primary'       => true,
                'show_form_when_empty' => false,
                'add_label'            => '',
                'allow_add_after'      => false,
                'row_count_add'        => 1,
                'row_count_initial'    => 1,
            ],
            $resolvedOptions
        );
    }

    public function testConfigureOptionsCustomAddLabel()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

        $options = [
            'entry_type'           => 'test_type',
            'add_label'            => 'Test Label'
        ];
        $resolvedOptions = $resolver->resolve($options);
        $this->assertEquals(
            [
                'entry_type'           => 'test_type',
                'allow_add'            => true,
                'allow_delete'         => true,
                'by_reference'         => false,
                'prototype'            => true,
                'prototype_name'       => '__name__',
                'handle_primary'       => true,
                'show_form_when_empty' => true,
                'add_label'            => 'Test Label',
                'allow_add_after'      => false,
                'row_count_add'        => 1,
                'row_count_initial'    => 1,
            ],
            $resolvedOptions
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(BaseCollectionType::class, $this->type->getParent());
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
