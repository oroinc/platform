<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\EntityIdentifierTypeStub;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class EntityTreeSelectTypeTest extends FormIntegrationTestCase
{
    private EntityTreeSelectType $formType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formType = new EntityTreeSelectType();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    EntityIdentifierType::class => new EntityIdentifierTypeStub([1 => new TestEntity(1)])
                ],
                []
            ),
        ];
    }

    public function testGetBlockPrefix(): void
    {
        self::assertEquals(EntityTreeSelectType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetParent(): void
    {
        self::assertEquals(EntityIdentifierType::class, $this->formType->getParent());
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testOptions(array $options, int|null $data, array $expectedViewVars): void
    {
        $form = $this->factory->create(EntityTreeSelectType::class, null, $options);
        $form->submit($data);
        $view = $form->createView();

        foreach ($expectedViewVars as $expectedKey => $expectedValue) {
            self::assertArrayHasKey($expectedKey, $view->vars);
            self::assertEquals($expectedValue, $view->vars[$expectedKey]);
        }
    }

    public function optionsDataProvider(): array
    {
        return [
            'data array' => [
                [
                    'class' => TestEntity::class,
                    'tree_key' => 'test',
                    'tree_data' => [],
                ],
                null,
                [
                    'treeOptions' => [
                        'view' => 'oroform/js/app/components/entity-tree-select-form-type-view',
                        'key' => 'test',
                        'data' => [],
                        'nodeId' => null,
                        'fieldSelector' => '#oro_entity_tree_select',
                        'disabled' => false,
                    ],
                ],
            ],
            'data callback' => [
                [
                    'class' => TestEntity::class,
                    'tree_key' => 'test',
                    'tree_data' => function () {
                        return [];
                    },
                ],
                null,
                [
                    'treeOptions' => [
                        'view' => 'oroform/js/app/components/entity-tree-select-form-type-view',
                        'key' => 'test',
                        'data' => [],
                        'nodeId' => null,
                        'fieldSelector' => '#oro_entity_tree_select',
                        'disabled' => false,
                    ],
                ],
            ],
            'custom component' => [
                [
                    'class' => TestEntity::class,
                    'tree_key' => 'test',
                    'tree_data' => [],
                    'page_component_module' => 'myModule',
                ],
                1,
                [
                    'treeOptions' => [
                        'view' => 'myModule',
                        'key' => 'test',
                        'data' => [],
                        'nodeId' => 1,
                        'fieldSelector' => '#oro_entity_tree_select',
                        'disabled' => false,
                    ],
                ],
            ],
            'disabled' => [
                [
                    'class' => TestEntity::class,
                    'tree_key' => 'test',
                    'tree_data' => [],
                    'disabled' => true,
                ],
                null,
                [
                    'treeOptions' => [
                        'view' => 'oroform/js/app/components/entity-tree-select-form-type-view',
                        'key' => 'test',
                        'data' => [],
                        'nodeId' => null,
                        'fieldSelector' => '#oro_entity_tree_select',
                        'disabled' => true,
                    ],
                ],
            ],
        ];
    }
}
