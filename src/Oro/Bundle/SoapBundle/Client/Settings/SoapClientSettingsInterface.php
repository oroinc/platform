<?php

namespace Oro\Bundle\SoapBundle\Client\Settings;

interface SoapClientSettingsInterface
{
    /**
     * @return string|null
     */
    public function getWsdlFilePath();

    /**
     * @return string
     */
    public function getMethodName(): string;

    /**
     * @return array
     */
    public function getSoapOptions(): array;
}
