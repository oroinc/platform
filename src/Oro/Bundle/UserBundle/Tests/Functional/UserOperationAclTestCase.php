<?php

declare(strict_types=1);

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadBusinessUnitData;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserACLData;

/**
 * Runs back-office user operations as a user who may edit only the users of their own business unit.
 */
abstract class UserOperationAclTestCase extends WebTestCase
{
    use OperationAwareTestTrait;
    use RolePermissionExtension;

    protected const string CALLER = LoadUserACLData::SIMPLE_USER_ROLE_LOCAL;
    protected const string USER_OF_OWN_BUSINESS_UNIT = LoadUserACLData::SIMPLE_USER_2_ROLE_LOCAL;
    protected const string USER_OF_ANOTHER_BUSINESS_UNIT = LoadUserACLData::SIMPLE_USER_2_ROLE_LOCAL_BU2;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadUserACLData::class]);

        $this->updateRolePermissions(
            LoadUserACLData::ROLE_LOCAL,
            User::class,
            ['VIEW' => AccessLevel::LOCAL_LEVEL, 'EDIT' => AccessLevel::LOCAL_LEVEL]
        );
        // The caller belongs to BUSINESS_UNIT_1, so LOCAL access covers only the users owned by it.
        $this->getUser(self::USER_OF_OWN_BUSINESS_UNIT)
            ->setOwner($this->getReference(LoadBusinessUnitData::BUSINESS_UNIT_1));
        $this->getUser(self::USER_OF_ANOTHER_BUSINESS_UNIT)
            ->setOwner($this->getReference(LoadBusinessUnitData::BUSINESS_UNIT_2));
        $this->flush();

        $this->initClient([], self::generateBasicAuthHeader(self::CALLER, self::CALLER));
    }

    protected function getUser(string $userReference): User
    {
        return self::getContainer()->get('doctrine')->getRepository(User::class)
            ->find($this->getReference($userReference)->getId());
    }

    protected function flush(): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(User::class);
        $entityManager->flush();
        $entityManager->clear();
    }

    /**
     * Runs the operation from the users grid and returns the flash messages it added.
     */
    protected function executeOperation(string $operationName, string $userReference): array
    {
        $userId = $this->getReference($userReference)->getId();
        $this->client->request(
            'POST',
            $this->getUrl('oro_action_operation_execute', [
                'operationName' => $operationName,
                'entityId' => $userId,
                'entityClass' => User::class,
                'datagrid' => 'users-grid',
            ]),
            $this->getOperationExecuteParams($operationName, $userId, User::class, 'users-grid'),
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        // The response includes the flash messages only when the operation refreshes the grid.
        return self::jsonToArray($this->client->getResponse()->getContent())['flashMessages']
            ?? $this->client->getRequest()->getSession()->getFlashBag()->all();
    }
}
