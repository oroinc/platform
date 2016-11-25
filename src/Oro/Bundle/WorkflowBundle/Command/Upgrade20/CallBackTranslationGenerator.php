<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

class CallBackTranslationGenerator implements ResourceTranslationGenerator
{
    /**
     * @var callable
     */
    private $callableFunc;

    /**
     * @param callable $callableFunc
     */
    public function __construct(callable $callableFunc)
    {
        $this->callableFunc = $callableFunc;
    }

    /**
     * @param ConfigResource $configResource
     * @return \Traversable
     */
    public function generate(ConfigResource $configResource)
    {
        return call_user_func($this->callableFunc, $configResource);
    }
}
