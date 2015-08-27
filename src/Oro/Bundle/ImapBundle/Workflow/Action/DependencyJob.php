<?php

namespace Oro\Bundle\ImapBundle\Workflow\Action;

use JMS\JobQueueBundle\Entity\Job;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class DependencyJob extends AbstractAction
{
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function initialize(array $options)
    {
        if (count($options) !== 2) {
            throw new InvalidParameterException('Two options must be defined.');
        }

        if (!isset($options['job']) && !isset($options[0])) {
            throw new InvalidParameterException('Job must be defined.');
        }

        if (!isset($options['dependency']) && !isset($options[1])) {
            throw new InvalidParameterException('Dependency must be defined.');
        }

        $this->job = isset($options['job']) ? $options['job'] : $options[0];
        $this->dependency = isset($options['dependency']) ? $options['dependency'] : $options[1];
    }
}
