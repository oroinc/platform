<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper\Substitution;

use Oro\Bundle\ActionBundle\Helper\Substitution\CircularReferenceSearch;

class CircularReferenceSearchTest extends \PHPUnit\Framework\TestCase
{
    public function testAssert()
    {
        CircularReferenceSearch::assert([
            'a' => 'b',
            'b' => 'c',
        ]);

        $this->expectException('Oro\Bundle\ActionBundle\Exception\CircularReferenceException');

        CircularReferenceSearch::assert([
            'a' => 'b',
            'b' => 'a',
        ]);
    }
}
