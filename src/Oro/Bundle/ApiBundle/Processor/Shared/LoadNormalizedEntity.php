<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads whole entity by its identifier using "get" action.
 * We have to do it because the entity returned by "create" or "update" actions
 * must be the same as the entity returned by "get" action.
 */
class LoadNormalizedEntity implements ProcessorInterface
{
    public const OPERATION_NAME = 'normalized_entity_loaded';

    private ActionProcessorBagInterface $processorBag;
    private bool $reuseExistingEntity;

    /**
     * @param ActionProcessorBagInterface $processorBag
     * @param bool                        $reuseExistingEntity Set TRUE to prevent loading of the entity
     *                                                         by the "get" action and use the entity
     *                                                         from the current context
     */
    public function __construct(ActionProcessorBagInterface $processorBag, bool $reuseExistingEntity = false)
    {
        $this->processorBag = $processorBag;
        $this->reuseExistingEntity = $reuseExistingEntity;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the normalized entity was already loaded
            return;
        }

        $entityId = $context->getId();
        if (null !== $entityId) {
            $getProcessor = $this->processorBag->getProcessor(ApiAction::GET);
            $getContext = $this->createGetContext($context, $getProcessor);
            $getProcessor->process($getContext);
            $this->processGetResult($getContext, $context);
            $context->setProcessed(self::OPERATION_NAME);
        } elseif (!$context->hasIdentifierFields()) {
            // remove the result if it was not normalized yet
            if ($context->hasResult() && \is_object($context->getResult())) {
                $context->removeResult();
            }
            $context->setProcessed(self::OPERATION_NAME);
        }
    }

    protected function createGetContext(SingleItemContext $context, ActionProcessorInterface $processor): GetContext
    {
        /** @var GetContext $getContext */
        $getContext = $processor->createContext();
        $getContext->setVersion($context->getVersion());
        $getContext->getRequestType()->set($context->getRequestType());
        $getContext->setRequestHeaders($context->getRequestHeaders());
        $getContext->setSharedData($context->getSharedData());
        $getContext->setHateoas($context->isHateoasEnabled());
        $getContext->setClassName($context->getClassName());
        $getContext->setId($context->getId());
        if ($this->reuseExistingEntity && $context->hasResult()) {
            $getContext->setResult($context->getResult());
        }
        $getContext->skipGroup(ApiActionGroup::SECURITY_CHECK);
        $getContext->skipGroup(ApiActionGroup::DATA_SECURITY_CHECK);
        $getContext->skipGroup(ApiActionGroup::NORMALIZE_RESULT);
        $getContext->setSoftErrorsHandling(true);

        return $getContext;
    }

    protected function processGetResult(GetContext $getContext, SingleItemContext $context): void
    {
        if ($getContext->hasErrors()) {
            $errors = $getContext->getErrors();
            foreach ($errors as $error) {
                $context->addError($error);
            }
        } else {
            $context->setConfigExtras($getContext->getConfigExtras());
            $getConfig = $getContext->getConfig();
            if (null !== $getConfig) {
                $context->setConfig($getConfig);
            }
            $getConfigSections = $getContext->getConfigSections();
            foreach ($getConfigSections as $configSection) {
                if ($getContext->hasConfigOf($configSection)) {
                    $context->setConfigOf($configSection, $getContext->getConfigOf($configSection));
                }
            }

            $getMetadata = $getContext->getMetadata();
            if (null !== $getMetadata) {
                $context->setMetadata($getMetadata);
            }

            $responseHeaders = $context->getResponseHeaders();
            $getResponseHeaders = $getContext->getResponseHeaders();
            foreach ($getResponseHeaders as $key => $value) {
                $responseHeaders->set($key, $value);
            }

            $context->setInfoRecords($getContext->getInfoRecords());
            $context->setResult($getContext->getResult());
        }
    }
}
