<?php

namespace Oro\Bundle\SecurityBundle\Session;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

/**
 * Adds extra session bags to the session storage.
 */
class SessionStorageFactory implements SessionStorageFactoryInterface
{
    private SessionStorageFactoryInterface $innerSessionStorageFactory;

    private iterable $sessionBags;

    public function __construct(SessionStorageFactoryInterface $sessionStorageFactory, iterable $sessionBags)
    {
        $this->innerSessionStorageFactory = $sessionStorageFactory;
        $this->sessionBags = $sessionBags;
    }

    public function createStorage(?Request $request): SessionStorageInterface
    {
        $storage = $this->innerSessionStorageFactory->createStorage($request);

        foreach ($this->sessionBags as $sessionBag) {
            $storage->registerBag($sessionBag);
        }

        return $storage;
    }
}
