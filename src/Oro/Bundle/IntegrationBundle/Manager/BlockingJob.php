<?php

namespace Oro\Bundle\IntegrationBundle\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\IntegrationBundle\Exception\LogicException;
use Oro\Bundle\IntegrationBundle\Provider\BlockingJobsInterface;

class BlockingJob
{
    /** @var array|ArrayCollection[] */
    protected $jobs = [];

    public function __construct()
    {
        $this->jobs = new ArrayCollection();
    }

    /**
     * @param $channelType
     * @param BlockingJobsInterface $blockingJobProvider
     *
     * @return $this
     */
    public function addJob($channelType, BlockingJobsInterface $blockingJobProvider)
    {
        if (!$this->jobs->containsKey($channelType)) {
            $this->jobs->set($channelType, $blockingJobProvider);
        } else {
            throw new LogicException(sprintf('Trying to redeclare blockingJob provider  "%s".', $channelType));
        }

        return $this;
    }

    public function hasBlockingJobs($channelType) {
        return isset($this->jobs[$channelType]) && count($this->jobs[$channelType]) > 0;
    }

    public function getBlockingJobs($channelType) {
        return $this->jobs[$channelType];
    }
}
