<?php

namespace Oro\Bundle\BatchBundle\Job;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;

/**
 * Common interface for Job repositories which should handle how job are stored, updated
 * and retrieved
 *
 * Inspired by Spring Batch org.springframework.batch.core.repository.JobRepository;
 *
 * @author    Benoit Jacquemont <benoit@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/MIT MIT
 */
interface JobRepositoryInterface
{
    /**
     * Create a JobExecution object
     */
    public function createJobExecution(JobInstance $job): JobExecution;

    /**
     * Update a JobExecution
     */
    public function updateJobExecution(JobExecution $jobExecution): JobExecution;

    /**
     * Update a StepExecution
     */
    public function updateStepExecution(StepExecution $stepExecution): StepExecution;
}
