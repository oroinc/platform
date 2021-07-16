<?php

namespace Oro\Bundle\MaintenanceBundle\Drivers;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Factory for create driver
 */
class DriverFactory
{
    protected array $driverOptions;

    protected TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator, array $driverOptions)
    {
        $this->translator = $translator;
        $this->driverOptions = $driverOptions;
    }

    public function getDriver(): AbstractDriver
    {
        $driver = new FileDriver($this->driverOptions['options']);
        $driver->setTranslator($this->translator);

        return $driver;
    }
}
