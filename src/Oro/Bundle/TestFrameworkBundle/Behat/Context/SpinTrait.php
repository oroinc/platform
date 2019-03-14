<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

/**
 * Provides spin function.
 */
trait SpinTrait
{
    /**
     * @param \Closure $lambda
     * @param int $timeLimit in seconds
     * @return null|mixed Return null if closure throw error or return not true value.
     *                    Return value that return closure.
     */
    protected function spin(\Closure $lambda, $timeLimit = 60)
    {
        $time = $timeLimit;

        while ($time > 0) {
            $start = microtime(true);
            try {
                $result = $lambda($this);
                if ($result) {
                    return $result;
                }
            } catch (\Exception $e) {
                // do nothing
            } catch (\Throwable $e) {
                // do nothing
            }
            usleep(50000);
            $time -= microtime(true) - $start;
        }
        return null;
    }
}
