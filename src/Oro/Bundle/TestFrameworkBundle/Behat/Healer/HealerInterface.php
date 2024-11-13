<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Healer;

use Behat\Testwork\Call\Call;
use Behat\Testwork\Call\CallResult;

/**
 * Base interface of self healer extensions.
 */
interface HealerInterface
{
    public function supports(Call $call): bool;

    public function getLabel(): string;

    /**
     * Will return true if successfully processed and false otherwise
     */
    public function process(Call &$call, CallResult $failedCall): bool;

    /**
     * The test should fail even if the failed step has been corrected
     */
    public function fallInAnyResult(): bool;
}
