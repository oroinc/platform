<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityFieldFallbackValueTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroEntityBundle/Tests/Functional/Api/DataFixtures/entity_field_fallback_value.yml'
        ]);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'entityfieldfallbackvalues', 'id' => '<toString(@scalar_value->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'entityfieldfallbackvalues',
                    'id'         => '<toString(@scalar_value->id)>',
                    'attributes' => [
                        'fallback'    => null,
                        'scalarValue' => 'test value',
                        'arrayValue'  => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'entityfieldfallbackvalues'],
            [
                'filter' => [
                    'id' => [
                        '<toString(@fallback_value->id)>',
                        '<toString(@scalar_value->id)>',
                        '<toString(@array_value->id)>'
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'entityfieldfallbackvalues',
                        'id'         => '<toString(@fallback_value->id)>',
                        'attributes' => [
                            'fallback'    => 'test_fallback',
                            'scalarValue' => null,
                            'arrayValue'  => null
                        ]
                    ],
                    [
                        'type'       => 'entityfieldfallbackvalues',
                        'id'         => '<toString(@scalar_value->id)>',
                        'attributes' => [
                            'fallback'    => null,
                            'scalarValue' => 'test value',
                            'arrayValue'  => null
                        ]
                    ],
                    [
                        'type'       => 'entityfieldfallbackvalues',
                        'id'         => '<toString(@array_value->id)>',
                        'attributes' => [
                            'fallback'    => null,
                            'scalarValue' => null,
                            'arrayValue'  => [1, 2, 3]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateFallbackValue()
    {
        $data = [
            'data' => [
                'type'       => 'entityfieldfallbackvalues',
                'attributes' => [
                    'fallback'    => 'test_fallback',
                    'scalarValue' => null,
                    'arrayValue'  => null
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'entityfieldfallbackvalues'],
            $data
        );

        $valueId = (int)$this->getResourceId($response);

        $expectedResponseData = $data;
        $expectedResponseData['data']['id'] = (string)$valueId;
        $this->assertResponseContains($expectedResponseData, $response);

        /** @var EntityFieldFallbackValue $value */
        $value = $this->getEntityManager()->find(EntityFieldFallbackValue::class, $valueId);
        self::assertEquals('test_fallback', $value->getFallback());
        self::assertNull($value->getScalarValue());
        self::assertNull($value->getArrayValue());
    }

    public function testCreateScalarValue()
    {
        $data = [
            'data' => [
                'type'       => 'entityfieldfallbackvalues',
                'attributes' => [
                    'fallback'    => null,
                    'scalarValue' => 'test value',
                    'arrayValue'  => null
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'entityfieldfallbackvalues'],
            $data
        );

        $valueId = (int)$this->getResourceId($response);

        $expectedResponseData = $data;
        $expectedResponseData['data']['id'] = (string)$valueId;
        $this->assertResponseContains($expectedResponseData, $response);

        /** @var EntityFieldFallbackValue $value */
        $value = $this->getEntityManager()->find(EntityFieldFallbackValue::class, $valueId);
        self::assertNull($value->getFallback());
        self::assertEquals('test value', $value->getScalarValue());
        self::assertNull($value->getArrayValue());
    }

    public function testCreateArrayValue()
    {
        $data = [
            'data' => [
                'type'       => 'entityfieldfallbackvalues',
                'attributes' => [
                    'fallback'    => null,
                    'scalarValue' => null,
                    'arrayValue'  => [1, 2, 3]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'entityfieldfallbackvalues'],
            $data
        );

        $valueId = (int)$this->getResourceId($response);

        $expectedResponseData = $data;
        $expectedResponseData['data']['id'] = (string)$valueId;
        $this->assertResponseContains($expectedResponseData, $response);

        /** @var EntityFieldFallbackValue $value */
        $value = $this->getEntityManager()->find(EntityFieldFallbackValue::class, $valueId);
        self::assertNull($value->getFallback());
        self::assertNull($value->getScalarValue());
        self::assertEquals([1, 2, 3], $value->getArrayValue());
    }

    public function testTryToCreateWithEmptyValue()
    {
        $data = [
            'data' => [
                'type'       => 'entityfieldfallbackvalues',
                'attributes' => [
                    'fallback'    => null,
                    'scalarValue' => null,
                    'arrayValue'  => null
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'entityfieldfallbackvalues'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'entity field fallback value constraint',
                'detail' => 'Either "fallback", "scalarValue" or "arrayValue" property should be specified.'
            ],
            $response
        );
    }

    public function testUpdate()
    {
        $valueId = $this->getReference('scalar_value')->getId();
        $data = [
            'data' => [
                'type'       => 'entityfieldfallbackvalues',
                'id'         => (string)$valueId,
                'attributes' => [
                    'scalarValue' => 'updated value'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'entityfieldfallbackvalues', 'id' => (string)$valueId],
            $data
        );

        $expectedResponseData = $data;
        $expectedResponseData['data']['attributes']['fallback'] = null;
        $expectedResponseData['data']['attributes']['arrayValue'] = null;
        $this->assertResponseContains($expectedResponseData, $response);

        /** @var EntityFieldFallbackValue $value */
        $value = $this->getEntityManager()->find(EntityFieldFallbackValue::class, $valueId);
        self::assertNull($value->getFallback());
        self::assertEquals('updated value', $value->getScalarValue());
        self::assertNull($value->getArrayValue());
    }

    public function testUpdateWithChangingTypeOfValue()
    {
        $valueId = $this->getReference('scalar_value')->getId();
        $data = [
            'data' => [
                'type'       => 'entityfieldfallbackvalues',
                'id'         => (string)$valueId,
                'attributes' => [
                    'scalarValue' => null,
                    'arrayValue'  => [10, 20]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'entityfieldfallbackvalues', 'id' => (string)$valueId],
            $data
        );

        $expectedResponseData = $data;
        $expectedResponseData['data']['attributes']['fallback'] = null;
        $this->assertResponseContains($expectedResponseData, $response);

        /** @var EntityFieldFallbackValue $value */
        $value = $this->getEntityManager()->find(EntityFieldFallbackValue::class, $valueId);
        self::assertNull($value->getFallback());
        self::assertNull($value->getScalarValue());
        self::assertEquals([10, 20], $value->getArrayValue());
    }

    public function testTryToUpdateWithChangingTypeOfValueButNotSettingOldValueToNull()
    {
        $valueId = $this->getReference('array_value')->getId();
        $data = [
            'data' => [
                'type'       => 'entityfieldfallbackvalues',
                'id'         => (string)$valueId,
                'attributes' => [
                    'scalarValue' => 'updated value'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'entityfieldfallbackvalues', 'id' => (string)$valueId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'entity field fallback value constraint',
                'detail' => 'Either "fallback", "scalarValue" or "arrayValue" property should be specified.'
            ],
            $response
        );
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'entityfieldfallbackvalues', 'id' => '<toString(@scalar_value->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'entityfieldfallbackvalues'],
            ['filter' => ['id' => '<toString(@scalar_value->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, POST');
    }
}
