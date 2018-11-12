<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Util;

use Oro\Bundle\SegmentBundle\Validator\Constraints\Sorting;
use Oro\Bundle\SegmentBundle\Validator\SortingValidator;
use Symfony\Component\Validator\Constraint;

class SortingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function getTargets()
    {
        $sortingConstraint = new Sorting();
        static::assertEquals(Constraint::CLASS_CONSTRAINT, $sortingConstraint->getTargets());
    }

    /**
     * @test
     */
    public function validatedBy()
    {
        $sortingConstraint = new Sorting();
        static::assertEquals(SortingValidator::class, $sortingConstraint->validatedBy());
    }
}
