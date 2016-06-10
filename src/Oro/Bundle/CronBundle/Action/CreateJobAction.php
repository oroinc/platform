<?php

namespace Oro\Bundle\CronBundle\Action;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use JMS\JobQueueBundle\Entity\Job;

use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CronBundle\Entity\Manager\JobManager;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Component\Action\OptionsResolverTrait;

class CreateJobAction extends AbstractAction
{
    use OptionsResolverTrait;

    const OPTION_COMMAND = 'command'; //job command to run
    const OPTION_ARGUMENTS = 'arguments'; //job command arguments
    const OPTION_ALLOW_DUPLICATES = 'allow_duplicates'; //allow same jobs to be added
    const OPTION_PRIORITY = 'priority'; //job priority
    const OPTION_QUEUE = 'queue'; //job queue name
    const OPTION_COMMIT = 'commit'; //save to database right after creation of job
    const OPTION_ATTRIBUTE = 'attribute'; //property path where job entity instance should be placed in context

    /** @var ManagerRegistry */
    private $registry;

    /** @var array */
    private $options;

    /** @var JobManager */
    private $jobManager;

    /**
     * @param ContextAccessor $contextAccessor
     * @param JobManager $jobManager
     * @param ManagerRegistry $registry
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        JobManager $jobManager,
        ManagerRegistry $registry
    ) {
        parent::__construct($contextAccessor);

        $this->jobManager = $jobManager;
        $this->registry = $registry;
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
        $resolver->setRequired(self::OPTION_COMMAND)
            ->setDefined(
                [
                    self::OPTION_ATTRIBUTE,
                    self::OPTION_PRIORITY,
                    self::OPTION_ARGUMENTS,
                    self::OPTION_QUEUE
                ]
            )
            ->setAllowedTypes(
                self::OPTION_ATTRIBUTE,
                ['null', 'Symfony\Component\PropertyAccess\PropertyPathInterface']
            )
            ->setAllowedTypes(self::OPTION_PRIORITY, ['int'])
            ->setAllowedTypes(self::OPTION_ARGUMENTS, ['array'])
            ->setAllowedTypes(self::OPTION_QUEUE, ['string'])
            ->setAllowedValues(
                self::OPTION_COMMAND,
                function ($value) {
                    return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
                }
            )
            ->setDefaults(
                [
                    self::OPTION_ARGUMENTS => [],
                    self::OPTION_QUEUE => Job::DEFAULT_QUEUE,
                    self::OPTION_PRIORITY => Job::PRIORITY_DEFAULT,
                    self::OPTION_ALLOW_DUPLICATES => false,
                    self::OPTION_COMMIT => true,
                    self::OPTION_ATTRIBUTE => null
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        if (null === $this->options) {
            throw new \RuntimeException('Action is not initialized.');
        }

        $args = [];
        foreach ($this->options[self::OPTION_ARGUMENTS] as $key => $value) {
            $value = $this->contextAccessor->getValue($context, $value);
            $args[] = sprintf('%s=%s', $key, $value);
        }

        if (false === $this->canAddTheJob($args)) {
            return;
        }

        $job = Job::create(
            $this->options[self::OPTION_COMMAND],
            $args,
            true,
            $this->options[self::OPTION_QUEUE],
            $this->options[self::OPTION_PRIORITY]
        );

        $manager = $this->getObjectManager();
        $manager->persist($job);

        if ($this->options[self::OPTION_COMMIT]) {
            $manager->flush();
        }

        if ($this->options[self::OPTION_ATTRIBUTE]) {
            $this->contextAccessor->setValue($context, $this->options[self::OPTION_ATTRIBUTE], $job);
        }
    }

    /**
     * Checks if we can add a Job to a queue
     *
     * @param array $args
     * @return bool
     */
    private function canAddTheJob(array $args)
    {
        if ($this->options[self::OPTION_ALLOW_DUPLICATES]) {
            return true;
        }

        return false === $this->jobManager->hasJobInQueue(
            $this->options[self::OPTION_COMMAND],
            json_encode($args)
        );
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->registry->getManagerForClass('JMSJobQueueBundle:Job');
    }
}
