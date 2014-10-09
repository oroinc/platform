<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\Authentication\Token\ConsoleToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

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
     * @param ManagerRegistry $registry
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(ManagerRegistry $registry, SecurityContextInterface $securityContext)
    {
        $this->registry = $registry;
        $this->securityContext = $securityContext;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        $input = $event->getInput();
        $definition = $command->getApplication()->getDefinition();

        $definition->addOption(
            new InputOption(
                self::OPTION_USER,
                null,
                InputOption::VALUE_REQUIRED,
                'ID or username of the user that should be used as current user'
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

        $command->mergeApplicationDefinition();
        $input->bind($definition);

        $user         = $input->getOption(self::OPTION_USER);
        $organization = $input->getOption(self::OPTION_ORGANIZATION);

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
     */
    protected function setUser($user, TokenInterface $token)
    {
        if (!$user) {
            return;
        }

        $userRepository = $this->registry->getRepository('OroUserBundle:User');

        $userId = filter_var($user, FILTER_VALIDATE_INT);
        if ($userId) {
            $user = $userRepository->find($userId);
            if ($user) {
                $token->setUser($user);
            }
        } else {
            $user = $userRepository->findOneBy(['username' => $user]);
            if ($user) {
                $token->setUser($user);
            }
        }
    }

    /**
     * @param mixed $organization
     * @param OrganizationContextTokenInterface $token
     */
    protected function setOrganization($organization, OrganizationContextTokenInterface $token)
    {
        if (!$organization) {
            return;
        }

        $organizationRepository = $this->registry->getRepository('OroOrganizationBundle:Organization');

        $organizationId = filter_var($organization, FILTER_VALIDATE_INT);
        if ($organizationId) {
            $organization = $organizationRepository->find($organizationId);
            if ($organization) {
                $token->setOrganizationContext($organization);
            }
        } else {
            $organization = $organizationRepository->findOneBy(['name' => $organization]);
            if ($organization) {
                $token->setOrganizationContext($organization);
            }
        }
    }
}
