<?php

namespace Oro\Bundle\ImportExportBundle\Model\Action;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ImportExportBundle\Job\JobExecutor;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

/**
 * Run batch job
 *
 * Usage:
 * @execute_job:
 *      jobType: $.jobType
 *      jobName: $.jobName
 *      configuration: $.configuration
 *      attribute: $.jobResult
 */
class ExecuteJobAction extends AbstractAction
{
    const NAME = 'execute_job';

    /** @var string */
    protected $jobType;

    /** @var string */
    protected $jobName;

    /** @var array */
    protected $configuration;

    /** @var string */
    protected $attribute;

    /** @var JobExecutor */
    protected $jobExecutor;

    /**
     * @param ContextAccessor $contextAccessor
     * @param JobExecutor $jobExecutor
     */
    public function __construct(ContextAccessor $contextAccessor, JobExecutor $jobExecutor)
    {
        parent::__construct($contextAccessor);

        $this->jobExecutor = $jobExecutor;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $jobType = $this->getJobType($context);
        $jobName = $this->getJobName($context);
        $configuration = $this->getConfiguration($context);

        $jobResult = $this->jobExecutor->executeJob($jobType, $jobName, $configuration);

        if (!$jobResult->isSuccessful() && $jobResult->getFailureExceptions()) {
            throw new \RuntimeException(implode(PHP_EOL, $jobResult->getFailureExceptions()));
        }

        $this->contextAccessor->setValue($context, $this->attribute, $jobResult);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (empty($options['jobType'])) {
            throw new InvalidParameterException('Parameter "jobType" is required.');
        }

        if (empty($options['jobName'])) {
            throw new InvalidParameterException('Parameter "jobName" is required.');
        }

        if (!array_key_exists('configuration', $options)) {
            throw new InvalidParameterException('Parameter "configuration" is required.');
        }

        if (empty($options['attribute'])) {
            throw new InvalidParameterException('Parameter "attribute" is required.');
        }

        $this->jobType = $options['jobType'];
        $this->jobName = $options['jobName'];
        $this->configuration = $options['configuration'];
        $this->attribute = $options['attribute'];

        return $this;
    }

    /**
     * @param mixed $context
     * @return string
     * @throws InvalidParameterException
     */
    protected function getJobType($context)
    {
        $jobType = $this->contextAccessor->getValue($context, $this->jobType);
        if (!is_string($jobType)) {
            throw new InvalidParameterException(
                sprintf(
                    'Action "%s" expects string in parameter "jobType", %s is given.',
                    self::NAME,
                    $this->getType($jobType)
                )
            );
        }

        return $jobType;
    }

    /**
     * @param mixed $context
     * @return string
     * @throws InvalidParameterException
     */
    protected function getJobName($context)
    {
        $jobName = $this->contextAccessor->getValue($context, $this->jobName);
        if (!is_string($jobName)) {
            throw new InvalidParameterException(
                sprintf(
                    'Action "%s" expects string in parameter "jobName", %s is given.',
                    self::NAME,
                    $this->getType($jobName)
                )
            );
        }

        return $jobName;
    }

    /**
     * @param mixed $context
     * @return string
     * @throws InvalidParameterException
     */
    protected function getConfiguration($context)
    {
        $configuration = $this->contextAccessor->getValue($context, $this->configuration);
        if (!is_array($configuration)) {
            throw new InvalidParameterException(
                sprintf(
                    'Action "%s" expects array in parameter "configuration", %s is given.',
                    self::NAME,
                    $this->getType($configuration)
                )
            );
        }

        $configuration = $this->parseArrayValues($context, $configuration);

        return $configuration;
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function getType($value)
    {
        if (is_object($value)) {
            return ClassUtils::getClass($value);
        }

        return gettype($value);
    }

    /**
     * @param mixed $context
     * @param array $data
     * @return array
     */
    protected function parseArrayValues($context, array $data)
    {
        foreach ($data as $key => $value) {
            if ($value instanceof PropertyPath) {
                $data[$key] = $this->contextAccessor->getValue($context, $value);
            }
        }

        return $data;
    }
}
