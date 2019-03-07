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
     * @param bool $strict
     * @return null|mixed Return null if closure throw error or return not true value.
     *                    Return value that return closure.
     */
    protected function spin(\Closure $lambda, $timeLimit = 60, $strict = false)
    {
        $limitOverride = $this->getSpinLimit();
        $time = !$strict && $limitOverride > 0 ? $limitOverride : $timeLimit;

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

    /**
     * @return int
     */
    private function getSpinLimit(): int
    {
        return (int) getenv('BEHAT_SPIN_LIMIT');
    }
}
