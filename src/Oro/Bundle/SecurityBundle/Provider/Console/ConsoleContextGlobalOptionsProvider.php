<?php
declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Provider\Console;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PlatformBundle\Provider\Console\AbstractGlobalOptionsProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Adds --current-user global command option to specify the user for the security context if necessary.
 */
class ConsoleContextGlobalOptionsProvider extends AbstractGlobalOptionsProvider
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function addGlobalOptions(Command $command)
    {
        $options = [
            new InputOption(
                'current-user',
                null,
                InputOption::VALUE_REQUIRED,
                'ID, username or email'
            ),
            new InputOption(
                'current-organization',
                null,
                InputOption::VALUE_REQUIRED,
                'ID or organization name (required if user has access to multiple organizations)'
            )
        ];

        $this->addOptionsToCommand($command, $options);
    }

    public function resolveGlobalOptions(InputInterface $input)
    {
        $user = $input->getParameterOption('--current-user');
        $organization = $input->getParameterOption('--current-organization');

        if (!$user && !$organization) {
            return;
        }

        $token = $this->createToken($user);
        $this->getSecurityTokenStorage()->setToken($token);

        if ($token instanceof OrganizationAwareTokenInterface) {
            $this->setOrganization($organization, $token);
        }
    }

    /**
     * @param string|int|null $user Username, email or user ID (int).
     */
    private function createToken($user): ?TokenInterface
    {
        if (!$user) {
            return null;
        }

        $userEntity = $this->loadUser($user);
        if (null === $userEntity) {
            throw new \InvalidArgumentException(\sprintf('Can\'t find user with identifier %s', $user));
        }

        $token = new ConsoleToken($userEntity->getUserRoles());
        $token->setUser($userEntity);

        return $token;
    }

    /**
     * @param string|int $user Username, email or user ID (int).
     */
    private function loadUser($user): ?UserInterface
    {
        $userId = \filter_var($user, FILTER_VALIDATE_INT);
        if ($userId) {
            return $this->getDoctrine()->getRepository(User::class)->find($userId);
        }

        return $this->getUserManager()->findUserByUsernameOrEmail($user);
    }

    /**
     * @param string|int|null $organization Organization name or ID
     * @param OrganizationAwareTokenInterface $token
     */
    private function setOrganization($organization, OrganizationAwareTokenInterface $token): void
    {
        $user = $token->getUser();
        $organizationEntity = null;
        if (!$organization) {
            $userOrganizations = $user->getOrganizations(true);
            if (1 === $userOrganizations->count()) {
                $organizationEntity = $userOrganizations->first();
            } else {
                throw new \InvalidArgumentException('The --current-organization parameter is not specified.');
            }
        }

        if (null === $organizationEntity) {
            $organizationEntity = $this->loadOrganization($organization);
        }

        if (null === $organizationEntity) {
            throw new \InvalidArgumentException(\sprintf(
                'Can\'t find organization with identifier %s',
                $organization
            ));
        }

        if (!$organizationEntity->isEnabled()) {
            throw new \InvalidArgumentException(\sprintf(
                'Organization %s is not enabled',
                $organizationEntity->getName()
            ));
        }

        $user = $token->getUser();
        if ($user instanceof User && !$user->isBelongToOrganization($organizationEntity)) {
            throw new \InvalidArgumentException(\sprintf(
                'User %s is not in organization %s',
                $user->getUsername(),
                $organizationEntity->getName()
            ));
        }

        $token->setOrganization($organizationEntity);
    }

    /**
     * @param string|int $organization Organization name or ID
     */
    private function loadOrganization($organization): ?Organization
    {
        $organizationRepository = $this->getDoctrine()->getRepository(Organization::class);
        $organizationId = \filter_var($organization, FILTER_VALIDATE_INT);
        if ($organizationId) {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $organizationRepository->find($organizationId);
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $organizationRepository->findOneBy(['name' => $organization]);
    }

    private function getDoctrine(): ManagerRegistry
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->container->get('doctrine');
    }

    private function getSecurityTokenStorage(): TokenStorageInterface
    {
        return $this->container->get('security.token_storage');
    }

    private function getUserManager(): UserManager
    {
        return $this->container->get('oro_user.manager');
    }
}
