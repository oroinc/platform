<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads included entities using "get" action.
 * We have to do it because the entities returned by "create" or "update" actions
 * must be the same as the entities returned by "get" action.
 */
class LoadNormalizedIncludedEntities implements ProcessorInterface
{
    private const INCLUDE_ID_META = 'includeId';
    private const INCLUDE_ID_PROPERTY = '__include_id__';

    private ActionProcessorBagInterface $processorBag;

    public function __construct(ActionProcessorBagInterface $processorBag)
    {
        $this->processorBag = $processorBag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var FormContext $context */

        $includedData = $context->getIncludedData();
        if (null === $includedData) {
            // there are no included data in the request
            return;
        }

        $includedEntities = $context->getIncludedEntities();
        if (null === $includedEntities) {
            // no included entities
            return;
        }

        foreach ($includedEntities as $entity) {
            $this->processIncludedEntity(
                $context,
                $entity,
                $includedEntities->getClass($entity),
                $includedEntities->getId($entity),
                $includedEntities->getData($entity)
            );
        }
    }

    private function processIncludedEntity(
        FormContext $context,
        object $entity,
        string $entityClass,
        string $entityIncludeId,
        IncludedEntityData $entityData
    ): void {
        $getProcessor = $this->processorBag->getProcessor(ApiAction::GET);

        /** @var GetContext $getContext */
        $getContext = $getProcessor->createContext();
        $getContext->setVersion($context->getVersion());
        $getContext->getRequestType()->set($context->getRequestType());
        $getContext->setRequestHeaders($context->getRequestHeaders());
        $getContext->setSharedData($context->getSharedData());
        $getContext->setHateoas($context->isHateoasEnabled());
        $getContext->setClassName($entityClass);
        $getContext->setId($entityData->getMetadata()->getIdentifierValue($entity));
        if (!$entityData->isExisting()) {
            $getContext->setResult($entity);
        }
        $getContext->skipGroup(ApiActionGroup::SECURITY_CHECK);
        $getContext->skipGroup(ApiActionGroup::NORMALIZE_RESULT);
        $getContext->setSoftErrorsHandling(true);

        $getProcessor->process($getContext);

        if ($getContext->hasErrors()) {
            $errors = $getContext->getErrors();
            foreach ($errors as $error) {
                $context->addError($error);
            }
        } else {
            $normalizedData = $getContext->getResult();
            $metadata = $getContext->getMetadata();

            $normalizedData[self::INCLUDE_ID_PROPERTY] = (string)$entityIncludeId;
            $metadata->addMetaProperty(new MetaPropertyMetadata(self::INCLUDE_ID_PROPERTY))
                ->setResultName(self::INCLUDE_ID_META);

            $entityData->setNormalizedData($normalizedData);
            $entityData->setMetadata($metadata);
        }
    }
}
