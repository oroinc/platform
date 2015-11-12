<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\BuildConfigProcessor;

class GetConfigFromMetadata implements ProcessorInterface
{
    /** @var BuildConfigProcessor */
    protected $buildConfigProcessor;

    /**
     * @param BuildConfigProcessor $buildConfigProcessor
     */
    public function __construct(BuildConfigProcessor $buildConfigProcessor)
    {
        $this->buildConfigProcessor = $buildConfigProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetConfigContext $context */

        if ($context->hasResult()) {
            // a config is already set
            return;
        }

        $entityClass = $context->getClassName();
        if (!$entityClass) {
            // an entity type is not specified
            return;
        }

        $buildConfigContext = $this->buildConfigProcessor->createContext();
        $buildConfigContext->setAction('build_config');
        $buildConfigContext->setRequestType($context->getRequestType());
        $buildConfigContext->setClassName($entityClass);

        $this->buildConfigProcessor->process($buildConfigContext);

        $config = [];
        if ($buildConfigContext->hasResult()) {
            $config['definition'] = $buildConfigContext->getResult();
        }
        if ($buildConfigContext->hasFilters()) {
            $config['filters'] = $buildConfigContext->getFilters();
        }
        if ($buildConfigContext->hasSorters()) {
            $config['sorters'] = $buildConfigContext->getSorters();
        }

        if (!empty($config)) {
            $context->setResult($config);
        }
    }
}
