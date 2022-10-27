<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper\Substitution;

use Oro\Bundle\ActionBundle\Exception\CircularReferenceException;
use Oro\Bundle\ActionBundle\Helper\Substitution\CircularReferenceSearch;

class CircularReferenceSearchTest extends \PHPUnit\Framework\TestCase
{
    public function testAssertNoCircularReference()
    {
        CircularReferenceSearch::assert([
            'a' => 'b',
            'b' => 'c',
        ]);
    }

    public function testAssertWithCircularReference()
    {
        $this->expectException(CircularReferenceException::class);

        CircularReferenceSearch::assert([
            'a' => 'b',
            'b' => 'a',
        ]);
    }
}
