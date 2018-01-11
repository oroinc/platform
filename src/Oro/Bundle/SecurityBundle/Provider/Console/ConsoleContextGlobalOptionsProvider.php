<?php

namespace Oro\Bundle\SecurityBundle\Provider\Console;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Provider\Console\AbstractGlobalOptionsProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ConsoleContextGlobalOptionsProvider extends AbstractGlobalOptionsProvider
{
    const OPTION_USER         = 'current-user';
    const OPTION_ORGANIZATION = 'current-organization';

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

        $tokenStorage = $this->getSecurityTokenStorage();
        $token = $this->createToken($user);
        $tokenStorage->setToken($token);

        if ($token && $token instanceof OrganizationContextTokenInterface) {
            $this->setOrganization($organization, $token);
        }
    }

    /**
     * @param mixed $user
     * @throws \InvalidArgumentException
     *
     * @return TokenInterface
     */
    protected function createToken($user)
    {
        if (!$user) {
            return;
        }

        $userId = filter_var($user, FILTER_VALIDATE_INT);
        if ($userId) {
            $userEntity = $this->getDoctrine()->getRepository('OroUserBundle:User')->find($userId);
        } else {
            $userEntity = $this->getUserManager()->findUserByUsernameOrEmail($user);
        }

        if ($userEntity) {
            $token = new ConsoleToken($userEntity->getRoles());
            $token->setUser($userEntity);

            return $token;
        } else {
            throw new \InvalidArgumentException(
                sprintf('Can\'t find user with identifier %s', $user)
            );
        }
    }

    /**
     * @param mixed $organization
     * @param OrganizationContextTokenInterface $token
     * @throws \InvalidArgumentException
     */
    protected function setOrganization($organization, OrganizationContextTokenInterface $token)
    {
        if (!$organization) {
            return;
        }

        $organizationRepository = $this->getDoctrine()->getRepository('OroOrganizationBundle:Organization');

        $organizationId = filter_var($organization, FILTER_VALIDATE_INT);
        if ($organizationId) {
            $organizationEntity = $organizationRepository->find($organizationId);
        } else {
            $organizationEntity = $organizationRepository->findOneBy(['name' => $organization]);
        }

        if ($organizationEntity) {
            // organization must be enabled
            if (!$organizationEntity->isEnabled()) {
                throw new \InvalidArgumentException(
                    sprintf('Organization %s is not enabled', $organizationEntity->getName())
                );
            }

            $user = $token->getUser();
            if ($user && $user instanceof User && !$user->hasOrganization($organizationEntity)) {
                throw new \InvalidArgumentException(
                    sprintf('User %s is not in organization %s', $user->getUsername(), $organizationEntity->getName())
                );
            }

            $token->setOrganizationContext($organizationEntity);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Can\'t find organization with identifier %s', $organization)
            );
        }
    }

    /**
     * @return ManagerRegistry
     */
    protected function getDoctrine()
    {
        return $this->container->get('doctrine');
    }

    /**
     * @return TokenStorageInterface
     */
    protected function getSecurityTokenStorage()
    {
        return $this->container->get('security.token_storage');
    }

    /**
     * @return UserManager
     */
    protected function getUserManager()
    {
        return $this->container->get('oro_user.manager');
    }
}
