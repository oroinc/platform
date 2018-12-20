<?php

namespace Oro\Bundle\EmailBundle\Mailbox;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\MailboxProcessSettings;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class MailboxProcessStorage
{
    /** @var FeatureChecker */
    protected $featureChecker;

    /** @var MailboxProcessProviderInterface[] */
    protected $processes = [];

    /**
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * Registers mailbox process provider with application.
     *
     * @param string                          $type
     * @param MailboxProcessProviderInterface $provider
     */
    public function addProcess($type, MailboxProcessProviderInterface $provider)
    {
        if (isset($this->processes[$type])) {
            throw new \LogicException(
                sprintf('Process of type %s is already registered. Review your service configuration.')
            );
        }

        $this->processes[$type] = $provider;
    }

    /**
     * Returns process provider of provided type.
     *
     * @param string $type
     *
     * @return MailboxProcessProviderInterface
     */
    public function getProcess($type)
    {
        $this->errorIfUnregistered($type);

        return $this->processes[$type];
    }

    /**
     * Returns all registered processes.
     *
     * @return MailboxProcessProviderInterface['type' => MailboxProcessProviderInterface]
     */
    public function getProcesses()
    {
        return $this->processes;
    }

    /**
     * Creates new instance of settings entity for provided type.
     *
     * @param $type
     *
     * @return MailboxProcessSettings
     */
    public function getNewSettingsEntity($type)
    {
        $entityClass = $this->getProcess($type)->getSettingsEntityFQCN();

        if (!class_exists($entityClass)) {
            throw new \LogicException(
                sprintf('Settings entity %s for mailbox process %s does not exist.', $entityClass, $type)
            );
        }

        return new $entityClass();
    }

    /**
     * Returns choice list for process type choice field.
     *
     * @param Mailbox $mailbox
     *
     * @return array('type' => 'Process Type Label (translate id)')
     */
    public function getProcessTypeChoiceList(Mailbox $mailbox = null)
    {
        $choices = [];
        foreach ($this->processes as $type => $provider) {
            if (!$provider->isEnabled($mailbox) ||
                !$this->featureChecker->isResourceEnabled($provider->getProcessDefinitionName(), 'processes')
            ) {
                continue;
            }

            $choices[$provider->getLabel()] = $type;
        }

        return $choices;
    }

    /**
     * Returns array of process definition names for all mailbox processes.
     *
     * @return array
     */
    public function getProcessDefinitionNames()
    {
        $list = [];
        foreach ($this->processes as $process) {
            $list[] = $process->getProcessDefinitionName();
        }

        return $list;
    }

    /**
     * Throws exception if provided type is not registered within storage instance.
     *
     * @param string $type
     */
    protected function errorIfUnregistered($type)
    {
        if (!isset($this->processes[$type])) {
            throw new \LogicException(
                sprintf('There is no mailbox process with type %s registered.', $type)
            );
        }
    }
}
