<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\ComplexData;

use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataConverterInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataConverterRegistry;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor\ComplexDataConvertationDataAccessorInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\JsonApiImportConverter;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\Mapping\ComplexDataMappingProvider;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class JsonApiImportConverterTest extends TestCase
{
    private ComplexDataConvertationDataAccessorInterface&MockObject $dataAccessor;
    private JsonApiImportConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->dataAccessor = $this->createMock(ComplexDataConvertationDataAccessorInterface::class);

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
                        'external' => [
                            'target_path' => 'attributes.external',
                            'value' => true
                        ],
                        'user' => [
                            'target_path' => 'relationships.user.data',
                            'ref' => 'user'
                        ],
                        'lineItems' => [
                            'target_path' => 'relationships.lineItems.data',
                            'ref' => 'line_items'
                        ],
                        'status' => [
                            'target_path' => 'relationships.status.data',
                            'ref' => 'status'
                        ]
                    ]
                ],
                'user' => [
                    'target_type' => 'users',
                    'entity' => User::class,
                    'lookup_field' => 'email'
                ],
                'line_items' => [
                    'target_type' => 'lineitems',
                    'collection' => true,
                    'fields' => [
                        'organizationName' => [
                            'target_path' => 'attributes.organizationName'
                        ],
                        'quantity' => [
                            'target_path' => 'attributes.quantity'
                        ],
                        'value' => [
                            'target_path' => 'attributes.value'
                        ],
                        'organization' => [
                            'target_path' => 'relationships.organization.data',
                            'ref' => 'organization',
                            'source' => 'organizationName'
                        ]
                    ]
                ],
                'organization' => [
                    'target_type' => 'organizations',
                    'entity' => Organization::class,
                    'lookup_field' => 'sku'
                ],
                'status' => [
                    'target_type' => 'statuses',
                    'entity' => 'Extend\Entity\EV_Status',
                    'lookup_field' => 'name',
                    'ignore_not_found' => true
                ]
            ]);

        $this->converter = new JsonApiImportConverter(
            $mappingProvider,
            PropertyAccess::createPropertyAccessor(),
            $this->dataAccessor,
            'test_entity'
        );

        $converterRegistry = $this->createMock(ComplexDataConverterRegistry::class);
        $converterRegistry->expects(self::any())
            ->method('getConverterForEntity')
            ->willReturnCallback(function (string $entityName) {
                if ('test_entity' === $entityName) {
                    $converter = $this->createMock(ComplexDataConverterInterface::class);
                    $converter->expects(self::any())
                        ->method('convert')
                        ->willReturnCallback(function (array $item) {
                            $item['converted'] = true;

                            return $item;
                        });

                    return $converter;
                }

                return null;
            });
        $this->converter->setConverterRegistry($converterRegistry);
    }

    public function testConvert(): void
    {
        $user = new User();
        ReflectionUtil::setId($user, 1);
        $user->setEmail('test@example.com');
        $organization = new Organization();
        ReflectionUtil::setId($organization, 777);
        $organization->setName('Org 1');

        $this->dataAccessor->expects(self::any())
            ->method('findEntityId')
            ->willReturnMap([
                [User::class, 'email', $user->getEmail(), $user->getId()],
                [Organization::class, 'sku', $organization->getName(), $organization->getId()],
                ['Extend\Entity\EV_Status', 'name', 'in process', 'in_process']
            ]);

        $output = $this->converter->convert([
            'name' => 'Test Entity',
            'status' => 'in process',
            'user' => 'test@example.com',
            'lineItems' => [
                [
                    'organizationName' => 'Org 1',
                    'quantity' => 2,
                    'value' => 50.0
                ]
            ]
        ]);

        $outputJson = json_encode($output, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $outputJson = preg_replace('/tmp_[a-f0-9]{14}\.[a-f0-9]{8}/i', 'tmp_id', $outputJson);

        self::assertEquals(
            <<<JSON
{
    "data": {
        "type": "testentities",
        "attributes": {
            "name": "Test Entity",
            "external": true
        },
        "relationships": {
            "user": {
                "data": {
                    "type": "users",
                    "id": "1"
                }
            },
            "lineItems": {
                "data": [
                    {
                        "type": "lineitems",
                        "id": "tmp_id"
                    }
                ]
            },
            "status": {
                "data": {
                    "type": "statuses",
                    "id": "in_process"
                }
            }
        }
    },
    "included": [
        {
            "type": "lineitems",
            "id": "tmp_id",
            "attributes": {
                "organizationName": "Org 1",
                "quantity": 2,
                "value": 50
            },
            "relationships": {
                "organization": {
                    "data": {
                        "type": "organizations",
                        "id": "777"
                    }
                }
            }
        }
    ]
}
JSON,
            $outputJson
        );
    }

    public function testConvertWhenEntityNotFoundForEntityWithoutIgnoreNotFoundMappingOption(): void
    {
        $user = new User();
        ReflectionUtil::setId($user, 1);
        $user->setEmail('test@example.com');

        $this->dataAccessor->expects(self::any())
            ->method('findEntityId')
            ->willReturnMap([
                [User::class, 'email', $user->getEmail(), null],
                ['Extend\Entity\EV_Status', 'name', 'in process', 'in_process']
            ]);

        $output = $this->converter->convert([
            'name' => 'Test Entity',
            'status' => 'in process',
            'user' => 'test@example.com'
        ]);

        $outputJson = json_encode($output, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        self::assertEquals(
            <<<JSON
{
    "data": {
        "type": "testentities",
        "attributes": {
            "name": "Test Entity",
            "external": true
        },
        "relationships": {
            "status": {
                "data": {
                    "type": "statuses",
                    "id": "in_process"
                }
            }
        }
    },
    "errors": [
        {
            "title": "request data constraint",
            "detail": "The entity was not found.",
            "source": {
                "pointer": "\\/data\\/relationships\\/user\\/data"
            }
        }
    ]
}
JSON,
            $outputJson
        );
    }

    public function testConvertWhenEntityNotFoundForEntityWithIgnoreNotFoundMappingOption(): void
    {
        $user = new User();
        ReflectionUtil::setId($user, 1);
        $user->setEmail('test@example.com');

        $this->dataAccessor->expects(self::any())
            ->method('findEntityId')
            ->willReturnMap([
                [User::class, 'email', $user->getEmail(), $user->getId()],
                ['Extend\Entity\EV_Status', 'name', 'in process', null]
            ]);

        $output = $this->converter->convert([
            'name' => 'Test Entity',
            'status' => 'in process',
            'user' => 'test@example.com'
        ]);

        $outputJson = json_encode($output, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        self::assertEquals(
            <<<JSON
{
    "data": {
        "type": "testentities",
        "attributes": {
            "name": "Test Entity",
            "external": true
        },
        "relationships": {
            "user": {
                "data": {
                    "type": "users",
                    "id": "1"
                }
            }
        }
    }
}
JSON,
            $outputJson
        );
    }
}
