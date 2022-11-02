<?php

namespace Oro\Bundle\BatchBundle\Step;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\BatchBundle\Job\JobRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Instantiates a Step
 */
class StepFactory
{
    private EventDispatcherInterface $eventDispatcher;

    private JobRepositoryInterface $jobRepository;

    private Inflector $inflector;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        JobRepositoryInterface $jobRepository,
        Inflector $inflector
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->jobRepository = $jobRepository;
        $this->inflector = $inflector;
    }

    public function createStep(string $title, string $class, array $services, array $parameters): ItemStep
    {
        $step = new $class($title);
        $step->setEventDispatcher($this->eventDispatcher);
        $step->setJobRepository($this->jobRepository);

        foreach ($services as $setter => $service) {
            $method = 'set' . $this->inflector->camelize($setter);
            $step->$method($service);
        }

        foreach ($parameters as $setter => $param) {
            $method = 'set' . $this->inflector->camelize($setter);
            $step->$method($param);
        }

        return $step;
    }
}
