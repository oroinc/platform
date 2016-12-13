<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Entity\TestDefaultAndNull;

class RestJsonApiNotNullAndNotBlankTest extends DefaultAndNullTestCase
{
    public function testCreateShouldCheckNotBlankConstraintIfValueIsNotSpecified()
    {
        $data = [
            'data' => [
                'attributes' => [
                    'withNotNull' => 'value',
                ],
            ]
        ];

        $response = $this->sendCreateRequest($data, 400);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not blank constraint',
                        'detail' => 'This value should not be blank.',
                        'source' => [
                            'pointer' => '/data/attributes/withNotBlank'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testUpdateShouldNotCheckNotBlankConstraintIfValueIsNotSpecified()
    {
        $entity = new TestDefaultAndNull();
        $this->saveTestEntity($entity);

        $data = [
            'data' => [
                'attributes' => [
                    'withNotNull' => 'value',
                ],
            ]
        ];

        $this->sendUpdateRequest($entity->id, $data);
    }

    public function testCreateShouldCheckNotBlankConstraintIfValueIsBlank()
    {
        $data = [
            'data' => [
                'attributes' => [
                    'withNotBlank' => '',
                    'withNotNull'  => 'value',
                ],
            ]
        ];

        $response = $this->sendCreateRequest($data, 400);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not blank constraint',
                        'detail' => 'This value should not be blank.',
                        'source' => [
                            'pointer' => '/data/attributes/withNotBlank'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testUpdateShouldCheckNotBlankConstraintIfValueIsBlank()
    {
        $entity = new TestDefaultAndNull();
        $this->saveTestEntity($entity);

        $data = [
            'data' => [
                'attributes' => [
                    'withNotBlank' => '',
                    'withNotNull'  => 'value',
                ],
            ]
        ];

        $response = $this->sendUpdateRequest($entity->id, $data, 400);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not blank constraint',
                        'detail' => 'This value should not be blank.',
                        'source' => [
                            'pointer' => '/data/attributes/withNotBlank'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testCreateShouldCheckNotBlankConstraintIfValueIsBlankForFieldWithDefaultValue()
    {
        $data = [
            'data' => [
                'attributes' => [
                    'withDefaultValueAndNotBlank' => '',
                    'withNotBlank'                => 'value',
                    'withNotNull'                 => 'value',
                ],
            ]
        ];

        $response = $this->sendCreateRequest($data, 400);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not blank constraint',
                        'detail' => 'This value should not be blank.',
                        'source' => [
                            'pointer' => '/data/attributes/withDefaultValueAndNotBlank'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testUpdateShouldCheckNotBlankConstraintIfValueIsBlankForFieldWithDefaultValue()
    {
        $entity = new TestDefaultAndNull();
        $this->saveTestEntity($entity);

        $data = [
            'data' => [
                'attributes' => [
                    'withDefaultValueAndNotBlank' => '',
                ],
            ]
        ];

        $response = $this->sendUpdateRequest($entity->id, $data, 400);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not blank constraint',
                        'detail' => 'This value should not be blank.',
                        'source' => [
                            'pointer' => '/data/attributes/withDefaultValueAndNotBlank'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testCreateShouldCheckNotNullConstraintIfValueIsNotSpecified()
    {
        $data = [
            'data' => [
                'attributes' => [
                    'withNotBlank' => 'value',
                ],
            ]
        ];

        $response = $this->sendCreateRequest($data, 400);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => [
                            'pointer' => '/data/attributes/withNotNull'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testUpdateShouldNotCheckNotNullConstraintIfValueIsNotSpecified()
    {
        $entity = new TestDefaultAndNull();
        $this->saveTestEntity($entity);

        $data = [
            'data' => [
                'attributes' => [
                    'withNotBlank' => 'value',
                ],
            ]
        ];

        $this->sendUpdateRequest($entity->id, $data);
    }

    public function testCreateShouldCheckNotNullConstraintIfValueIsNull()
    {
        $data = [
            'data' => [
                'attributes' => [
                    'withNotNull'  => null,
                    'withNotBlank' => 'value',
                ],
            ]
        ];

        $response = $this->sendCreateRequest($data, 400);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => [
                            'pointer' => '/data/attributes/withNotNull'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testUpdateShouldCheckNotNullConstraintIfValueIsNull()
    {
        $entity = new TestDefaultAndNull();
        $this->saveTestEntity($entity);

        $data = [
            'data' => [
                'attributes' => [
                    'withNotNull'  => null,
                    'withNotBlank' => 'value',
                ],
            ]
        ];

        $response = $this->sendUpdateRequest($entity->id, $data, 400);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => [
                            'pointer' => '/data/attributes/withNotNull'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testCreateShouldCheckNotNullConstraintIfValueIsNullForFieldWithDefaultValue()
    {
        $data = [
            'data' => [
                'attributes' => [
                    'withDefaultValueAndNotNull' => null,
                    'withNotBlank'               => 'value',
                    'withNotNull'                => 'value',
                ],
            ]
        ];

        $response = $this->sendCreateRequest($data, 400);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => [
                            'pointer' => '/data/attributes/withDefaultValueAndNotNull'
                        ]
                    ]
                ]
            ],
            $result
        );
    }

    public function testUpdateShouldCheckNotNullConstraintIfValueIsNullForFieldWithDefaultValue()
    {
        $entity = new TestDefaultAndNull();
        $this->saveTestEntity($entity);

        $data = [
            'data' => [
                'attributes' => [
                    'withDefaultValueAndNotNull' => null,
                ],
            ]
        ];

        $response = $this->sendUpdateRequest($entity->id, $data, 400);

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(
            [
                'errors' => [
                    [
                        'status' => '400',
                        'title'  => 'not null constraint',
                        'detail' => 'This value should not be null.',
                        'source' => [
                            'pointer' => '/data/attributes/withDefaultValueAndNotNull'
                        ]
                    ]
                ]
            ],
            $result
        );
    }
}
