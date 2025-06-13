<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Processor\ComplexData;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataConverterRegistry;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ComplexDataReverseConverterInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor\ComplexDataConvertationDataAccessor;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\DataAccessor\ComplexDataConvertationEntityLoaderInterface;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\Mapping\ComplexDataMappingProvider;
use Oro\Bundle\ImportExportBundle\Converter\ComplexData\ValueTransformer\ComplexDataValueTransformerInterface;
use Oro\Bundle\ImportExportBundle\Processor\ComplexData\ComplexDataExportProcessor;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures\TestEntity;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ComplexDataExportProcessorTest extends TestCase
{
    private ComplexDataExportProcessor $processor;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    protected function setUp(): void
    {
        $mappingProvider = $this->createMock(ComplexDataMappingProvider::class);
        $mappingProvider->expects(self::once())
            ->method('getMapping')
            ->willReturn([
                'test_entity' => [
                    'fields' => [
                        'id' => [
                            'target_path' => 'attributes.id'
                        ],
                        'organization' => [
                            'target_path' => 'relationships.organization.data',
                            'ref' => 'organization'
                        ],
                        'user' => [
                            'target_path' => 'relationships.user.data',
                            'entity_path' => 'userOwner',
                            'ref' => 'user'
                        ],
                        'businessUnit' => [
                            'target_path' => 'relationships.businessUnit.data',
                            'entity_path' => 'businessUnitOwner.name'
                        ]
                    ]
                ],
                'organization' => [
                    'lookup_field' => 'name'
                ],
                'organizations' => [
                    'lookup_field' => 'name',
                    'collection' => true
                ],
                'user' => [
                    'fields' => [
                        'enabled' => [
                            'target_path' => 'attributes.enabled',
                            'value' => false
                        ],
                        'authStatus' => [
                            'target_path' => 'relationships.authStatus.data',
                            'ref' => 'auth_status'
                        ],
                        'email' => [
                            'target_path' => 'attributes.email'
                        ],
                        'firstName' => [
                            'target_path' => 'attributes.firstName'
                        ],
                        'lastName' => [
                            'target_path' => 'attributes.lastName'
                        ],
                        'birthday' => [
                            'target_path' => 'attributes.birthday',
                            'entity_data_type' => 'date'
                        ],
                        'organizations' => [
                            'target_path' => 'relationships.organizations.data',
                            'ref' => 'organizations'
                        ],
                        'groups' => [
                            'target_path' => 'relationships.groups.data',
                            'ref' => 'groups'
                        ]
                    ]
                ],
                'auth_status' => [
                    'target_type' => 'authstatuses',
                    'entity' => 'Extend\Entity\EV_Auth_Status',
                    'lookup_field' => 'name'
                ],
                'groups' => [
                    'collection' => true,
                    'fields' => [
                        'name' => [
                            'target_path' => 'attributes.name'
                        ]
                    ]
                ]
            ]);

        $valueTransformer = $this->createMock(ComplexDataValueTransformerInterface::class);
        $valueTransformer->expects(self::any())
            ->method('transformValue')
            ->willReturnCallback(function ($value, $dataType) {
                if (null === $value) {
                    return null;
                }

                switch ($dataType) {
                    case 'datetime':
                        return $value->format('Y-m-d H:i:s');
                    case 'date':
                        return $value->format('Y-m-d');
                }

                return $value;
            });

        $enumOptionsProvider = $this->createMock(EnumOptionsProvider::class);
        $enumOptionsProvider->expects(self::any())
            ->method('getEnumInternalChoices')
            ->with('auth_status')
            ->willReturn(['enabled' => 'Enabled', 'disabled' => 'Disabled']);

        $this->processor = new ComplexDataExportProcessor(
            $mappingProvider,
            new ComplexDataConvertationDataAccessor(
                $this->createMock(DoctrineHelper::class),
                PropertyAccess::createPropertyAccessor(),
                $this->createMock(ComplexDataConvertationEntityLoaderInterface::class),
                $enumOptionsProvider
            ),
            $valueTransformer,
            'test_entity'
        );

        $converterRegistry = $this->createMock(ComplexDataConverterRegistry::class);
        $converterRegistry->expects(self::any())
            ->method('getReverseConverterForEntity')
            ->willReturnCallback(function (string $entityType) {
                if ('user' === $entityType) {
                    $converter = $this->createMock(ComplexDataReverseConverterInterface::class);
                    $converter->expects(self::any())
                        ->method('reverseConvert')
                        ->willReturnCallback(function (array $item) {
                            $item['additionalField'] = $item['firstName'] ? 'test' : null;

                            return $item;
                        });

                    return $converter;
                }

                return null;
            });
        $this->processor->setConverterRegistry($converterRegistry);
    }

    public function testProcess(): void
    {
        $entity = new TestEntity();
        $entity->setId(123);
        $organization1 = new Organization();
        $organization1->setName('Org 1');
        $organization2 = new Organization();
        $organization2->setName('Org 2');
        $entity->setOrganization($organization1);
        $user = new UserStub();
        $user->setAuthStatus(new TestEnumValue('auth_status', 'Enabled', 'enabled'));
        $user->setEmail('user@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setBirthday(new \DateTime('2025-01-02 10:11:12', new \DateTimeZone('UTC')));
        $user->addOrganization($organization1);
        $user->addOrganization($organization2);
        $group1 = new Group();
        $group1->setName('Group 1');
        $group2 = new Group();
        $group2->setName('Group 2');
        $user->addGroup($group1);
        $user->addGroup($group2);
        $entity->setUserOwner($user);
        $businessUnit = new BusinessUnit();
        $businessUnit->setName('Business Unit');
        $entity->setBusinessUnitOwner($businessUnit);

        self::assertSame(
            [
                'id' => 123,
                'organization' => 'Org 1',
                'user' => [
                    'authStatus' => 'Enabled',
                    'email' => 'user@example.com',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'birthday' => '2025-01-02',
                    'organizations' => ['Org 1', 'Org 2'],
                    'groups' => [
                        ['name' => 'Group 1'],
                        ['name' => 'Group 2']
                    ],
                    'additionalField' => 'test'
                ],
                'businessUnit' => 'Business Unit'
            ],
            $this->processor->process($entity)
        );
    }

    public function testProcessWithEmptyData(): void
    {
        $entity = new TestEntity();
        $entity->setId(123);
        $user = new User();
        $user->setEmail('user@example.com');
        $entity->setUserOwner($user);

        self::assertSame(
            [
                'id' => 123,
                'organization' => null,
                'user' => [
                    'authStatus' => null,
                    'email' => 'user@example.com',
                    'firstName' => null,
                    'lastName' => null,
                    'birthday' => null,
                    'organizations' => [],
                    'groups' => [],
                    'additionalField' => null
                ],
                'businessUnit' => null
            ],
            $this->processor->process($entity)
        );
    }
}
