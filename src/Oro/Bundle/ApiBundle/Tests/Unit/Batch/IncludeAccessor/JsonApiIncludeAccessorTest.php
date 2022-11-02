<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\IncludeAccessor;

use Oro\Bundle\ApiBundle\Batch\IncludeAccessor\JsonApiIncludeAccessor;
use Oro\Bundle\ApiBundle\Batch\ItemKeyBuilder;

class JsonApiIncludeAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JsonApiIncludeAccessor */
    private $includeAccessor;

    protected function setUp(): void
    {
        $this->includeAccessor = new JsonApiIncludeAccessor(new ItemKeyBuilder());
    }

    public function testGetPrimaryItemData()
    {
        $itemData = ['type' => 'accounts', 'id' => '1'];
        $item = ['jsonapi' => ['version' => '1.0'], 'data' => $itemData];
        self::assertSame($itemData, $this->includeAccessor->getPrimaryItemData($item));
    }

    public function testSetPrimaryItemData()
    {
        $item = ['jsonapi' => ['version' => '1.0'], 'data' => ['type' => 'accounts', 'id' => 'old']];
        $this->includeAccessor->setPrimaryItemData($item, ['type' => 'accounts', 'id' => 'new']);
        self::assertSame(
            ['jsonapi' => ['version' => '1.0'], 'data' => ['type' => 'accounts', 'id' => 'new']],
            $item
        );
    }

    public function testGetItemIdentifier()
    {
        self::assertSame(
            ['accounts', '1'],
            $this->includeAccessor->getItemIdentifier(['type' => 'accounts', 'id' => '1'])
        );
    }

    /**
     * @dataProvider getItemIdentifierWithInvalidDataProvider
     */
    public function testGetItemIdentifierWithInvalidData(array $item, string $expectedExceptionMessage)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->includeAccessor->getItemIdentifier($item);
    }

    public function getItemIdentifierWithInvalidDataProvider(): array
    {
        return [
            [[], "The 'type' property is required"],
            [['type' => 'accounts'], "The 'id' property is required"],
            [['type' => 'accounts', 'id' => null], "The 'id' property should not be null"],
            [['type' => 'accounts', 'id' => ''], "The 'id' property should not be blank"],
            [['id' => '1'], "The 'type' property is required"],
            [['type' => null, 'id' => '1'], "The 'type' property should not be null"],
            [['type' => '', 'id' => '1'], "The 'type' property should not be blank"],
            [['type' => 1, 'id' => '1'], "The 'type' property should be a string"],
            [['type' => 'accounts', 'id' => 1], "The 'id' property should be a string"]
        ];
    }

    /**
     * @dataProvider getRelationshipsProvider
     */
    public function testGetRelationships(array $item, array $expectedResult)
    {
        self::assertSame($expectedResult, $this->includeAccessor->getRelationships($item));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getRelationshipsProvider(): array
    {
        return [
            [
                ['type' => 'accounts'],
                []
            ],
            [
                ['type' => 'accounts', 'relationships' => []],
                []
            ],
            [
                ['type' => 'accounts', 'relationships' => 'invalid'],
                []
            ],
            [
                ['type' => 'accounts', 'relationships' => ['contact' => []]],
                []
            ],
            [
                ['type' => 'accounts', 'relationships' => ['contact' => 'invalid']],
                []
            ],
            [
                ['type' => 'accounts', 'relationships' => ['contacts' => [[]]]],
                []
            ],
            [
                ['type' => 'accounts', 'relationships' => ['contacts' => ['invalid']]],
                []
            ],
            [
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact1' => ['data' => ['type' => 'contacts']],
                        'contact2' => ['data' => ['type' => 'contacts', 'id' => null]],
                        'contact3' => ['data' => ['type' => 'contacts', 'id' => '']],
                        'contact4' => ['data' => ['id' => '1']],
                        'contact5' => ['data' => ['type' => null, 'id' => '1']],
                        'contact6' => ['data' => ['type' => '', 'id' => '1']],
                        'contact7' => ['data' => ['type' => 1, 'id' => '1']],
                        'contact8' => ['data' => ['type' => 'contacts', 'id' => 1]]
                    ]
                ],
                []
            ],
            [
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts1' => ['data' => [['type' => 'contacts']]],
                        'contacts2' => ['data' => [['type' => 'contacts', 'id' => null]]],
                        'contacts3' => ['data' => [['type' => 'contacts', 'id' => '']]],
                        'contacts4' => ['data' => [['id' => '1']]],
                        'contacts5' => ['data' => [['type' => null, 'id' => '1']]],
                        'contacts6' => ['data' => [['type' => '', 'id' => '1']]],
                        'contacts7' => ['data' => [['type' => 1, 'id' => '1']]],
                        'contacts8' => ['data' => [['type' => 'contacts', 'id' => 1]]]
                    ]
                ],
                []
            ],
            [
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact1' => ['data' => ['type' => 'contacts', 'id' => '1']],
                        'contact2' => ['data' => ['type' => 'contacts', 'id' => '2']],
                        'contact3' => ['data' => ['type' => 'contacts', 'id' => '1']]
                    ]
                ],
                ['contacts|1' => ['contacts', '1'], 'contacts|2' => ['contacts', '2']]
            ],
            [
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts1' => ['data' => [['type' => 'contacts', 'id' => '1']]],
                        'contacts2' => ['data' => [['type' => 'contacts', 'id' => '2']]],
                        'contacts3' => ['data' => [['type' => 'contacts', 'id' => '1']]]
                    ]
                ],
                ['contacts|1' => ['contacts', '1'], 'contacts|2' => ['contacts', '2']]
            ],
            [
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts1' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => '1'],
                                ['type' => 'contacts', 'id' => '2'],
                                ['type' => 'contacts', 'id' => '3']
                            ]
                        ],
                        'contacts2' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => '2'],
                                ['type' => 'contacts', 'id' => '4']
                            ]
                        ],
                        'contact1'  => [
                            'data' => ['type' => 'contacts', 'id' => '2']
                        ],
                        'contact2'  => [
                            'data' => ['type' => 'contacts', 'id' => '5']
                        ]
                    ]
                ],
                [
                    'contacts|1' => ['contacts', '1'],
                    'contacts|2' => ['contacts', '2'],
                    'contacts|3' => ['contacts', '3'],
                    'contacts|4' => ['contacts', '4'],
                    'contacts|5' => ['contacts', '5']
                ]
            ]
        ];
    }

    /**
     * @dataProvider updateRelationshipsProvider
     */
    public function testUpdateRelationships(array $item, array $expectedResult = null)
    {
        if (null === $expectedResult) {
            $expectedResult = $item;
        }
        $this->includeAccessor->updateRelationships(
            $item,
            function (string $type, string $id) {
                return sprintf('new id (%s|%s)', $type, $id);
            }
        );
        self::assertSame($expectedResult, $item);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function updateRelationshipsProvider(): array
    {
        return [
            [
                ['type' => 'accounts']
            ],
            [
                ['type' => 'accounts', 'relationships' => []]
            ],
            [
                ['type' => 'accounts', 'relationships' => 'invalid']
            ],
            [
                ['type' => 'accounts', 'relationships' => ['contact' => []]]
            ],
            [
                ['type' => 'accounts', 'relationships' => ['contact' => 'invalid']]
            ],
            [
                ['type' => 'accounts', 'relationships' => ['contacts' => [[]]]]
            ],
            [
                ['type' => 'accounts', 'relationships' => ['contacts' => ['invalid']]]
            ],
            [
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact1' => ['data' => ['type' => 'contacts']],
                        'contact2' => ['data' => ['type' => 'contacts', 'id' => null]],
                        'contact3' => ['data' => ['type' => 'contacts', 'id' => '']],
                        'contact4' => ['data' => ['id' => '1']],
                        'contact5' => ['data' => ['type' => null, 'id' => '1']],
                        'contact6' => ['data' => ['type' => '', 'id' => '1']],
                        'contact7' => ['data' => ['type' => 1, 'id' => '1']],
                        'contact8' => ['data' => ['type' => 'contacts', 'id' => 1]]
                    ]
                ]
            ],
            [
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts1' => ['data' => [['type' => 'contacts']]],
                        'contacts2' => ['data' => [['type' => 'contacts', 'id' => null]]],
                        'contacts3' => ['data' => [['type' => 'contacts', 'id' => '']]],
                        'contacts4' => ['data' => [['id' => '1']]],
                        'contacts5' => ['data' => [['type' => null, 'id' => '1']]],
                        'contacts6' => ['data' => [['type' => '', 'id' => '1']]],
                        'contacts7' => ['data' => [['type' => 1, 'id' => '1']]],
                        'contacts8' => ['data' => [['type' => 'contacts', 'id' => 1]]]
                    ]
                ]
            ],
            [
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact1' => ['data' => ['type' => 'contacts', 'id' => '1']],
                        'contact2' => ['data' => ['type' => 'contacts', 'id' => '2']],
                        'contact3' => ['data' => ['type' => 'contacts', 'id' => '1']]
                    ]
                ],
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contact1' => ['data' => ['type' => 'contacts', 'id' => 'new id (contacts|1)']],
                        'contact2' => ['data' => ['type' => 'contacts', 'id' => 'new id (contacts|2)']],
                        'contact3' => ['data' => ['type' => 'contacts', 'id' => 'new id (contacts|1)']]
                    ]
                ]
            ],
            [
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts1' => ['data' => [['type' => 'contacts', 'id' => '1']]],
                        'contacts2' => ['data' => [['type' => 'contacts', 'id' => '2']]],
                        'contacts3' => ['data' => [['type' => 'contacts', 'id' => '1']]]
                    ]
                ],
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts1' => ['data' => [['type' => 'contacts', 'id' => 'new id (contacts|1)']]],
                        'contacts2' => ['data' => [['type' => 'contacts', 'id' => 'new id (contacts|2)']]],
                        'contacts3' => ['data' => [['type' => 'contacts', 'id' => 'new id (contacts|1)']]]
                    ]
                ]
            ],
            [
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts1' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => '1'],
                                ['type' => 'contacts', 'id' => '2'],
                                ['type' => 'contacts', 'id' => '3']
                            ]
                        ],
                        'contacts2' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => '2'],
                                ['type' => 'contacts', 'id' => '4']
                            ]
                        ],
                        'contact1'  => [
                            'data' => ['type' => 'contacts', 'id' => '2']
                        ],
                        'contact2'  => [
                            'data' => ['type' => 'contacts', 'id' => '5']
                        ]
                    ]
                ],
                [
                    'type'          => 'accounts',
                    'relationships' => [
                        'contacts1' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => 'new id (contacts|1)'],
                                ['type' => 'contacts', 'id' => 'new id (contacts|2)'],
                                ['type' => 'contacts', 'id' => 'new id (contacts|3)']
                            ]
                        ],
                        'contacts2' => [
                            'data' => [
                                ['type' => 'contacts', 'id' => 'new id (contacts|2)'],
                                ['type' => 'contacts', 'id' => 'new id (contacts|4)']
                            ]
                        ],
                        'contact1'  => [
                            'data' => ['type' => 'contacts', 'id' => 'new id (contacts|2)']
                        ],
                        'contact2'  => [
                            'data' => ['type' => 'contacts', 'id' => 'new id (contacts|5)']
                        ]
                    ]
                ]
            ]
        ];
    }

    public function testUpdateRelationshipsWhenNewIdsNotFound()
    {
        $item = [
            'type'          => 'accounts',
            'relationships' => [
                'contacts1' => [
                    'data' => [
                        ['type' => 'contacts', 'id' => '1'],
                        ['type' => 'contacts', 'id' => '2'],
                        ['type' => 'contacts', 'id' => '3']
                    ]
                ],
                'contacts2' => [
                    'data' => [
                        ['type' => 'contacts', 'id' => '2'],
                        ['type' => 'contacts', 'id' => '4']
                    ]
                ],
                'contact1'  => [
                    'data' => ['type' => 'contacts', 'id' => '2']
                ],
                'contact2'  => [
                    'data' => ['type' => 'contacts', 'id' => '5']
                ]
            ]
        ];
        $expectedResult = $item;
        $this->includeAccessor->updateRelationships(
            $item,
            function (string $type, string $id) {
                return null;
            }
        );
        self::assertSame($expectedResult, $item);
    }
}
