<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Metadata\MetaPropertyMetadata;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultActionProcessor;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads included entities using "get" action.
 * We have to do it because the entities returned by "create" or "update" actions
 * must be the same as the entities returned by "get" action.
 */
class LoadNormalizedIncludedEntities implements ProcessorInterface
{
    const INCLUDE_ID_META     = 'includeId';
    const INCLUDE_ID_PROPERTY = '__include_id__';

    /** @var ActionProcessorBagInterface */
    protected $processorBag;

    /**
     * @param ActionProcessorBagInterface $processorBag
     */
    public function __construct(ActionProcessorBagInterface $processorBag)
    {
        $this->processorBag = $processorBag;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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

    /**
     * @param FormContext        $context
     * @param object             $entity
     * @param string             $entityClass
     * @param string             $entityIncludeId
     * @param IncludedEntityData $entityData
     */
    protected function processIncludedEntity(
        FormContext $context,
        $entity,
        $entityClass,
        $entityIncludeId,
        IncludedEntityData $entityData
    ) {
        $getProcessor = $this->processorBag->getProcessor(ApiActions::GET);

        /** @var GetContext $getContext */
        $getContext = $getProcessor->createContext();
        $getContext->setVersion($context->getVersion());
        $getContext->getRequestType()->set($context->getRequestType());
        $getContext->setRequestHeaders($context->getRequestHeaders());
        $getContext->setHateoas($context->isHateoasEnabled());
        $getContext->setClassName($entityClass);
        $getContext->setId($entityData->getMetadata()->getIdentifierValue($entity));
        if (!$entityData->isExisting()) {
            $getContext->setResult($entity);
        }
        $getContext->skipGroup('security_check');
        $getContext->skipGroup(NormalizeResultActionProcessor::NORMALIZE_RESULT_GROUP);
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
