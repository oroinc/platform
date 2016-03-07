<?php

namespace Oro\Bundle\ImapBundle\Workflow\Action;

use JMS\JobQueueBundle\Entity\Job;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Action\Model\ContextAccessor;

class DependencyJob extends AbstractAction
{
    const OPTION_KEY_JOB = 'job';
    const OPTION_KEY_DEPENDENCY = 'dependency';

    /** @var string */
    protected $attribute;

    /** @var Job */
    protected $job;

    /** @var Job */
    protected $dependency;

    /** @var Registry */
    private $doctrine;

    /**
     * @param ContextAccessor       $contextAccessor
     * @param Registry              $doctrine
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        Registry $doctrine
    ) {
        parent::__construct($contextAccessor);
        $this->doctrine = $doctrine;
    }

    /**
     * @param mixed $context
     */
    protected function executeAction($context)
    {
        /** @var Job $job */
        $job = $this->contextAccessor->getValue($context, $this->job);
        /** @var Job $dependency */
        $dependency = $this->contextAccessor->getValue($context, $this->dependency);

        $job->addDependency($dependency);
        $job->setState(Job::STATE_PENDING);

        $this->doctrine->getManager()->persist($job);
        $this->doctrine->getManager()->flush();
    }

    /**
     * Initialize action based on passed options.
     *
     * @param array $options
     *
     * @return ActionInterface
     * @throws InvalidParameterException
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function initialize(array $options)
    {
        if (count($options) !== 2) {
            throw new InvalidParameterException('Two options must be defined.');
        }

        if (!isset($options[self::OPTION_KEY_JOB]) && !isset($options[0])) {
            throw new InvalidParameterException('Job must be defined.');
        }

        if (!isset($options[self::OPTION_KEY_DEPENDENCY]) && !isset($options[1])) {
            throw new InvalidParameterException('Dependency must be defined.');
        }

        $this->job = isset($options[self::OPTION_KEY_JOB])
            ? $options[self::OPTION_KEY_JOB] : $options[0];
        $this->dependency = isset($options[self::OPTION_KEY_DEPENDENCY])
            ? $options[self::OPTION_KEY_DEPENDENCY] : $options[1];
    }
}
