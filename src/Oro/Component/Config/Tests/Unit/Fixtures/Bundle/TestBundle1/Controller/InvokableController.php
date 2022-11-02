<?php

namespace Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\Controller;

class InvokableController
{
    public function __invoke(): array
    {
        return [];
    }
}
