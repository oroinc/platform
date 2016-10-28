<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

class CallBackTranslationGenerator implements ResourceTranslationGenerator
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * @param ConfigResource $configResource
     * @return \Traversable
     */
    public function generate(ConfigResource $configResource)
    {
        return call_user_func($this->callable, $configResource);
    }
}
