<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

/**
 * Provides common functionality for Behat isolators that are operating system-specific.
 *
 * This base class implements OS detection and applicability checking, allowing isolators to declare
 * which operating systems they support. Subclasses should extend this to create isolators
 * that perform OS-specific operations during Behat test isolation.
 */
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
