<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Processor\Shared\RemoveInfoRecordsForCollection;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class RemoveInfoRecordsForCollectionTest extends GetListProcessorTestCase
{
    /** @var RemoveInfoRecordsForCollection */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new RemoveInfoRecordsForCollection();
    }

    /**
     * @param string $associationName
     * @param bool   $isCollection
     *
     * @return AssociationMetadata
     */
    private function createAssociationMetadata($associationName, $isCollection = false)
    {
        $associationMetadata = new AssociationMetadata();
        $associationMetadata->setName($associationName);
        $associationMetadata->setIsCollection($isCollection);
        $targetMetadata = new EntityMetadata();
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
        $metadata = new EntityMetadata();
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
}
