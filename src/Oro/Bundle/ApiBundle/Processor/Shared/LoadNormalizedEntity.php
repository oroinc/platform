<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Processor\RequestActionProcessor;
use Oro\Bundle\ApiBundle\Request\ApiActions;

/**
 * Loads whole entity by its id using "get" action.
 * We have to do it because the entity returned by "create" or "update" actions
 * must be the same as the entity returned by "get" action.
 */
class LoadNormalizedEntity implements ProcessorInterface
{
    /** @var ActionProcessorBagInterface */
    protected $processorBag;

    /** @var bool */
    protected $reuseExistingEntity;

    /**
     * @param ActionProcessorBagInterface $processorBag
     * @param bool                        $reuseExistingEntity Set TRUE to prevent loading of the entity
     *                                                         by the "get" action and use the entity
     *                                                         from the current context
     */
    public function __construct(ActionProcessorBagInterface $processorBag, $reuseExistingEntity = false)
    {
        $this->processorBag = $processorBag;
        $this->reuseExistingEntity = $reuseExistingEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        $entityId = $context->getId();
        if (null === $entityId) {
            // undefined entity
            return;
        }

        $getProcessor = $this->processorBag->getProcessor(ApiActions::GET);

        /** @var GetContext $getContext */
        $getContext = $getProcessor->createContext();
        $getContext->setId($entityId);
        $getContext->skipGroup('security_check');
        $getContext->skipGroup(RequestActionProcessor::NORMALIZE_RESULT_GROUP);
        $this->prepareGetContext($getContext, $context);

        $getProcessor->process($getContext);

        $this->processGetResult($getContext, $context);

    }

    /**
     * @param GetContext        $getContext
     * @param SingleItemContext $context
     */
    protected function prepareGetContext(GetContext $getContext, SingleItemContext $context)
    {
        $getContext->setVersion($context->getVersion());
        $getContext->getRequestType()->set($context->getRequestType());
        $getContext->setRequestHeaders($context->getRequestHeaders());
        $getContext->setClassName($context->getClassName());
        if ($this->reuseExistingEntity && $context->hasResult()) {
            $getContext->setResult($context->getResult());
        }
    }

    /**
     * @param GetContext        $getContext
     * @param SingleItemContext $context
     */
    protected function processGetResult(GetContext $getContext, SingleItemContext $context)
    {
        if ($getContext->hasErrors()) {
            $errors = $getContext->getErrors();
            foreach ($errors as $error) {
                $context->addError($error);
            }
        } else {
            $context->setConfigExtras($getContext->getConfigExtras());
            if ($getContext->hasConfig()) {
                $context->setConfig($getContext->getConfig());
            }
            $getConfigSections = $getContext->getConfigSections();
            foreach ($getConfigSections as $configSection) {
                if ($getContext->hasConfigOf($configSection)) {
                    $context->setConfigOf($configSection, $getContext->getConfigOf($configSection));
                }
            }

            if ($getContext->hasMetadata()) {
                $context->setMetadata($getContext->getMetadata());
            }

            $getResponseHeaders = $getContext->getResponseHeaders();
            $responseHeaders = $context->getResponseHeaders();
            foreach ($getResponseHeaders as $key => $value) {
                $responseHeaders->set($key, $value);
            }

            $context->setResult($getContext->getResult());
        }
    }
}
