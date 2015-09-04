<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Command;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Command\CreateUserCommand;
use Oro\Bundle\UserBundle\Command\UpdateUserCommand;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @dbIsolation
 */
class UserCreateUpdateTest extends WebTestCase
{
    /**
     * @var Application
     */
    protected $application;
    /**
     * @var BusinessUnit[]
     */
    protected $businessUnits;

    /**
     * @var Organization[]
     */
    protected $organizations;

    /**
     * @var UserManager
     */
    protected $userManager;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(['Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadCommandUserCreateUpdateData']);

        $this->application = new Application($this->client->getKernel());
        $this->application->setAutoExit(false);
        $this->application->add(new CreateUserCommand());
        $this->application->add(new UpdateUserCommand());

        $this->businessUnits = ['bu1' => $this->getReference('bu1')];
        $this->organizations = [
            'org1' => $this->getReference('org1'),
            'org2' => $this->getReference('org2'),
            'org3' => $this->getReference('org3'),
        ];

        $this->userManager = $this->getContainer()->get('oro_user.manager');
    }

    /**
     * @dataProvider parametersProvider
     *
     * @param array $arguments
     * @param string $result
     */
    public function testParameters(array $arguments, $result)
    {
        $command = $this->application->find($arguments['command']);
        $commandTester = new CommandTester($command);
        $commandTester->execute($arguments);

        $businessUnit =
            isset($arguments['--user-business-unit']) &&
            isset($this->businessUnits[$arguments['--user-business-unit']])
            ? $this->businessUnits[$arguments['--user-business-unit']]
            : null;

        if (!empty($result)) {
            $this->assertStringStartsWith($result, $commandTester->getDisplay());
            return;
        }

        $this->assertEmpty($commandTester->getDisplay());
        /** @var User $user */
        $user = $this->userManager->findUserByUsername($arguments['--user-name']);
        $this->assertNotEmpty($user);
        if ($businessUnit) {
            $this->assertSame($user->getOrganization(), $businessUnit->getOrganization());
            $this->assertContains($businessUnit, $user->getBusinessUnits());
        }
        $this->assertTrue($user->isEnabled());
        $this->assertEquals($arguments['--user-name'], $user->getUsername());
        $this->assertEquals($arguments['--user-email'], $user->getEmail());
        $this->assertEquals($arguments['--user-firstname'], $user->getFirstName());
        $this->assertEquals($arguments['--user-lastname'], $user->getLastName());

        $userOrganizations = $user->getOrganizations();
        foreach ($arguments['--user-organizations'] as $organizationName) {
            $this->assertContains($this->organizations[$organizationName], $userOrganizations);
        }
    }

    /**
     * @return array
     */
    public function parametersProvider()
    {
        return [
            'create' => [
                'arguments'    => [
                    'command'               => 'oro:user:create',
                    '--user-business-unit'  => 'bu1',
                    '--user-name'           => 'test_user_1',
                    '--user-email'          => 'test_user_1@mailinator.com',
                    '--user-firstname'      => 'User1F',
                    '--user-lastname'       => 'User1L',
                    '--user-password'       => 'admin',
                    '--user-organizations'  => ['org2', 'org3'],
                ],
                'result' => '',
            ],
            'update' => [
                'arguments'    => [
                    'command'               => 'oro:user:update',
                    'user-name'             => 'test_user_main',
                    '--user-name'           => 'test_user_2',
                    '--user-email'          => 'test_user_2@mailinator.com',
                    '--user-firstname'      => 'User2F',
                    '--user-lastname'       => 'User2L',
                    '--user-organizations'  => ['org1'],
                ],
                'result' => '',
            ],
            'create existent user' => [
                'arguments'    => [
                    'command'               => 'oro:user:create',
                    '--user-name'           => 'test_user_main',
                ],
                'result' => 'User exists',
            ],
            'invalid business unit' => [
                'arguments'    => [
                    'command'               => 'oro:user:create',
                    '--user-business-unit'  => uniqid(),
                ],
                'result' => 'Invalid Business Unit',
            ],
            'invalid organization' => [
                'arguments'    => [
                    'command'               => 'oro:user:create',
                    '--user-organizations'  => [uniqid()],
                ],
                'result' => 'Invalid organization',
            ],
            'invalid role' => [
                'arguments'    => [
                    'command'               => 'oro:user:create',
                    '--user-role'  => [uniqid()],
                ],
                'result' => 'Invalid Role',
            ],
        ];
    }
}
