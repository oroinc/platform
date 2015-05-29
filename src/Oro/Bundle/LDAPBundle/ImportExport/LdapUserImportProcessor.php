<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\StepExecutionAwareProcessor;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorContextMediator;
use Oro\Bundle\LDAPBundle\Provider\ChannelManagerProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LdapUserImportProcessor implements StepExecutionAwareProcessor
{
    use HasChannel;

    /** @var ContextRegistry */
    private $contextRegistry;

    /** @var UserManager */
    private $userManager;

    /** @var ChannelManagerProvider */
    private $managerProvider;

    /** @var string */
    private $usernameAttr;

    public function __construct(
        UserManager                 $userManager,
        ContextRegistry             $contextRegistry,
        ConnectorContextMediator    $connectorContextMediator,
        ChannelManagerProvider      $managerProvider
    ) {
        $this->contextRegistry           = $contextRegistry;
        $this->userManager               = $userManager;
        $this->setConnectorContextMediator($connectorContextMediator);
        $this->managerProvider            = $managerProvider;
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

        $this->managerProvider->channel($this->getChannel())->hydrate($user, $item);

        // Set organization of user to same as on channel.
        $user->getOrganizations()->add($this->getChannel()->getOrganization());
        $user->setOrganization($this->getChannel()->getOrganization());

        if (!$user->getPassword()) {
            $user->setPassword('');
        }

        return $user;
    }

    public function initialize()
    {
        $this->usernameAttr = $this->managerProvider->channel($this->getChannel())->getUsernameAttr();
    }

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        try {
            $user = $this->userManager->findUserByUsername($item[$this->usernameAttr]);

            if ($user === null) {
                $user = $this->createUser($item);
                $this->getContext()->incrementAddCount();
            } else {
                $this->managerProvider->channel($this->getChannel())->hydrate($user, $item);
                $this->getContext()->incrementUpdateCount();
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
