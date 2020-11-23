<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadCommandUserCreateUpdateData;

class UserCreateUpdateTest extends WebTestCase
{
    /** @var UserManager */
    private $userManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCommandUserCreateUpdateData::class]);

        $this->userManager = self::getContainer()->get('oro_user.manager');
    }

    public function testCreateUserWithAllParameters()
    {
        $arguments = [
            '--user-business-unit' => 'bu1',
            '--user-name'          => 'test_user_1',
            '--user-email'         => 'test_user_1@mailinator.com',
            '--user-firstname'     => 'User1F',
            '--user-lastname'      => 'User1L',
            '--user-password'      => 'admin'
        ];
        $arguments[] = '--user-organizations=org2';
        $arguments[] = '--user-organizations=org3';

        $output = self::runCommand('oro:user:create', $arguments);
        self::assertEmpty($output);

        /** @var User $user */
        $user = $this->userManager->findUserByUsername('test_user_1');
        self::assertTrue($user->isEnabled());
        self::assertEquals('test_user_1@mailinator.com', $user->getEmail());
        self::assertEquals('User1F', $user->getFirstName());
        self::assertEquals('User1L', $user->getLastName());
        self::assertEquals($this->getReference('bu1'), $user->getOwner());
        self::assertContains($this->getReference('bu1'), $user->getBusinessUnits());
        $userOrganizations = $user->getOrganizations();
        self::assertContains($this->getReference('org1'), $userOrganizations);
        self::assertContains($this->getReference('org2'), $userOrganizations);
        self::assertContains($this->getReference('org3'), $userOrganizations);
        self::assertEquals($this->getReference('org1'), $user->getOrganization());
    }

    /**
     * @depends testCreateUserWithAllParameters
     */
    public function testUpdate()
    {
        $arguments = [
            '--user-name'      => 'test_user_2',
            '--user-email'     => 'test_user_2@mailinator.com',
            '--user-firstname' => 'User2F',
            '--user-lastname'  => 'User2L'
        ];
        $arguments[] = '--user-organizations=org1';
        $arguments[] = 'test_user_1';

        $output = self::runCommand('oro:user:update', $arguments);
        self::assertEmpty($output);

        /** @var User $user */
        $user = $this->userManager->findUserByUsername('test_user_2');
        self::assertTrue($user->isEnabled());
        self::assertEquals('test_user_2@mailinator.com', $user->getEmail());
        self::assertEquals('User2F', $user->getFirstName());
        self::assertEquals('User2L', $user->getLastName());
        $userOrganizations = $user->getOrganizations();
        self::assertContains($this->getReference('org1'), $userOrganizations);
        self::assertContains($this->getReference('org2'), $userOrganizations);
        self::assertContains($this->getReference('org3'), $userOrganizations);
    }

    public function testTryToCreateUserWithWrongBusinessUnit()
    {
        $arguments = [
            '--user-business-unit' => 'invalid_business_unit_123o',
            '--user-name'          => 'test_user_1',
            '--user-email'         => 'test_user_2@mailinator.com',
            '--user-firstname'     => 'User1F',
            '--user-lastname'      => 'User1L',
            '--user-password'      => 'admin'
        ];

        $output = self::runCommand('oro:user:create', $arguments);
        self::assertEquals('Invalid Business Unit', $output);
    }

    public function testTryToCreateUserWithWrongOrganization()
    {
        $arguments = [
            '--user-business-unit' => 'bu1',
            '--user-name'          => 'new_user_2',
            '--user-email'         => 'test_user_2@mailinator.com',
            '--user-firstname'     => 'User1F',
            '--user-lastname'      => 'User1L',
            '--user-password'      => 'admin'
        ];
        $arguments[] = '--user-organizations=invalid_user_organization_123o';

        $output = self::runCommand('oro:user:create', $arguments);
        self::assertEquals(
            'Invalid organization "invalid_user_organization_123o" in "--user-organizations" parameter',
            $output
        );
    }

    public function testTryToCreateUserWithWrongRole()
    {
        $arguments = [
            '--user-business-unit' => 'bu1',
            '--user-name'          => 'new_user_2',
            '--user-email'         => 'test_user_2@mailinator.com',
            '--user-firstname'     => 'User1F',
            '--user-lastname'      => 'User1L',
            '--user-password'      => 'admin'
        ];
        $arguments[] = '--user-role=invalid_user_role_123o';

        $output = self::runCommand('oro:user:create', $arguments);
        self::assertEquals(
            'Invalid Role',
            $output
        );
    }

    public function testTryToCreateUserWithoutBusinessUnit()
    {
        $arguments = [
            '--user-name'     => 'new_user_2',
            '--user-email'    => 'test_user_2@mailinator.com',
            '--user-password' => 'admin'
        ];

        $output = self::runCommand('oro:user:create', $arguments);
        self::assertEquals(
            '--user-business-unit option required',
            $output
        );
    }

    public function testTryToCreateUserWithoutUsername()
    {
        $arguments = [
            '--user-business-unit' => 'bu1',
            '--user-email'         => 'test_user_2@mailinator.com',
            '--user-password'      => 'admin'
        ];

        $output = self::runCommand('oro:user:create', $arguments);
        self::assertEquals(
            '--user-name option required',
            $output
        );
    }

    public function testTryToCreateUserWithoutEmail()
    {
        $arguments = [
            '--user-business-unit' => 'bu1',
            '--user-name'          => 'test_user_2',
            '--user-password'      => 'admin'
        ];

        $output = self::runCommand('oro:user:create', $arguments);
        self::assertEquals(
            '--user-email option required',
            $output
        );
    }

    public function testCreateUserWithoutOrganizations()
    {
        $arguments = [
            '--user-business-unit' => 'bu1',
            '--user-name'          => 'test_user_3',
            '--user-email'         => 'test_user_3@mailinator.com',
            '--user-password'      => 'admin'
        ];

        $output = self::runCommand('oro:user:create', $arguments);
        self::assertEmpty($output);

        /** @var User $user */
        $user = $this->userManager->findUserByUsername('test_user_3');
        self::assertTrue($user->isEnabled());
        self::assertEquals('test_user_3@mailinator.com', $user->getEmail());
        self::assertEquals($this->getReference('bu1'), $user->getOwner());
        self::assertContains($this->getReference('bu1'), $user->getBusinessUnits());
        $userOrganizations = $user->getOrganizations();
        self::assertContains($this->getReference('org1'), $userOrganizations);
        self::assertEquals($this->getReference('org1'), $user->getOrganization());
    }

    /**
     * @depends testUpdate
     */
    public function testTryToCreateOnExistingUser()
    {
        $arguments = [
            '--user-business-unit' => 'bu1',
            '--user-name'          => 'test_user_2',
            '--user-email'         => 'test_user_2@mailinator.com',
            '--user-firstname'     => 'User1F',
            '--user-lastname'      => 'User1L',
            '--user-password'      => 'admin'
        ];

        $output = self::runCommand('oro:user:create', $arguments);
        self::assertEquals('User exists', $output);
    }
}
