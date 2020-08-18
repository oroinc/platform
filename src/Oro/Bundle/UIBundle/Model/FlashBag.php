<?php

namespace Oro\Bundle\UIBundle\Model;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBag as BaseFlashBag;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Decorate original flash bag to bypass session start on session.flash_bag injection.
 * Extending original FlashBag because not all classes depends on the interface
 */
class FlashBag extends BaseFlashBag
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var FlashBagInterface
     */
    private $flashBag;

    /**
     * @param SessionInterface $session
     */
    public function __construct(
        SessionInterface $session
    ) {
        $this->session = $session;
    }

    /**
     * {@inheritDoc}
     */
    public function add($type, $message)
    {
        $this->getFlashBag()->add($type, $message);
    }

    /**
     * {@inheritDoc}
     */
    public function set($type, $messages)
    {
        $this->getFlashBag()->set($type, $messages);
    }

    /**
     * {@inheritDoc}
     */
    public function peek($type, array $default = [])
    {
        return $this->getFlashBag()->peek($type, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function peekAll()
    {
        return $this->getFlashBag()->peekAll();
    }

    /**
     * {@inheritDoc}
     */
    public function get($type, array $default = [])
    {
        return $this->getFlashBag()->get($type, $default);
    }

    /**
     * {@inheritDoc}
     */
    public function all()
    {
        return $this->getFlashBag()->all();
    }

    /**
     * {@inheritDoc}
     */
    public function setAll(array $messages)
    {
        $this->getFlashBag()->setAll($messages);
    }

    /**
     * {@inheritDoc}
     */
    public function has($type)
    {
        return $this->getFlashBag()->has($type);
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        return $this->getFlashBag()->keys();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getFlashBag()->getName();
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array &$array)
    {
        $this->getFlashBag()->initialize($array);
    }

    /**
     * {@inheritDoc}
     */
    public function getStorageKey()
    {
        return $this->getFlashBag()->getStorageKey();
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
        $this->getFlashBag()->clear();
    }

    /**
     * @return FlashBagInterface
     */
    private function getFlashBag(): FlashBagInterface
    {
        if (null === $this->flashBag) {
            $this->flashBag = $this->session->getFlashBag();
        }

        return $this->flashBag;
    }
}
