<?php

namespace Oro\Bundle\SoapBundle\Client\Settings;

interface SoapClientSettingsInterface
{
    /**
     * @return string|null
     */
    public function getWsdlFilePath();

    public function getMethodName(): string;

    public function getSoapOptions(): array;
}
