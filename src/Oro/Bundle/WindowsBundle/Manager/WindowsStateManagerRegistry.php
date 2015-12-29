<?php

namespace Oro\Bundle\WindowsBundle\Manager;

class WindowsStateManagerRegistry
{
    /**  @var WindowsStateManager[] */
    protected $managers = [];

    /** @var WindowsStateManager */
    private $defaultManager;

    /**
     * @param WindowsStateManager $defaultManager
     */
    public function __construct(WindowsStateManager $defaultManager)
    {
        $this->defaultManager = $defaultManager;
    }

    /**
     * @param WindowsStateManager $manager
     */
    public function addManager(WindowsStateManager $manager)
    {
        $this->managers[] = $manager;
    }

    /**
     * @return WindowsStateManager
     */
    public function getManager()
    {
        foreach ($this->managers as $manager) {
            if ($manager->isApplicable()) {
                return $manager;
            }
        }

        return $this->defaultManager;
    }
}
