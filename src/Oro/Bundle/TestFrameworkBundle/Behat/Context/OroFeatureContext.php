<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Behat\MinkExtension\Context\RawMinkContext;

class OroFeatureContext extends RawMinkContext
{
    use AssertTrait;

    /**
     * @param \Closure $lambda
     * @return false|mixed Return false if closure throw error or return not true value.
     *                     Return value that return closure
     */
    public function spin(\Closure $lambda)
    {
        $time = 60;

        while ($time > 0) {
            try {
                if ($result = $lambda($this)) {
                    return $result;
                }
            } catch (\Exception $e) {
                // do nothing
            }
            usleep(250000);
            $time -= 0.25;
        }
        return false;
    }
}
