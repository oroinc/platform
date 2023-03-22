<?php

namespace Oro\Component\Testing\Assert;

use Oro\Bundle\MessageQueueBundle\Job\JobManager;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\Comparator\ArrayComparator;
use SebastianBergmann\Comparator\ComparisonFailure;

/** It is used for checking database on unprocessed unique jobs */
class UniqueJobsProcessedConstraint extends \PHPUnit\Framework\Constraint\Constraint
{
    private bool $expected;

    public function __construct(bool $expected = true)
    {
        $this->expected = $expected;
    }

    /** @param JobManager $jobManager */
    public function matches($jobManager): bool
    {
        if (!($jobManager instanceof JobManager)) {
            throw new \Exception(sprintf('The jobManager argument must be an instance of %s', JobManager::class));
        }

        $uniqueJobs = $jobManager->getUniqueJobs();

        if ($this->expected && empty($uniqueJobs)) {
            return true;
        }

        if (!$this->expected && !empty($uniqueJobs)) {
            return true;
        }

        if ($uniqueJobs) {
            try {
                $comparator = new ArrayComparator();

                $comparator->assertEquals([], $uniqueJobs);
            } catch (ComparisonFailure $f) {
                throw new ExpectationFailedException(
                    trim($f->getMessage()),
                    $f
                );
            }
        }

        return false;
    }

    public function toString(): string
    {
        return 'the unique jobs table is empty';
    }

    protected function failureDescription($jobManager): string
    {
        return 'the unique jobs table is not empty';
    }
}
