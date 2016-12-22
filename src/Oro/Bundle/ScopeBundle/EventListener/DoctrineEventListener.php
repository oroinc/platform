<?php

namespace Oro\Bundle\ScopeBundle\EventListener;

use Oro\Bundle\ScopeBundle\Manager\ScopeEntityStorage;

class DoctrineEventListener
{
    /**
     * @var ScopeEntityStorage
     */
    private $entityStorage;

    /**
     * @param ScopeEntityStorage $entityStorage
     */
    public function __construct(ScopeEntityStorage $entityStorage)
    {
        $this->entityStorage = $entityStorage;
    }

    public function preFlush()
    {
        $this->entityStorage->persistScheduledForInsert();
        $this->entityStorage->clear();
    }

    public function onClear()
    {
        $this->entityStorage->clear();
    }
}
