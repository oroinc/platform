<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Field;

use Oro\Bundle\EntityBundle\Tests\Unit\Provider\EntityFieldProviderTest;
use Oro\Bundle\WorkflowBundle\Field\FieldProvider;

class FieldProviderTest extends EntityFieldProviderTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new FieldProvider(
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->entityClassResolver,
            $this->fieldTypeHelper,
            $this->doctrine,
            $this->translator,
            []
        );

        $this->provider->setEntityProvider($this->entityProvider);
        $this->provider->setVirtualFieldProvider($this->virtualFieldProvider);
        $this->provider->setVirtualRelationProvider($this->virtualRelationProvider);
        $this->provider->setExclusionProvider($this->exclusionProvider);
    }

    public function fieldsWithRelationsExpectedDataProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A Translated',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label Translated',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B Translated',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C Translated',
                        'identifier' => true,
                    ],
                ],
            ],
        ];
    }

    public function getFieldsWithRelationsAndDeepLevelDataProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A Translated',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label Translated',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B Translated',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C Translated',
                        'identifier' => true,
                    ],
                ],
            ],
        ];
    }

    public function getFieldsWithRelationsAndDeepLevelAndEntityDetailsDataProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A Translated',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label Translated',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B Translated',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C Translated',
                        'identifier' => true,
                    ],
                ],
            ],
        ];
    }

    public function getFieldsWithRelationsAndDeepLevelAndLastLevelRelations(): array
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A Translated',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label Translated',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B Translated',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C Translated',
                        'identifier' => true,
                    ],
                ],
            ],
        ];
    }

    public function getFieldsWithRelationsAndDeepLevelAndLastLevelRelationsAndEntityDetailsDataProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A Translated',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label Translated',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B Translated',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C Translated',
                        'identifier' => true,
                    ],
                ],
            ],
        ];
    }

    public function getFieldsWithRelationsAndDeepLevelAndWithUnidirectional(): array
    {
        return [
            [
                [
                    [
                        'name' => 'Test1field2',
                        'type' => 'string',
                        'label' => 'A Translated',
                    ],
                    [
                        'name' => 'id',
                        'type' => 'integer',
                        'label' => 'B Translated',
                        'identifier' => true,
                    ],
                    [
                        'name' => 'rel1',
                        'type' => 'ref-one',
                        'label' => 'Rel11 Translated',
                    ],
                    [
                        'name' => 'rel1',
                        'type' => 'ref-one',
                        'label' => 'Rel11 Translated',
                        'relation_type' => 'ref-one',
                        'related_entity_name' => 'Acme\Entity\Test11',
                    ],
                    [
                        'name' => 'Acme\Entity\Test22::uni_rel1',
                        'type' => 'ref-one',
                        'label' => 'UniRel1 Translated (Test22 Label Translated)',
                        'relation_type' => 'ref-one',
                        'related_entity_name' => 'Acme\Entity\Test22',
                    ],
                ],
            ],
        ];
    }

    public function getFieldsWithVirtualRelationsAndEnumsDataProvider(): array
    {
        $expectedResult = [
            [
                [
                    [
                        'name' => 'rel1',
                        'type' => 'ref-one',
                        'label' => 'Enum Field Translated',
                    ],
                    [
                        'name' => 'rel1',
                        'type' => 'enum',
                        'label' => 'Enum Field Translated',
                        'related_entity_name' => 'Acme\EnumValue1',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'Field 1 Translated',
                        'identifier' => true,
                    ],
                    [
                        'name' => 'rel2',
                        'type' => 'multiEnum',
                        'label' => 'Multi Enum Field Translated',
                        'related_entity_name' => 'Acme\EnumValue2',
                    ],
                    [
                        'name' => 'virtual_relation',
                        'type' => 'oneToMany',
                        'label' => 'acme.entity.test.virtual_relation.label Translated',
                        'relation_type' => 'oneToMany',
                        'related_entity_name' => 'OtherEntity',
                    ],
                ],
            ],
        ];

        array_splice($expectedResult[0][0], 0, 2, [$expectedResult[0][0][1], $expectedResult[0][0][0]]);

        return $expectedResult;
    }

    public function relationsExpectedDataProvider(): array
    {
        return [
            [
                [],
            ],
        ];
    }
}
