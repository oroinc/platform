<?php

namespace Oro\Bundle\SecurityBundle\Provider\Console;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PlatformBundle\Provider\Console\AbstractGlobalOptionsProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Provides "current-user" and "current-organization" options for all commands.
 */
class ConsoleContextGlobalOptionsProvider extends AbstractGlobalOptionsProvider
{
    public const OPTION_USER         = 'current-user';
    public const OPTION_ORGANIZATION = 'current-organization';

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function addGlobalOptions(Command $command)
    {
        $options = [
            new InputOption(
                self::OPTION_USER,
                null,
                InputOption::VALUE_REQUIRED,
                'ID, username or email of the user that should be used as current user'
            ),
            new InputOption(
                self::OPTION_ORGANIZATION,
                null,
                InputOption::VALUE_REQUIRED,
                'ID or name of the organization that should be used as current organization'
            )
        ];

        $this->addOptionsToCommand($command, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveGlobalOptions(InputInterface $input)
    {
        $user = $input->getParameterOption('--' . self::OPTION_USER);
        $organization = $input->getParameterOption('--' . self::OPTION_ORGANIZATION);

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
     * @param mixed $user
     *
     * @return TokenInterface|null
     */
    private function createToken($user): ?TokenInterface
    {
        if (!$user) {
            return null;
        }

        $userEntity = $this->loadUser($user);
        if (null === $userEntity) {
            throw new \InvalidArgumentException(sprintf('Can\'t find user with identifier %s', $user));
        }

        $token = new ConsoleToken($userEntity->getRoles());
        $token->setUser($userEntity);

        return $token;
    }

    /**
     * @param mixed $user
     *
     * @return User|null
     */
    private function loadUser($user): ?User
    {
        $userId = filter_var($user, FILTER_VALIDATE_INT);
        if ($userId) {
            return $this->getDoctrine()->getRepository(User::class)->find($userId);
        }

        return $this->getUserManager()->findUserByUsernameOrEmail($user);
    }

    /**
     * @param mixed                           $organization
     * @param OrganizationAwareTokenInterface $token
     */
    private function setOrganization($organization, OrganizationAwareTokenInterface $token): void
    {
        if (!$organization) {
            return;
        }

        $organizationEntity = $this->loadOrganization($organization);
        if (null === $organizationEntity) {
            throw new \InvalidArgumentException(sprintf(
                'Can\'t find organization with identifier %s',
                $organization
            ));
        }

        if (!$organizationEntity->isEnabled()) {
            throw new \InvalidArgumentException(sprintf(
                'Organization %s is not enabled',
                $organizationEntity->getName()
            ));
        }

        $user = $token->getUser();
        if ($user instanceof User && !$user->isBelongToOrganization($organizationEntity)) {
            throw new \InvalidArgumentException(sprintf(
                'User %s is not in organization %s',
                $user->getUsername(),
                $organizationEntity->getName()
            ));
        }

        $token->setOrganization($organizationEntity);
    }

    /**
     * @param mixed $organization
     *
     * @return Organization|null
     */
    private function loadOrganization($organization): ?Organization
    {
        $organizationRepository = $this->getDoctrine()->getRepository(Organization::class);
        $organizationId = filter_var($organization, FILTER_VALIDATE_INT);
        if ($organizationId) {
            return $organizationRepository->find($organizationId);
        }

        return $organizationRepository->findOneBy(['name' => $organization]);
    }

    /**
     * @return ManagerRegistry
     */
    private function getDoctrine(): ManagerRegistry
    {
        return $this->container->get('doctrine');
    }

    /**
     * @return TokenStorageInterface
     */
    private function getSecurityTokenStorage(): TokenStorageInterface
    {
        return $this->container->get('security.token_storage');
    }

    /**
     * @return UserManager
     */
    private function getUserManager(): UserManager
    {
        return $this->container->get('oro_user.manager');
    }
}
