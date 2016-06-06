<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Dumper;

interface DumperInterface
{
    public function dump();

    public function restore();
}
