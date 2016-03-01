<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class ConsoleContextListener implements ContainerAwareInterface
{
    const OPTION_USER         = 'current-user';
    const OPTION_ORGANIZATION = 'current-organization';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var SecurityContextInterface
     */
    private $securityContext;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        if (!$this->container) {
            throw new \InvalidArgumentException('ContainerInterface is not injected');
        }

        return $this->container;
    }

    /**
     * @param ManagerRegistry $registry
     * @param SecurityContextInterface $securityContext
     * @param UserManager $userManager
     *
     * @deprecated since 1.8, inject @service_container via setContainer instead
     */
    public function __construct(
        ManagerRegistry $registry,
        SecurityContextInterface $securityContext,
        UserManager $userManager
    ) {
        $this->registry = $registry;
        $this->securityContext = $securityContext;
        $this->userManager = $userManager;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command    = $event->getCommand();
        $input      = $event->getInput();
        $definition = $command->getApplication()->getDefinition();

        $definition->addOption(
            new InputOption(
                self::OPTION_USER,
                null,
                InputOption::VALUE_REQUIRED,
                'ID, username or email of the user that should be used as current user'
            )
        );
        $definition->addOption(
            new InputOption(
                self::OPTION_ORGANIZATION,
                null,
                InputOption::VALUE_REQUIRED,
                'ID or name of the organization that should be used as current organization'
            )
        );

        $user         = $input->getParameterOption('--' . self::OPTION_USER);
        $organization = $input->getParameterOption('--' . self::OPTION_ORGANIZATION);

        if (!$user && !$organization) {
            return;
        }

        $token = $this->getSecurityContext()->getToken();
        if (!$token) {
            $token = new ConsoleToken();
            $this->getSecurityContext()->setToken($token);
        }

        $this->setUser($user, $token);

        if ($token instanceof OrganizationContextTokenInterface) {
            $this->setOrganization($organization, $token);
        }
    }

    /**
     * @param mixed $user
     * @param TokenInterface $token
     * @throws \InvalidArgumentException
     */
    protected function setUser($user, TokenInterface $token)
    {
        if (!$user) {
            return;
        }

        $userId = filter_var($user, FILTER_VALIDATE_INT);
        if ($userId) {
            $userEntity = $this->getRegistry()->getRepository('OroUserBundle:User')->find($userId);
        } else {
            $userEntity = $this->getUserManager()->findUserByUsernameOrEmail($user);
        }

        if ($userEntity) {
            $token->setUser($userEntity);
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

        $organizationRepository = $this->getRegistry()->getRepository('OroOrganizationBundle:Organization');

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
    protected function getRegistry()
    {
        if (!$this->registry) {
            $this->registry = $this->getContainer()->get('doctrine');
        }

        return $this->registry;
    }

    /**
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        if (!$this->securityContext) {
            $this->securityContext = $this->getContainer()->get('security.context');
        }

        return $this->securityContext;
    }

    /**
     * @return UserManager
     */
    protected function getUserManager()
    {
        if (!$this->userManager) {
            $this->userManager = $this->getContainer()->get('oro_user.manager');
        }

        return $this->userManager;
    }
}
