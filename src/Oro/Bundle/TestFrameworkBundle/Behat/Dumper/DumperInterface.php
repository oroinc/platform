<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Dumper;

interface DumperInterface
{
    /**
     * Dump initial state
     */
    public function dump();

    /**
     * Restore initial state
     */
    public function restore();
}
