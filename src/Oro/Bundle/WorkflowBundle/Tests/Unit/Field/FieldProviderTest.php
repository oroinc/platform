<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Field;

use Oro\Bundle\EntityBundle\Tests\Unit\Provider\EntityFieldProviderTest;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\WorkflowBundle\Field\FieldProvider;

class FieldProviderTest extends EntityFieldProviderTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->provider = new FieldProvider(
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->entityClassResolver,
            new FieldTypeHelper([]),
            $this->doctrine,
            $this->translator,
            []
        );

        $this->provider->setEntityProvider($this->entityProvider);
        $this->provider->setVirtualFieldProvider($this->virtualFieldProvider);
        $this->provider->setVirtualRelationProvider($this->virtualRelationProvider);
        $this->provider->setExclusionProvider($this->exclusionProvider);
    }

    /**
     * exclusions are not used in workflow
     *
     * {@inheritdoc}
     */
    public function fieldsWithRelationsExpectedDataProvider()
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C',
                        'identifier' => true
                    ],
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsWithRelationsAndDeepLevelDataProvider()
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C',
                        'identifier' => true
                    ],
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsWithRelationsAndDeepLevelAndEntityDetailsDataProvider()
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C',
                        'identifier' => true
                    ],
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsWithRelationsAndDeepLevelAndLastLevelRelations()
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C',
                        'identifier' => true
                    ],
                ]
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldsWithRelationsAndDeepLevelAndLastLevelRelationsAndEntityDetailsDataProvider()
    {
        return [
            [
                [
                    [
                        'name' => 'field3',
                        'type' => 'string',
                        'label' => 'A',
                    ],
                    [
                        'name' => 'field4',
                        'type' => 'string',
                        'label' => 'acme.entity.test.field4.label',
                    ],
                    [
                        'name' => 'field2',
                        'type' => 'string',
                        'label' => 'B',
                    ],
                    [
                        'name' => 'field1',
                        'type' => 'integer',
                        'label' => 'C',
                        'identifier' => true
                    ],
                ]
            ]
        ];
    }
}
