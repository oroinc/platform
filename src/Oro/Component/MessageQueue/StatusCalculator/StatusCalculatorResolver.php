<?php

namespace Oro\Component\MessageQueue\StatusCalculator;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Oro\Component\MessageQueue\Job\Job;

/**
 * Resolve calculator to calculate job status and progress
 */
class StatusCalculatorResolver
{
    /** @var CollectionCalculator */
    private $collectionCalculator;

    /** @var QueryCalculator */
    private $queryCalculator;

    public function __construct(CollectionCalculator $collectionCalculator, QueryCalculator $queryCalculator)
    {
        $this->collectionCalculator = $collectionCalculator;
        $this->queryCalculator = $queryCalculator;
    }

    /**
     * @throws \LogicException
     *
     * @return AbstractStatusCalculator
     */
    public function getCalculatorForRootJob(Job $rootJob)
    {
        $childJobs = $rootJob->getChildJobs();

        if ($childJobs instanceof PersistentCollection) {
            $this->queryCalculator->init($rootJob);

            return $this->queryCalculator;
        }

        if ($childJobs instanceof Collection) {
            $this->collectionCalculator->init($rootJob);

            return $this->collectionCalculator;
        }

        throw new \LogicException(
            sprintf(
                'Can\'t find status and progress calculator for this type of child jobs: "%s".',
                is_object($childJobs) ? get_class($childJobs) : gettype($childJobs)
            )
        );
    }
}
