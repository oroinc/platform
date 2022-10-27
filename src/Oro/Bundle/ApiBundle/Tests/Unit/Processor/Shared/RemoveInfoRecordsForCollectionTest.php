<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Metadata\TargetMetadataAccessorInterface;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\Shared\RemoveInfoRecordsForCollection;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class RemoveInfoRecordsForCollectionTest extends GetListProcessorTestCase
{
    /** @var RemoveInfoRecordsForCollection */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->processor = new RemoveInfoRecordsForCollection();
    }

    private function createAssociationMetadata(
        string $associationName,
        bool $isCollection = false
    ): AssociationMetadata {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setIsCollection($isCollection);
        $targetMetadata = new EntityMetadata('Test\Entity');
        $associationMetadata->setTargetMetadata($targetMetadata);
        $associationMetadata->getTargetMetadata()->addField(new FieldMetadata('id'));

        return $associationMetadata;
    }

    public function testProcessWithoutMetadata()
    {
        $data = [
            [
                'id'    => 1,
                'users' => [
                    11,
                    '_' => ['path' => 'users']
                ]
            ],
            '_' => ['path' => '']
        ];

        $this->context->setResult($data);
        $this->processor->process($this->context);
        self::assertEquals($data, $this->context->getResult());
        self::assertNull($this->context->getInfoRecords());
    }

    public function testProcess()
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->addField(new FieldMetadata('id'));
        $user = $metadata->addAssociation($this->createAssociationMetadata('user'))
            ->getTargetMetadata();
        $user->addAssociation($this->createAssociationMetadata('role'));
        $user->addAssociation($this->createAssociationMetadata('roles', true));
        $users = $metadata->addAssociation($this->createAssociationMetadata('users', true))
            ->getTargetMetadata();
        $users->addAssociation($this->createAssociationMetadata('role'));
        $users->addAssociation($this->createAssociationMetadata('roles', true));

        $data = [
            [
                'id'    => 1,
                'user'  => [
                    'id'    => 10,
                    'role'  => 100,
                    'roles' => [
                        101,
                        '_' => ['path' => '0.user.roles']
                    ]
                ],
                'users' => [
                    [
                        'id'    => 11,
                        'role'  => 110,
                        'roles' => [
                            111,
                            '_' => ['path' => '0.users.0.roles']
                        ]
                    ],
                    [
                        'id'    => 12,
                        'role'  => 120,
                        'roles' => [121]
                    ],
                    [
                        'id'    => 13,
                        'role'  => null,
                        'roles' => []
                    ],
                    '_' => ['path' => '0.users']
                ]
            ],
            '_' => ['path' => '']
        ];
        $expectedData = $data;
        unset(
            $expectedData['_'],
            $expectedData[0]['user']['roles']['_'],
            $expectedData[0]['users']['_'],
            $expectedData[0]['users'][0]['roles']['_']
        );
        $expectedInfoRecords = [
            ''                => ['path' => ''],
            '0.user.roles'    => ['path' => '0.user.roles'],
            '0.users'         => ['path' => '0.users'],
            '0.users.0.roles' => ['path' => '0.users.0.roles']
        ];

        $this->context->setResult($data);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertEquals($expectedData, $this->context->getResult());
        self::assertEquals($expectedInfoRecords, $this->context->getInfoRecords());
    }

    public function testProcessWhenInfoRecordsAlreadyContainData()
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->addField(new FieldMetadata('id'));

        $data = [
            ['id' => 1],
            '_' => ['path' => '']
        ];
        $expectedData = $data;
        unset($expectedData['_']);
        $expectedInfoRecords = [
            'someMetaInfo' => 123,
            ''             => ['path' => '']
        ];

        $this->context->addInfoRecord('someMetaInfo', 123);
        $this->context->setResult($data);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertEquals($expectedData, $this->context->getResult());
        self::assertEquals($expectedInfoRecords, $this->context->getInfoRecords());
    }

    public function testProcessForMultiTargetAssociation()
    {
        $targetMetadataAccessor = $this->createMock(TargetMetadataAccessorInterface::class);
        $metadata = new EntityMetadata(EntityIdentifier::class);
        $metadata->setTargetMetadataAccessor($targetMetadataAccessor);
        $metadata->addField(new FieldMetadata('id'));
        $typedMetadata = new EntityMetadata('Test\Entity');
        $typedMetadata->setTargetMetadataAccessor($targetMetadataAccessor);
        $typedMetadata->addField(new FieldMetadata('id'));
        $usersAssociation = $typedMetadata->addAssociation($this->createAssociationMetadata('users', true));
        $usersAssociation->setTargetMetadataAccessor($targetMetadataAccessor);
        $usersAssociation->setAssociationPath('users');
        $usersAssociation->setTargetClassName(EntityIdentifier::class);
        $user = new EntityMetadata('Test\Entity');
        $user->setTargetMetadataAccessor($targetMetadataAccessor);
        $user->addField(new FieldMetadata('id'));
        $rolesAssociation = $user->addAssociation($this->createAssociationMetadata('roles', true));
        $rolesAssociation->setTargetMetadataAccessor($targetMetadataAccessor);
        $rolesAssociation->setAssociationPath('users.roles');

        $targetMetadataAccessor->expects(self::exactly(2))
            ->method('getTargetMetadata')
            ->willReturnMap([
                ['Test\Company', null, $typedMetadata],
                ['Test\User', 'users', $user]
            ]);

        $data = [
            [
                ConfigUtil::CLASS_NAME => 'Test\Company',
                'id'                   => 1,
                'users'                => [
                    [
                        ConfigUtil::CLASS_NAME => 'Test\User',
                        'id'                   => 10,
                        'roles'                => [
                            101,
                            '_' => ['path' => '0.users.0.roles']
                        ]
                    ]
                ]
            ]
        ];
        $expectedData = $data;
        unset($expectedData[0]['users'][0]['roles']['_']);
        $expectedInfoRecords = [
            '0.users.0.roles' => ['path' => '0.users.0.roles']
        ];

        $this->context->setResult($data);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);
        self::assertEquals($expectedData, $this->context->getResult());
        self::assertEquals($expectedInfoRecords, $this->context->getInfoRecords());
    }
}
