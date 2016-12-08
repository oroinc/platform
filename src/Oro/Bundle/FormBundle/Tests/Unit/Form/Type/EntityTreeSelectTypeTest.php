<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\EntityIdentifierType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class EntityTreeSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var EntityTreeSelectType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new EntityTreeSelectType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $entityIdentifierType = new EntityIdentifierType(
            [
                1 => new TestEntity(1)
            ]
        );

        return [
            new PreloadedExtension(
                [
                    $entityIdentifierType->getName() => $entityIdentifierType,
                ],
                []
            )
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(EntityTreeSelectType::NAME, $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(EntityTreeSelectType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_entity_identifier', $this->formType->getParent());
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param int|null $data
     * @param array $expectedViewVars
     */
    public function testOptions(array $options, $data, array $expectedViewVars)
    {
        $form = $this->factory->create($this->formType, null, $options);
        $form->submit($data);
        $view = $form->createView();

        foreach ($expectedViewVars as $expectedKey => $expectedValue) {
            $this->assertArrayHasKey($expectedKey, $view->vars);
            $this->assertEquals($expectedValue, $view->vars[$expectedKey]);
        }
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            'data array' => [
                [
                    'class' => TestEntity::class,
                    'tree_key' => 'test',
                    'tree_data' => []
                ],
                null,
                [
                    'pageComponentModule' => 'oroform/js/app/components/entity-tree-select-form-type-component',
                    'treeOptions' => [
                        'key' => 'test',
                        'data' => [],
                        'nodeId' => null,
                        'fieldSelector' => '#oro_entity_tree_select'
                    ]
                ]
            ],
            'data callback' => [
                [
                    'class' => TestEntity::class,
                    'tree_key' => 'test',
                    'tree_data' => function () {
                        return [];
                    }
                ],
                null,
                [
                    'pageComponentModule' => 'oroform/js/app/components/entity-tree-select-form-type-component',
                    'treeOptions' => [
                        'key' => 'test',
                        'data' => [],
                        'nodeId' => null,
                        'fieldSelector' => '#oro_entity_tree_select'
                    ]
                ]
            ],
            'custom component' => [
                [
                    'class' => TestEntity::class,
                    'tree_key' => 'test',
                    'tree_data' => [],
                    'page_component_module' => 'myModule'
                ],
                1,
                [
                    'pageComponentModule' => 'myModule',
                    'treeOptions' => [
                        'key' => 'test',
                        'data' => [],
                        'nodeId' => 1,
                        'fieldSelector' => '#oro_entity_tree_select'
                    ]
                ]
            ]
        ];
    }
}
