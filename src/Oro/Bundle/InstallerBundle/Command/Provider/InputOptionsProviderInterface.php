<?php

namespace Oro\Bundle\InstallerBundle\Command\Provider;

interface InputOptionsProviderInterface
{
    /**
     * @param string      $name
     * @param string      $question
     * @param string|null $default
     * @param string      $askMethod
     * @param array       $additionalAskArgs
     *
     * @return string
     */
    public function get($name, $question, $default = null, $askMethod = 'ask', $additionalAskArgs = []);
}
