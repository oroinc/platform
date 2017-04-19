<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;

class WorkflowManagerRegistry
{
    /** @var CurrentApplicationProviderInterface */
    protected $applicationProvider;

    /** @var WorkflowManager[] */
    protected $managers = [];

    /**
     * @param CurrentApplicationProviderInterface $currentApplicationProvider
     */
    public function __construct(CurrentApplicationProviderInterface $currentApplicationProvider)
    {
        $this->currentApplicationProvider = $currentApplicationProvider;
    }

    /**
     * @param WorkflowManager $manager
     * @param string $name
     */
    public function addManager(WorkflowManager $manager, $name)
    {
        $this->managers[$name] = $manager;
    }

    /**
     * @param string|null $name
     * @return WorkflowManager
     * @throws \LogicException
     */
    public function getManager($name = null)
    {
        if (null === $name) {
            $name = $this->isDefaultApplication() ? 'system' : 'default';
        }

        if (!array_key_exists($name, $this->managers)) {
            throw new \LogicException(sprintf('Workflow manager with name "%s" not registered.', $name));
        }

        return $this->managers[$name];
    }

    /**
     * @return bool
     */
    protected function isDefaultApplication()
    {
        return CurrentApplicationProviderInterface::DEFAULT_APPLICATION
            === $this->currentApplicationProvider->getCurrentApplication();
    }
}
