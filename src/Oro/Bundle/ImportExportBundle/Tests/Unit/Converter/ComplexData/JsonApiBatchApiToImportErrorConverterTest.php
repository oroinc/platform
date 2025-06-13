<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\ComplexData;

use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataConverterRegistry;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataErrorConverterInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\JsonApiBatchApiToImportErrorConverter;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\Mapping\ComplexDataMappingProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class JsonApiBatchApiToImportErrorConverterTest extends TestCase
{
    private JsonApiBatchApiToImportErrorConverter $errorConverter;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    protected function setUp(): void
    {
        $mappingProvider = $this->createMock(ComplexDataMappingProvider::class);
        $mappingProvider->expects(self::any())
            ->method('getMapping')
            ->willReturn([
                'test_entity' => [
                    'target_type' => 'testentities',
                    'fields' => [
                        'name' => [
                            'target_path' => 'attributes.name'
                        ],
                        'email' => [
                            'target_path' => 'attributes.lowerCaseEmail'
                        ],
                        'roleName' => [
                            'target_path' => 'attributes.roleName'
                        ],
                        'role' => [
                            'target_path' => 'relationships.role.data',
                            'ref' => 'roles',
                            'source' => 'roleName'
                        ],
                        'organization' => [
                            'target_path' => 'relationships.organization.data',
                            'ref' => 'organization'
                        ],
                        'user' => [
                            'target_path' => 'relationships.userOwner.data',
                            'ref' => 'user'
                        ],
                        'organizations' => [
                            'target_path' => 'relationships.organizations.data',
                            'ref' => 'organizations'
                        ],
                        'groups' => [
                            'target_path' => 'relationships.accessGroups.data',
                            'ref' => 'groups'
                        ]
                    ]
                ],
                'organization' => [
                    'target_type' => 'organizations',
                    'lookup_field' => 'name'
                ],
                'organizations' => [
                    'target_type' => 'organizations',
                    'lookup_field' => 'name',
                    'collection' => true
                ],
                'user' => [
                    'target_type' => 'users',
                    'fields' => [
                        'name' => [
                            'target_path' => 'attributes.name'
                        ],
                        'email' => [
                            'target_path' => 'attributes.lowerCaseEmail'
                        ],
                        'organizations' => [
                            'target_path' => 'relationships.organizations.data',
                            'ref' => 'organizations'
                        ],
                        'groups' => [
                            'target_path' => 'relationships.accessGroups.data',
                            'ref' => 'groups'
                        ]
                    ]
                ],
                'groups' => [
                    'target_type' => 'groups',
                    'collection' => true,
                    'fields' => [
                        'name' => [
                            'target_path' => 'attributes.name'
                        ],
                        'email' => [
                            'target_path' => 'attributes.lowerCaseEmail'
                        ]
                    ]
                ],
                'roles' => [
                    'target_type' => 'roles',
                    'lookup_field' => 'name'
                ]
            ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function (string $id, array $parameters) {
                if ('oro.importexport.import.error %number%' === $id) {
                    return '#' . $parameters['%number%'] . '.';
                }

                return $id . ' (translated)';
            });

        $this->errorConverter = new JsonApiBatchApiToImportErrorConverter(
            $mappingProvider,
            $translator,
            'test_entity'
        );

        $converterRegistry = $this->createMock(ComplexDataConverterRegistry::class);
        $converterRegistry->expects(self::any())
            ->method('getErrorConverterForEntity')
            ->willReturnCallback(function (string $entityType) {
                $converter = $this->createMock(ComplexDataErrorConverterInterface::class);
                $converter->expects(self::any())
                    ->method('convertError')
                    ->willReturnCallback(function (string $error, ?string $propertyPath) use ($entityType) {
                        return
                            $error
                            . \sprintf(
                                ' (converted, entity type: %s%s)',
                                $entityType,
                                $propertyPath ? ', property path: ' . $propertyPath : ''
                            );
                    });

                return $converter;
            });
        $this->errorConverter->setConverterRegistry($converterRegistry);
    }

    private static function createError(int $itemIndex, ?string $detail, ?string $pointer = null): BatchError
    {
        $error = BatchError::create('error title', $detail);
        $error->setItemIndex($itemIndex);
        if (null !== $pointer) {
            $error->setSource(ErrorSource::createByPointer($pointer));
        }

        return $error;
    }

    /**
     * @dataProvider convertToImportErrorDataProvider
     */
    public function testConvertToImportError(BatchError $error, string $expectedError): void
    {
        $requestData = [
            'data' => [
                [
                    'type' => 'testentities',
                    'attributes' => [
                        'name' => 'test entity 1',
                        'email' => 'test_entity_1@example.com',
                        'roleName' => 'Role 1'
                    ],
                    'relationships' => [
                        'role' => [
                            'data' => ['type' => 'roles', 'id' => 'role_1']
                        ],
                        'organization' => [
                            'data' => ['type' => 'organizations', 'id' => 'organization_1']
                        ],
                        'userOwner' => [
                            'data' => ['type' => 'users', 'id' => 'user_1']
                        ],
                        'organizations' => [
                            'data' => [
                                ['type' => 'organizations', 'id' => 'organization_1']
                            ]
                        ],
                        'accessGroups' => [
                            'data' => [
                                ['type' => 'groups', 'id' => 'group_1']
                            ]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'users',
                    'id' => 'user_1',
                    'attributes' => [
                        'email' => 'user_1@example.com'
                    ],
                    'relationships' => [
                        'organizations' => [
                            'data' => [
                                ['type' => 'organizations', 'id' => 'organization_2']
                            ]
                        ],
                        'accessGroups' => [
                            'data' => [
                                ['type' => 'groups', 'id' => 'group_2']
                            ]
                        ]
                    ]
                ],
                [
                    'type' => 'groups',
                    'id' => 'group_1',
                    'attributes' => [
                        'name' => 'Group 1',
                        'email' => 'group_1@example.com'
                    ]
                ],
                [
                    'type' => 'groups',
                    'id' => 'group_2',
                    'attributes' => [
                        'name' => 'Group 2',
                        'email' => 'group_2@example.com'
                    ]
                ]
            ]
        ];

        self::assertEquals(
            $expectedError,
            $this->errorConverter->convertToImportError($error, $requestData)
        );
    }

    public static function convertToImportErrorDataProvider(): array
    {
        return [
            'primary entity' => [
                self::createError(0, 'error'),
                '#1. error (converted, entity type: test_entity)'
            ],
            'primary entity attribute' => [
                self::createError(0, 'error', '/data/0/attributes/name'),
                '#1. name: error (converted, entity type: test_entity, property path: name)'
            ],
            'primary entity renamed attribute' => [
                self::createError(0, 'error', '/data/0/attributes/lowerCaseEmail'),
                '#1. email: error (converted, entity type: test_entity, property path: email)'
            ],
            'primary entity relationship' => [
                self::createError(0, 'error', '/data/0/relationships/organization/data'),
                '#1. organization: error (converted, entity type: test_entity, property path: organization)'
            ],
            'primary entity renamed relationship' => [
                self::createError(0, 'error', '/data/0/relationships/userOwner/data'),
                '#1. user: error (converted, entity type: test_entity, property path: user)'
            ],
            'included entity attribute (to many association)' => [
                self::createError(0, 'error', '/included/1/attributes/name'),
                '#1. groups.0.name: error (converted, entity type: groups, property path: name)'
            ],
            'included entity renamed attribute (to many association)' => [
                self::createError(0, 'error', '/included/1/attributes/lowerCaseEmail'),
                '#1. groups.0.email: error (converted, entity type: groups, property path: email)'
            ],
            'primary entity relationship with source config' => [
                self::createError(0, 'error', '/data/0/relationships/role/data'),
                '#1. roleName: error (converted, entity type: test_entity, property path: roleName)'
            ],
            'included entity' => [
                self::createError(0, 'error', '/included/0'),
                '#1. user: error (converted, entity type: user)'
            ],
            'included entity attribute' => [
                self::createError(0, 'error', '/included/0/attributes/name'),
                '#1. user.name: error (converted, entity type: user, property path: name)'
            ],
            'included entity renamed attribute' => [
                self::createError(0, 'error', '/included/0/attributes/lowerCaseEmail'),
                '#1. user.email: error (converted, entity type: user, property path: email)'
            ],
            'included entity relationship' => [
                self::createError(0, 'error', '/included/0/relationships/organizations/data'),
                '#1. user.organizations: error (converted, entity type: user, property path: organizations)'
            ],
            'included entity renamed relationship' => [
                self::createError(0, 'error', '/included/0/relationships/accessGroups/data'),
                '#1. user.groups: error (converted, entity type: user, property path: groups)'
            ],
            'nested included entity attribute' => [
                self::createError(0, 'error', '/included/2/attributes/name'),
                '#1. user.groups.0.name: error (converted, entity type: groups, property path: name)'
            ],
            'nested included entity renamed attribute' => [
                self::createError(0, 'error', '/included/2/attributes/lowerCaseEmail'),
                '#1. user.groups.0.email: error (converted, entity type: groups, property path: email)'
            ]
        ];
    }

    public function testConvertToImportErrorWithRowIndex(): void
    {
        $requestData = [
            'data' => [
                [
                    'type' => 'testentities',
                    'attributes' => [
                        'name' => 'test entity 1'
                    ]
                ]
            ]
        ];

        self::assertEquals(
            '#5. name: error (converted, entity type: test_entity, property path: name)',
            $this->errorConverter->convertToImportError(
                self::createError(0, 'error', '/data/0/attributes/name'),
                $requestData,
                4
            )
        );
    }
}
