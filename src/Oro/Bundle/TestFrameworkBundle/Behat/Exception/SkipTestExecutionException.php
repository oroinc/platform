<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Exception;

/**
 * Exception to skip test execution process.
 */
class SkipTestExecutionException extends \Exception
{
    /**
     * @param $code [0 => success, 1 => failed, 2 => skip]
     */
    public function __construct(public $code = 0)
    {
        parent::__construct('Skip behat test execution', $code);
    }
}
