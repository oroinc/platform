<?php

namespace Oro\Bundle\CronBundle\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\JobQueueBundle\Entity\Job;
use Oro\Bundle\CronBundle\Entity\Manager\JobManager;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\Action\OptionsResolverTrait;
use Oro\Component\DoctrineUtils\ORM\EntityManagementTrait;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreateJobAction extends AbstractAction
{
    use EntityManagementTrait;
    use OptionsResolverTrait;
    const OPT_COMMAND             = 'command'; #job command to run
    const OPT_ARGS                = 'arguments'; #job command arguments
    const OPTION_ALLOW_DUPLICATES = 'allow_duplicates'; #allow same jobs to be added
    const OPTION_PRIORITY         = 'priority'; #job priority
    const OPTION_QUEUE            = 'queue'; #job queue name
    const OPTION_COMMIT           = 'commit'; #save to database right after creation of job
    const OPTION_ATTRIBUTE        = 'attribute'; #property path where job entity instance should be placed in context

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var array
     */
    private $options;

    /**
     * @var JobManager
     */
    private $jobManager;

    /**
     * @param ContextAccessor $contextAccessor
     * @param JobManager $jobManager
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        JobManager $jobManager,
        ManagerRegistry $managerRegistry
    ) {
        parent::__construct($contextAccessor);

        $this->jobManager = $jobManager;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        try {
            $this->options = $this->resolve($options);
        } catch (InvalidArgumentException $resolverException) {
            throw new InvalidParameterException(
                $resolverException->getMessage(),
                $resolverException->getCode(),
                $resolverException
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(self::OPT_COMMAND);

        $resolver->setAllowedTypes(
            self::OPTION_ATTRIBUTE,
            ['null', 'Symfony\Component\PropertyAccess\PropertyPathInterface']
        );

        $resolver->setAllowedTypes(self::OPTION_PRIORITY, ['int']);
        $resolver->setAllowedTypes(self::OPT_ARGS, ['array']);
        $resolver->setAllowedTypes(self::OPTION_QUEUE, ['string']);

        $resolver->setAllowedValues(self::OPT_COMMAND, function ($value) {
            return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
        });

        $resolver->setAllowedValues(self::OPTION_QUEUE, function ($value) {
        });

        $resolver->setNormalizer(
            self::OPT_ARGS,
            function (Options $options, $value) {
                $normalized = [];
                foreach ((array)$value as $commandArgumentOrOption) {
                    $normalized[] = (string)$commandArgumentOrOption;
                }

                return $normalized;
            }
        );

        $resolver->setDefaults(
            [
                self::OPT_ARGS => [],
                self::OPTION_QUEUE => Job::DEFAULT_QUEUE,
                self::OPTION_PRIORITY => Job::PRIORITY_DEFAULT,
                self::OPTION_ALLOW_DUPLICATES => false,
                self::OPTION_COMMIT => true
            ]
        );
    }

    /**
     * @param {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (null === $this->options) {
            throw new \RuntimeException('Action is not initialized.');
        }

        if (false === $this->canAddTheJob()) {
            return;
        }

        $job = Job::create(
            $this->options[self::OPT_COMMAND],
            $this->options[self::OPT_ARGS],
            true,
            $this->options[self::OPTION_QUEUE],
            $this->options[self::OPTION_PRIORITY]
        );

        $this->getObjectManager()->persist($job);

        if ($this->options[self::OPTION_COMMIT]) {
            $this->getObjectManager()->flush();
        }

        if ($this->options[self::OPTION_ATTRIBUTE]) {
            $this->contextAccessor->setValue($context, $this->options[self::OPTION_ATTRIBUTE], $job);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntityClass()
    {
        return 'JMS\JobQueueBundle\Entity\Job';
    }

    /**
     * {@inheritdoc}
     */
    protected function getManagerRegistry()
    {
        return $this->managerRegistry;
    }

    /**
     * Checks if we can add a Job to a queue
     * @return bool
     */
    private function canAddTheJob()
    {
        if ($this->options[self::OPTION_ALLOW_DUPLICATES]) {
            return true;
        }

        return false === $this->jobManager->hasJobInQueue(
            $this->options[self::OPT_COMMAND],
            json_encode($this->options[self::OPT_ARGS])
        );
    }
}
