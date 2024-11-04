<?php

namespace Oro\Bundle\ApiBundle\Processor\Update\JsonApi;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetOperationFlags;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Util\AclProtectedEntityLoader;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads an existing entity or creates an instance of a new entity
 * when the upsert operation was requested for the "update" action.
 */
class LoadUpsertEntity implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private AclProtectedEntityLoader $entityLoader;
    private EntityInstantiator $entityInstantiator;
    private EntityIdHelper $entityIdHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AclProtectedEntityLoader $entityLoader,
        EntityInstantiator $entityInstantiator,
        EntityIdHelper $entityIdHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityLoader = $entityLoader;
        $this->entityInstantiator = $entityInstantiator;
        $this->entityIdHelper = $entityIdHelper;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateContext $context */

        if ($context->hasResult()) {
            // the entity is already loaded
            return;
        }

        $upsertFlag = $context->get(SetOperationFlags::UPSERT_FLAG);
        if (!$upsertFlag) {
            // the upsert operation was not requested
            return;
        }

        $resolvedEntityClass = $this->doctrineHelper->resolveManageableEntityClass($context->getClassName());
        if (!$resolvedEntityClass) {
            // only manageable entities are supported
            return;
        }

        $metadata = $context->getMetadata();
        if (null === $metadata) {
            $this->addUpsertFlagValidationError($context, 'The upsert operation is not supported.');
        } elseif ($metadata->hasIdentifierGenerator()) {
            $this->addUpsertFlagValidationError(
                $context,
                'The upsert operation is not supported for resources with auto-generated identifier value.'
            );
        } else {
            /** @var EntityDefinitionConfig $config */
            $config = $context->getConfig();
            $upsertConfig = $config->getUpsertConfig();
            if (!$upsertConfig->isEnabled()) {
                $this->addUpsertFlagValidationError($context, 'The upsert operation is not allowed.');
            } elseif (\is_array($upsertFlag)) {
                $this->addUpsertFlagValidationError(
                    $context,
                    'Only the entity identifier can be used by the upsert operation to find an entity.'
                );
            } elseif (!$upsertConfig->isAllowedById()) {
                $this->addUpsertFlagValidationError(
                    $context,
                    'The upsert operation cannot use the entity identifier to find an entity.'
                );
            } else {
                $entity = $this->entityLoader->findEntity(
                    $resolvedEntityClass,
                    $context->getId(),
                    $config,
                    $metadata,
                    $context->getRequestType()
                );
                if (null === $entity) {
                    $entity = $this->entityInstantiator->instantiate($resolvedEntityClass);
                    $this->entityIdHelper->setEntityIdentifier($entity, $context->getId(), $metadata);
                    $context->setExisting(false);
                }
                $context->setResult($entity);
            }
        }
    }

    private function addUpsertFlagValidationError(UpdateContext $context, string $detail): void
    {
        $context->addError(
            Error::createValidationError(Constraint::VALUE, $detail)
                ->setSource(ErrorSource::createByPointer($this->buildUpsertFlagPointer()))
        );
    }

    private function buildUpsertFlagPointer(): string
    {
        return '/' . JsonApiDoc::META . '/' . JsonApiDoc::META_UPSERT;
    }
}
