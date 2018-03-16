<?php

namespace Oro\Component\MessageQueue\StatusCalculator;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Oro\Component\MessageQueue\Job\Job;

class StatusCalculatorResolver
{
    /**
     * @var $collectionCalculator CollectionCalculator
     */
    private $collectionCalculator;

    /**
     * @var $queryCalculator QueryCalculator
     */
    private $queryCalculator;

    /**
     * @param CollectionCalculator $collectionCalculator
     * @param QueryCalculator $queryCalculator
     */
    public function __construct(CollectionCalculator $collectionCalculator, QueryCalculator $queryCalculator)
    {
        $this->collectionCalculator = $collectionCalculator;
        $this->queryCalculator = $queryCalculator;
    }

    /**
     * @throws \LogicException
     *
     * @param Job $rootJob
     *
     * @return AbstractStatusCalculator
     */
    public function getCalculatorForRootJob(Job $rootJob)
    {
        $childJobs = $rootJob->getChildJobs();

        if ($childJobs instanceof PersistentCollection && !$childJobs->isInitialized()) {
            $this->queryCalculator->init($rootJob);
            return $this->queryCalculator;
        }

        if ($childJobs instanceof Collection || $childJobs instanceof PersistentCollection) {
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
