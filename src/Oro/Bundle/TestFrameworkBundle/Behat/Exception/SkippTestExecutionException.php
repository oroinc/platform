<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Exception;

/**
 * Exception to skipp test execution process.
 */
class SkippTestExecutionException extends \Exception
{
    /**
     * @param $code [0 => success, 1 => failed, 2 => skipp]
     */
    public function __construct(public $code = 0)
    {
        parent::__construct('Skipp behat test execution', $code);
    }
}
