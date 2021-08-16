<?php

namespace Oro\Bundle\MaintenanceBundle\Drivers;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The factory to create driver for Maintenance Mode check.
 */
class DriverFactory
{
    protected array $driverOptions;
    protected TranslatorInterface $translator;
    private ?AbstractDriver $driver = null;

    public function __construct(TranslatorInterface $translator, array $driverOptions)
    {
        $this->translator = $translator;
        $this->driverOptions = $driverOptions;
    }

    public function getDriver(): AbstractDriver
    {
        if (null === $this->driver) {
            $this->driver = $this->createDriver();
        }

        return $this->driver;
    }

    protected function createDriver(): AbstractDriver
    {
        $driver = new FileDriver($this->driverOptions['options']);
        $driver->setTranslator($this->translator);

        return $driver;
    }
}
