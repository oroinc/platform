<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationship;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads relationship data using "get" action.
 * This processor can be used when data returned by "update_relationship",
 * "add_relationship" or "delete_relationship" actions must be the same
 * as data returned by "get_relationship" action.
 */
class LoadNormalizedRelationship implements ProcessorInterface
{
    private ActionProcessorBagInterface $processorBag;

    public function __construct(ActionProcessorBagInterface $processorBag)
    {
        $this->processorBag = $processorBag;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

        if ($context->hasResult()) {
            // the normalized entity was already loaded
            return;
        }

        $parentEntity = $context->getParentEntity();
        if (!\is_object($parentEntity)) {
            // the parent entity does not exist
            return;
        }

        $getProcessor = $this->processorBag->getProcessor(ApiAction::GET);
        $getContext = $this->createGetContext($context, $getProcessor);
        $getProcessor->process($getContext);
        $this->processGetResult($getContext, $context);
    }

    private function createGetContext(
        ChangeRelationshipContext $context,
        ActionProcessorInterface $processor
    ): GetContext {
        /** @var GetContext $getContext */
        $getContext = $processor->createContext();
        $getContext->setVersion($context->getVersion());
        $getContext->getRequestType()->set($context->getRequestType());
        $getContext->setRequestHeaders($context->getRequestHeaders());
        $getContext->setSharedData($context->getSharedData());
        $getContext->setHateoas($context->isHateoasEnabled());
        $getContext->setParentAction($context->getAction());
        $getContext->setClassName($context->getParentClassName());
        $getContext->setId($context->getParentId());
        $getContext->setResult($context->getParentEntity());
        $getContext->addConfigExtra(new FilterFieldsConfigExtra([
            $context->getParentClassName() => [$context->getAssociationName()]
        ]));
        $getContext->skipGroup(ApiActionGroup::SECURITY_CHECK);
        $getContext->skipGroup(ApiActionGroup::DATA_SECURITY_CHECK);
        $getContext->skipGroup(ApiActionGroup::NORMALIZE_RESULT);
        $getContext->setSoftErrorsHandling(true);
        foreach ($context->getNormalizedEntityConfigExtras() as $extra) {
            $getContext->addConfigExtra($extra);
        }

        return $getContext;
    }

    private function processGetResult(GetContext $getContext, ChangeRelationshipContext $context): void
    {
        if ($getContext->hasErrors()) {
            $errors = $getContext->getErrors();
            foreach ($errors as $error) {
                $context->addError($error);
            }
        } else {
            $associationName = $context->getAssociationName();
            $getConfig = $getContext->getConfig()?->getField($associationName)?->getTargetEntity();
            if (null !== $getConfig) {
                $context->setConfig($getConfig);
            }
            $getMetadata = $getContext->getMetadata()?->getAssociation($associationName)?->getTargetMetadata();
            if (null !== $getMetadata) {
                $context->setMetadata($getMetadata);
            }

            $data = $getContext->getResult();
            $associationName = $context->getAssociationName();
            if (\array_key_exists($associationName, $data)) {
                $context->setResult($this->normalizeGetResult(
                    $data[$associationName],
                    $context->getConfig(),
                    $context->isCollection()
                ));
            }
        }
    }

    private function normalizeGetResult(
        mixed $associationData,
        EntityDefinitionConfig $config,
        bool $isCollection
    ): mixed {
        $idFieldNames = $config->getIdentifierFieldNames();
        if (\count($idFieldNames) === 1) {
            $idFieldName = $idFieldNames[0];
            if ($isCollection) {
                if ($associationData) {
                    foreach ($associationData as $i => $item) {
                        if (null !== $item && !\is_array($item)) {
                            $associationData[$i] = [$idFieldName => $item];
                        }
                    }
                }
            } elseif (null !== $associationData && !\is_array($associationData)) {
                $associationData = [$idFieldName => $associationData];
            }
        }

        return $associationData;
    }
}
