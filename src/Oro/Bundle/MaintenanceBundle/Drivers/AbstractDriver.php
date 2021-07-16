<?php

namespace Oro\Bundle\MaintenanceBundle\Drivers;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract class for drivers
 *
 * @author  Gilles Gauthier <g.gauthier@lexik.fr>
 */
abstract class AbstractDriver
{
    protected array $options;

    protected TranslatorInterface $translator;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function setTranslator(TranslatorInterface $translator): self
    {
        $this->translator = $translator;

        return $this;
    }

    /**
     * Test if object exists
     */
    abstract public function isExists(): bool;

    /**
     * Result of creation of lock
     */
    abstract protected function createLock(): bool;

    /**
     * Result of create unlock
     */
    abstract protected function createUnlock(): bool;

    /**
     * The feedback message
     *
     * @param bool $resultTest The result of lock
     *
     * @return string
     */
    abstract public function getMessageLock(bool $resultTest): string;

    /**
     * The feedback message
     *
     * @param bool $resultTest The result of unlock
     *
     * @return string
     */
    abstract public function getMessageUnlock(bool $resultTest): string;

    /**
     * The response of lock
     */
    public function lock(): bool
    {
        if (!$this->isExists()) {
            return $this->createLock();
        }

        return false;
    }

    /**
     * The response of unlock
     */
    public function unlock(): bool
    {
        if ($this->isExists()) {
            return $this->createUnlock();
        }

        return false;
    }

    /**
     * Checks if the maintenance mode is on or off.
     */
    public function decide(): bool
    {
        return $this->isExists();
    }

    /**
     * Options of driver
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
