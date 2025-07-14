<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

class RoleRepositoryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadUser::class]);
    }

    private function getRepository(): RoleRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Role::class);
    }

    public function testGetFirstMatchedUserByRoleNameReturnsAdministrator(): void
    {
        self::assertEquals(
            $this->getReference(LoadUser::USER),
            $this->getRepository()->getFirstMatchedUserByRoleName(LoadRolesData::ROLE_ADMINISTRATOR)
        );
    }

    public function testGetFirstMatchedUserByRoleNameWithOrganizationReturnsAdministrator(): void
    {
        $user = $this->getReference(LoadUser::USER);

        self::assertEquals(
            $user,
            $this->getRepository()->getFirstMatchedUserByRoleName(
                LoadRolesData::ROLE_ADMINISTRATOR,
                $user->getOrganization()
            )
        );
    }

    public function testGetFirstMatchedUserByRoleNameReturnsNothingWhenNoUser(): void
    {
        self::assertNull(
            $this->getRepository()->getFirstMatchedUserByRoleName(LoadRolesData::ROLE_MANAGER)
        );
    }
}
