<?php

namespace Oro\Bundle\ApiBundle\Processor\GetConfig\JsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Processor\GetConfig\AbstractAddStatusCodes;
use Oro\Bundle\ApiBundle\Processor\GetConfig\ConfigContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds possible status codes specific for REST API that conforms the JSON:API specification.
 */
class CompleteStatusCodes extends AbstractAddStatusCodes
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ConfigContext $context */

        $this->addStatusCodes(
            $context->getResult(),
            $context->getClassName(),
            $context->getTargetAction()
        );
    }

    private function addStatusCodes(
        EntityDefinitionConfig $definition,
        string $entityClass,
        string $targetAction
    ): void {
        switch ($targetAction) {
            case ApiAction::CREATE:
                $this->addStatusCodesForCreate($definition, $entityClass);
                break;
            case ApiAction::UPDATE:
                $this->addStatusCodesForUpdate($definition->getStatusCodes());
                break;
        }
    }

    /**
     * Adds status codes for "create" action.
     */
    private function addStatusCodesForCreate(EntityDefinitionConfig $definition, string $entityClass): void
    {
        $statusCodes = $definition->getStatusCodes();
        if (!$statusCodes->hasCode(Response::HTTP_CONFLICT)) {
            $description = 'Returned when the specified entity type does not match the server\'s endpoint';
            if (!$this->hasIdGenerator($definition, $entityClass)) {
                $description .= ' or a client-generated identifier already exists';
            }
            $this->addStatusCode($statusCodes, Response::HTTP_CONFLICT, $description);
        }
    }

    /**
     * Adds status codes for "update" action.
     */
    private function addStatusCodesForUpdate(StatusCodesConfig $statusCodes): void
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_CONFLICT,
            'Returned when the specified entity type and identifier do not match the server\'s endpoint'
        );
    }

    private function hasIdGenerator(EntityDefinitionConfig $definition, string $entityClass): bool
    {
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            return false;
        }

        $idFieldNames = $definition->getIdentifierFieldNames();
        if (\count($idFieldNames) !== 1) {
            return false;
        }

        $classMetadata = $this->doctrineHelper->getEntityMetadataForClass($entityClass);
        if (!$classMetadata->usesIdGenerator()) {
            return false;
        }

        $idFieldName = reset($idFieldNames);
        $idField = $definition->getField($idFieldName);
        if (null !== $idField) {
            $idFieldName = $idField->getPropertyPath($idFieldName);
        }
        $idFieldNames = $classMetadata->getIdentifierFieldNames();

        return \count($idFieldNames) === 1 && reset($idFieldNames) === $idFieldName;
    }
}
