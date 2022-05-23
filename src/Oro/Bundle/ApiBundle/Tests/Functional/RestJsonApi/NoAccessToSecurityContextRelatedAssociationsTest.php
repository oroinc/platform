<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityWithUserOwnership;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class NoAccessToSecurityContextRelatedAssociationsTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadOrganization::class, LoadUser::class]);
    }

    private function denyAccess(string $entityClass): void
    {
        $this->updateRolePermissions('ROLE_ADMINISTRATOR', $entityClass, ['VIEW' => AccessLevel::NONE_LEVEL]);
    }

    private function getCurrentOrganizationId(): int
    {
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        return $organization->getId();
    }

    private function getCurrentUserId(): int
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        return $user->getId();
    }

    private function getCurrentBusinessUnitId(): int
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        return $user->getBusinessUnits()->first()->getId();
    }

    public function testCreateWhenNoAccessToCurrentOrganization(): void
    {
        $this->denyAccess(Organization::class);

        $entityType = $this->getEntityType(TestEntityWithUserOwnership::class);
        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'       => $entityType,
                    'attributes' => [
                        'name' => 'New'
                    ]
                ]
            ]
        );
        $entityId = (int)$this->getResourceId($response);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => (string)$entityId,
                    'attributes'    => [
                        'name' => 'New'
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => null
                        ]
                    ]
                ],
            ],
            $response
        );
        /** @var TestEntityWithUserOwnership $entity */
        $entity = $this->getEntityManager()->find(TestEntityWithUserOwnership::class, $entityId);
        self::assertNotNull($entity->getOrganization());
    }

    public function testTryToCreateWhenNoAccessToCurrentOrganizationWhenItIsSubmittedInRequest(): void
    {
        $this->denyAccess(Organization::class);

        $entityType = $this->getEntityType(TestEntityWithUserOwnership::class);
        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'          => $entityType,
                    'attributes'    => [
                        'name' => 'New'
                    ],
                    'relationships' => [
                        'organization' => [
                            'data' => [
                                'type' => 'organizations',
                                'id'   => (string)$this->getCurrentOrganizationId()
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access granted constraint',
                'detail' => 'The "VIEW" permission is denied for the related resource.',
                'source' => ['pointer' => '/data/relationships/organization/data']
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testCreateWhenNoAccessToCurrentUser(): void
    {
        $this->denyAccess(User::class);

        $entityType = $this->getEntityType(TestEntityWithUserOwnership::class);
        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'       => $entityType,
                    'attributes' => [
                        'name' => 'New'
                    ]
                ]
            ]
        );
        $entityId = (int)$this->getResourceId($response);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => (string)$entityId,
                    'attributes'    => [
                        'name' => 'New'
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => null
                        ]
                    ]
                ],
            ],
            $response
        );
        /** @var TestEntityWithUserOwnership $entity */
        $entity = $this->getEntityManager()->find(TestEntityWithUserOwnership::class, $entityId);
        self::assertNotNull($entity->getOwner());
    }

    public function testTryToCreateWhenNoAccessToCurrentUserWhenItIsSubmittedInRequest(): void
    {
        $this->denyAccess(User::class);

        $entityType = $this->getEntityType(TestEntityWithUserOwnership::class);
        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'          => $entityType,
                    'attributes'    => [
                        'name' => 'New'
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => [
                                'type' => 'users',
                                'id'   => (string)$this->getCurrentUserId()
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access granted constraint',
                'detail' => 'The "VIEW" permission is denied for the related resource.',
                'source' => ['pointer' => '/data/relationships/owner/data']
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testCreateWhenNoAccessToCurrentBusinessUnit(): void
    {
        $this->denyAccess(BusinessUnit::class);

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'       => $entityType,
                    'attributes' => [
                        'title' => 'New'
                    ]
                ]
            ]
        );
        $entityId = (int)$this->getResourceId($response);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => $entityType,
                    'id'            => (string)$entityId,
                    'attributes'    => [
                        'title' => 'New'
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => null
                        ]
                    ]
                ],
            ],
            $response
        );
        /** @var TestDepartment $entity */
        $entity = $this->getEntityManager()->find(TestDepartment::class, $entityId);
        self::assertNotNull($entity->getOwner());
    }

    public function testTryToCreateWhenNoAccessToCurrentBusinessUnitWhenItIsSubmittedInRequest(): void
    {
        $this->denyAccess(BusinessUnit::class);

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->post(
            ['entity' => $entityType],
            [
                'data' => [
                    'type'          => $entityType,
                    'attributes'    => [
                        'title' => 'New'
                    ],
                    'relationships' => [
                        'owner' => [
                            'data' => [
                                'type' => 'businessunits',
                                'id'   => (string)$this->getCurrentBusinessUnitId()
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'access granted constraint',
                'detail' => 'The "VIEW" permission is denied for the related resource.',
                'source' => ['pointer' => '/data/relationships/owner/data']
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
