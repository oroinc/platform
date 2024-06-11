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
     * @param int $spinDelay in microseconds, values grater than 1M will be divided by 1M and passed to sleep as seconds
     * @return null|mixed Return null if closure throw error or return not true value.
     *                    Return value that return closure.
     */
    protected function spin(\Closure $lambda, $timeLimit = 60, $spinDelay = 50000)
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

            if ($spinDelay < 1000000) {
                usleep($spinDelay);
            } else {
                sleep($spinDelay / 1000000);
            }
            $time -= microtime(true) - $start;
        }
        return null;
    }
}
