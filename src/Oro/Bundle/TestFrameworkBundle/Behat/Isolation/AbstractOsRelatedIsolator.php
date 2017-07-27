<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

abstract class AbstractOsRelatedIsolator
{
    const WINDOWS_OS = 'WINDOWS';
    const LINUX_OS   = 'LINUX';
    const MAC_OS     = 'DARWIN';

    /**
     * @return array of applicable OS
     */
    abstract protected function getApplicableOs();

    /**
     * @return bool
     */
    public function isApplicableOS()
    {
        return in_array($this->getOs(), $this->getApplicableOs());
    }

    /**
     * @return string
     */
    public function getOs()
    {
        return explode(' ', strtoupper(php_uname()))[0];
    }
}
