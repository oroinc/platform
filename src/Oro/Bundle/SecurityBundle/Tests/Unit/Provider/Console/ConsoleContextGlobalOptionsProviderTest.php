<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Provider\Console;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Bundle\SecurityBundle\Model\Role;
use Oro\Bundle\SecurityBundle\Provider\Console\ConsoleContextGlobalOptionsProvider;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ConsoleContextGlobalOptionsProviderTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private TokenStorageInterface&MockObject $tokenStorage;
    private UserManager&MockObject $userManager;
    private ConsoleContextGlobalOptionsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->userManager = $this->createMock(UserManager::class);

        $container = TestContainerBuilder::create()
            ->add(ManagerRegistry::class, $this->doctrine)
            ->add(TokenStorageInterface::class, $this->tokenStorage)
            ->add(UserManager::class, $this->userManager)
            ->getContainer($this);

        $this->provider = new ConsoleContextGlobalOptionsProvider($container);
    }

    private function getUserRepository(): UserRepository&MockObject
    {
        $repository = $this->createMock(UserRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        return $repository;
    }

    private function getOrganizationRepository(): OrganizationRepository&MockObject
    {
        $repository = $this->createMock(OrganizationRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Organization::class)
            ->willReturn($repository);

        return $repository;
    }

    public function testAddGlobalOptions(): void
    {
        $inputDefinition = new InputDefinition();
        $application = $this->createMock(Application::class);
        $application->expects(self::any())
            ->method('getDefinition')
            ->willReturn($inputDefinition);
        $application->expects(self::once())
            ->method('getHelperSet')
            ->willReturn(new HelperSet());

        $commandDefinition = new InputDefinition();
        $command = new Command('test');
        $command->setApplication($application);
        $command->setDefinition($commandDefinition);

        $this->provider->addGlobalOptions($command);
        self::assertEquals(
            ['current-user', 'current-organization'],
            array_keys($command->getApplication()->getDefinition()->getOptions())
        );
        self::assertEquals(
            ['current-user', 'current-organization'],
            array_keys($command->getDefinition()->getOptions())
        );
    }

    public function testResolveGlobalOptionsWhenNoUserAndOrganization(): void
    {
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(['current-user'], ['current-organization'])
            ->willReturn(null, null);

        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenNoUser(): void
    {
        $organizationId = 777;
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(['current-user'], ['current-organization'])
            ->willReturn(null, $organizationId);

        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenUserIsNotFound(): void
    {
        $userId = 777;
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(['current-user'], ['current-organization'])
            ->willReturn($userId, null);

        $repository = $this->getUserRepository();
        $repository->expects(self::once())
            ->method('find')
            ->with($userId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Can\'t find user with identifier %s', $userId));
        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenUserIsIntAndNoOrganizationAndUserHaveAccessToOneOrg(): void
    {
        $userId = 777;
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(['current-user'], ['current-organization'])
            ->willReturn($userId, null);

        $organization = new Organization();
        $organization->setEnabled(true);
        $role = $this->createMock(Role::class);
        $user = new User();
        $user->addUserRole($role);
        $user->addOrganization($organization);
        $repository = $this->getUserRepository();
        $repository->expects(self::once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $expectedToken = new ConsoleToken([$role]);
        $expectedToken->setUser($user);
        $expectedToken->setOrganization($organization);
        $this->tokenStorage->expects(self::once())
            ->method('setToken')
            ->with($expectedToken);

        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenNoOrganizationAndUserHaveAccessToMultipleOrgs(): void
    {
        $userId = 777;
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(['current-user'], ['current-organization'])
            ->willReturn($userId, null);

        $organization1 = new Organization();
        $organization1->setEnabled(true);
        $organization2 = new Organization();
        $organization2->setEnabled(true);
        $role = $this->createMock(Role::class);
        $user = new User();
        $user->addUserRole($role);
        $user->addOrganization($organization1);
        $user->addOrganization($organization2);
        $repository = $this->getUserRepository();
        $repository->expects(self::once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The --current-organization parameter is not specified.');

        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenNoOrganizationAndUserHaveAccessToMultipleOrgsAndEnableOnlyOne(): void
    {
        $userId = 777;
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(
                ['current-user'],
                ['current-organization']
            )
            ->willReturn($userId, null);

        $organization1 = new Organization();
        $organization1->setEnabled(false);
        $organization2 = new Organization();
        $organization2->setEnabled(true);
        $role = $this->createMock(Role::class);
        $user = new User();
        $user->addUserRole($role);
        $user->addOrganization($organization1);
        $user->addOrganization($organization2);
        $repository = $this->getUserRepository();
        $repository->expects(self::once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $expectedToken = new ConsoleToken([$role]);
        $expectedToken->setUser($user);
        $expectedToken->setOrganization($organization2);
        $this->tokenStorage->expects(self::once())
            ->method('setToken')
            ->with($expectedToken);

        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenNoOrganizationAndUserHaveNoOrgs(): void
    {
        $userId = 777;
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(['current-user'], ['current-organization'])
            ->willReturn($userId, null);

        $role = $this->createMock(Role::class);
        $user = new User();
        $user->addUserRole($role);
        $repository = $this->getUserRepository();
        $repository->expects(self::once())
            ->method('find')
            ->with($userId)
            ->willReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The --current-organization parameter is not specified.');

        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenUserIsStringAndNoOrganization(): void
    {
        $username = 'username';
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(['current-user'], ['current-organization'])
            ->willReturn($username, null);

        $organization = new Organization();
        $organization->setEnabled(true);
        $role = $this->createMock(Role::class);
        $user = new User();
        $user->addUserRole($role);
        $user->addOrganization($organization);
        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn($user);

        $expectedToken = new ConsoleToken([$role]);
        $expectedToken->setUser($user);
        $expectedToken->setOrganization($organization);
        $this->tokenStorage->expects(self::once())
            ->method('setToken')
            ->with($expectedToken);

        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenUserIsStringAndOrganizationIsNotFound(): void
    {
        $username = 'username';
        $organizationId = 777;
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(['current-user'], ['current-organization'])
            ->willReturn($username, $organizationId);

        $user = new User();
        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn($user);

        $organizationRepository = $this->getOrganizationRepository();
        $organizationRepository->expects(self::once())
            ->method('find')
            ->with($organizationId)
            ->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Can\'t find organization with identifier %s', $organizationId));
        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenUserIsStringAndOrganizationIsNotEnabled(): void
    {
        $username = 'username';
        $organizationId = 555;
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(['current-user'], ['current-organization'])
            ->willReturn($username, $organizationId);

        $user = new User();
        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn($user);

        $organization = new Organization();
        $organization->setEnabled(false);
        $organization->setName('testorg');
        $organizationRepository = $this->getOrganizationRepository();
        $organizationRepository->expects(self::once())
            ->method('find')
            ->with($organizationId)
            ->willReturn($organization);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Organization %s is not enabled', $organization->getName()));
        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptionsWhenUserNotFromCurrentOrganization(): void
    {
        $username = 'username';
        $organizationId = 555;
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(['current-user'], ['current-organization'])
            ->willReturn($username, $organizationId);

        $organization = new Organization();
        $organization->setEnabled(true);
        $organization->setName('testneworg');
        $organizationRepository = $this->getOrganizationRepository();
        $organizationRepository->expects(self::once())
            ->method('find')
            ->with($organizationId)
            ->willReturn($organization);

        $user = new User();
        $user->setUsername('testnewusername');
        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'User %s is not in organization %s',
            $user->getUserIdentifier(),
            $organization->getName()
        ));
        $this->provider->resolveGlobalOptions($input);
    }

    public function testResolveGlobalOptions(): void
    {
        $username = 'username';
        $organizationId = 555;
        $input = $this->createMock(InputInterface::class);
        $input->expects(self::exactly(2))
            ->method('getOption')
            ->withConsecutive(['current-user'], ['current-organization'])
            ->willReturn($username, $organizationId);

        $organization = new Organization();
        $organization->setEnabled(true);
        $organization->setName('testneworg');
        $organizationRepository = $this->getOrganizationRepository();
        $organizationRepository->expects(self::once())
            ->method('find')
            ->with($organizationId)
            ->willReturn($organization);

        $role = $this->createMock(Role::class);
        $user = new User();
        $user->setUsername('testnewusername');
        $user->addUserRole($role);
        $user->addOrganization($organization);
        $this->userManager->expects(self::once())
            ->method('findUserByUsernameOrEmail')
            ->with($username)
            ->willReturn($user);

        $expectedToken = new ConsoleToken([$role]);
        $expectedToken->setUser($user);
        $expectedToken->setOrganization($organization);
        $this->tokenStorage->expects(self::once())
            ->method('setToken')
            ->with($expectedToken);

        $this->provider->resolveGlobalOptions($input);
    }
}
