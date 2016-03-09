<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Helper\Substitution;

use Oro\Bundle\ActionBundle\Helper\Substitution\CircularReferenceSearch;

class CircularReferenceSearchTest extends \PHPUnit_Framework_TestCase
{

    public function testAssertException()
    {
        $this->setExpectedException('Oro\Bundle\ActionBundle\Exception\CircularReferenceException');

        CircularReferenceSearch::assert(
            [
                'a' => 'b',
                'b' => 'a'
            ]
        );
    }


    public function testAssertOk()
    {
        CircularReferenceSearch::assert(
            [
                'a' => 'b',
                'b' => 'c'
            ]
        );
    }
}
