<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class ConsoleContextListener
{
    const OPTION_USER         = 'current-user';
    const OPTION_ORGANIZATION = 'current-organization';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var UserManager
     */
    protected $userManager;

    /**
     * @param ManagerRegistry $registry
     * @param SecurityContextInterface $securityContext
     * @param UserManager $userManager
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

        $token = $this->securityContext->getToken();
        if (!$token) {
            $token = new ConsoleToken();
            $this->securityContext->setToken($token);
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
            $userEntity = $this->registry->getRepository('OroUserBundle:User')->find($userId);
        } else {
            $userEntity = $this->userManager->findUserByUsernameOrEmail($user);
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

        $organizationRepository = $this->registry->getRepository('OroOrganizationBundle:Organization');

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
}
