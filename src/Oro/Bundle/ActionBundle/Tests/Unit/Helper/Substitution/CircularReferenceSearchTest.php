<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper\Substitution;

use Oro\Bundle\ActionBundle\Exception\CircularReferenceException;
use Oro\Bundle\ActionBundle\Helper\Substitution\CircularReferenceSearch;
use PHPUnit\Framework\TestCase;

class CircularReferenceSearchTest extends TestCase
{
    public function testAssertNoCircularReference(): void
    {
        CircularReferenceSearch::assert([
            'a' => 'b',
            'b' => 'c',
        ]);
    }

    public function testAssertWithCircularReference(): void
    {
        $this->expectException(CircularReferenceException::class);

        CircularReferenceSearch::assert([
            'a' => 'b',
            'b' => 'a',
        ]);
    }
}
