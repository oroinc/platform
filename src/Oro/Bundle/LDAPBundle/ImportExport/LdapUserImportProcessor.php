<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\StepExecutionAwareProcessor;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\LDAPBundle\LDAP\LdapChannelManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LdapUserImportProcessor implements StepExecutionAwareProcessor
{
    use HasChannel;

    /** @var ContextRegistry */
    private $contextRegistry;

    /** @var UserManager */
    private $userManager;

    /** @var LdapChannelManager */
    private $channelManager;

    public function __construct(
        UserManager                 $userManager,
        ContextRegistry             $contextRegistry,
        ConnectorContextMediator    $connectorContextMediator,
        LdapChannelManager          $channelManager
    ) {
        $this->contextRegistry           = $contextRegistry;
        $this->userManager               = $userManager;
        $this->setConnectorContextMediator($connectorContextMediator);
        $this->channelManager            = $channelManager;
    }

    /**
     * Creates new User from provided array of parameters.
     *
     * @param mixed $item User as array with parameters. Comes from LdapUserReader.
     *
     * @return \Oro\Bundle\UserBundle\Entity\User
     */
    protected function createUser($item)
    {
        /** @var User $user */
        $user = $this->userManager->createUser();

        $this->hydrate($user, $item);

        // Set organization of user to same as on channel.
        $user->getOrganizations()->add($this->getChannel()->getOrganization());
        $user->setOrganization($this->getChannel()->getOrganization());

        if (!$user->getPassword()) {
            $user->setPassword('');
        }

        return $user;
    }

    /**
     * Hydrates user with data from ldap and sets his new dn.
     *
     * @param UserInterface $user
     * @param array $entry
     */
    protected function hydrate(UserInterface $user, array $entry)
    {
        $this->channelManager->hydrateThroughChannel(
            $this->getChannel(),
            $user,
            $entry
        );
    }

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        try {
            $user = $this->userManager->findUserByUsername(
                $item[$this->channelManager->getChannelUsernameAttr($this->getChannel())]
            );

            if ($user !== null) {
                $this->hydrate($user, $item);
                $this->getContext()->incrementUpdateCount();
            } else {
                $user = $this->createUser($item);
                $this->getContext()->incrementAddCount();
            }

            return $user;
        } catch (\Exception $ex) {
            $this->getContext()->addError($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->setContext($this->contextRegistry->getByStepExecution($stepExecution));
    }
}