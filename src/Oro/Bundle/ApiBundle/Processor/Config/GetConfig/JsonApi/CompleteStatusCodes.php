<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetConfig\JsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\StatusCodesConfig;
use Oro\Bundle\ApiBundle\Processor\Config\ConfigContext;
use Oro\Bundle\ApiBundle\Processor\Config\GetConfig\AbstractAddStatusCodes;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds possible status codes specific for REST API conforms JSON.API specification.
 */
class CompleteStatusCodes extends AbstractAddStatusCodes
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var ConfigContext $context */

        $this->addStatusCodes(
            $context->getResult(),
            $context->getClassName(),
            $context->getTargetAction()
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     * @param string                 $targetAction
     */
    private function addStatusCodes(EntityDefinitionConfig $definition, $entityClass, $targetAction)
    {
        switch ($targetAction) {
            case ApiActions::CREATE:
                $this->addStatusCodesForCreate($definition, $entityClass);
                break;
            case ApiActions::UPDATE:
                $this->addStatusCodesForUpdate($definition->getStatusCodes());
                break;
        }
    }

    /**
     * Adds status codes for "create" action.
     *
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     */
    private function addStatusCodesForCreate(EntityDefinitionConfig $definition, $entityClass)
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
     *
     * @param StatusCodesConfig $statusCodes
     */
    private function addStatusCodesForUpdate(StatusCodesConfig $statusCodes)
    {
        $this->addStatusCode(
            $statusCodes,
            Response::HTTP_CONFLICT,
            'Returned when the specified entity type and identifier do not match the server\'s endpoint'
        );
    }

    /**
     * @param EntityDefinitionConfig $definition
     * @param string                 $entityClass
     *
     * @return bool
     */
    private function hasIdGenerator(EntityDefinitionConfig $definition, $entityClass)
    {
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            return false;
        }

        $idFieldNames = $definition->getIdentifierFieldNames();
        if (count($idFieldNames) !== 1) {
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

        return count($idFieldNames) === 1 && reset($idFieldNames) === $idFieldName;
    }
}
