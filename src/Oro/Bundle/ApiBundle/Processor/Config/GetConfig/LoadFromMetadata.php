<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigProcessor;

class LoadFromMetadata implements ProcessorInterface
{
    /** @var ConfigProcessor */
    protected $buildConfigProcessor;

    /**
     * @param ConfigProcessor $buildConfigProcessor
     */
    public function __construct(ConfigProcessor $buildConfigProcessor)
    {
        $this->buildConfigProcessor = $buildConfigProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        $action = $context->getAction();
        $context->setAction($this->buildConfigProcessor->createContext()->getAction());
        try {
            $this->buildConfigProcessor->process($context);
            $context->setAction($action);
        } catch (\Exception $e) {
            $context->setAction($action);

            throw $e;
        }
    }
}
