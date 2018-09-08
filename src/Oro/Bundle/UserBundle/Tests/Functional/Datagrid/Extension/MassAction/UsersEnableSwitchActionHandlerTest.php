<?php

namespace Oro\Bundle\UserBundle\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\AjaxMassAction;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;

class UsersEnableSwitchActionHandlerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            ['@OroUserBundle/Tests/Functional/DataFixtures/Alice/users_enable_switch_action_handler_users.yml']
        );
    }

    public function testHandle()
    {
        $userReference = 'user.1';
        $this->initToken($userReference, 'organization.1');
        $userRepository = $this->getUserRepo();
        $query          = $userRepository->createQueryBuilder('u')->getQuery();//select all
        $resultIterator = new IterableResult($query);
        $handler = self::getContainer()->get('oro_datagrid.mass_action.users_enable_switch.handler.disable');
        /** @var DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);

        $response = $handler->handle(new MassActionHandlerArgs(new AjaxMassAction(), $datagrid, $resultIterator, []));

        $users = $userRepository->findAll();
        /** @var User $currentUser */
        $currentUser = $this->getReference($userReference);

        /** @var User $user */
        foreach ($users as $user) {
            // Admin user should not processed because he was created at another organization.
            if ($user->getId() !== $currentUser->getId() && $user->getUsername() !== 'admin') {
                self::assertFalse($user->isEnabled());
            }
        }

        $all          = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->getQuery()
            ->getSingleScalarResult();
        $expectedMessage = sprintf('%s user(s) were disabled', $all - 2/* except current and admin*/);
        self::assertEquals($expectedMessage, $response->getMessage());
    }

    /**
     * @return UserRepository
     */
    protected function getUserRepo()
    {
        return self::getContainer()->get('doctrine')->getRepository(User::class);
    }

    /**
     * @param string $userReference
     * @param string $orgReference
     */
    protected function initToken($userReference, $orgReference)
    {
        $token = new UsernamePasswordOrganizationToken(
            $this->getReference($userReference),
            [],
            'main',
            $this->getReference($orgReference)
        );
        self::getContainer()->get('security.token_storage')->setToken($token);
    }
}
