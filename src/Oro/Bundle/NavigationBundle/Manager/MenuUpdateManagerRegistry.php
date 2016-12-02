<?php

namespace Oro\Bundle\NavigationBundle\Manager;

use Doctrine\Common\Collections\ArrayCollection;

class MenuUpdateManagerRegistry
{
    /** @var ArrayCollection */
    private $managers;

    /**
     * MenuUpdateManagerRegistry constructor.
     */
    public function __construct()
    {
        $this->managers = new ArrayCollection();
    }

    /**
     * @param string            $scopeType
     * @param MenuUpdateManager $manager
     */
    public function addManager($scopeType, MenuUpdateManager $manager)
    {
        $this->managers->set($scopeType, $manager);
    }

    /**
     * @param string $scopeType
     *
     * @return MenuUpdateManager
     */
    public function getManager($scopeType)
    {
        return $this->managers->get($scopeType);
    }
}
