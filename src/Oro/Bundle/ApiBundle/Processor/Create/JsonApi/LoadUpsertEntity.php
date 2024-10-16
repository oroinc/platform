<?php

namespace Oro\Bundle\ApiBundle\Processor\Create\JsonApi;

use Doctrine\ORM\NonUniqueResultException;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Shared\JsonApi\SetOperationFlags;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Util\AclProtectedEntityLoader;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\ApiBundle\Util\UpsertCriteriaBuilder;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads an existing entity when the upsert operation was requested for the "create" action.
 */
class LoadUpsertEntity implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private AclProtectedEntityLoader $entityLoader;
    private UpsertCriteriaBuilder $upsertCriteriaBuilder;
    private EntityIdHelper $entityIdHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        AclProtectedEntityLoader $entityLoader,
        UpsertCriteriaBuilder $upsertCriteriaBuilder,
        EntityIdHelper $entityIdHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityLoader = $entityLoader;
        $this->upsertCriteriaBuilder = $upsertCriteriaBuilder;
        $this->entityIdHelper = $entityIdHelper;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CreateContext $context */

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
        } else {
            /** @var EntityDefinitionConfig $config */
            $config = $context->getConfig();
            $upsertConfig = $config->getUpsertConfig();
            if (!$upsertConfig->isEnabled()) {
                $this->addUpsertFlagValidationError($context, 'The upsert operation is not allowed.');
            } else {
                $entity = null;
                if (\is_array($upsertFlag)) {
                    if ($upsertConfig->isAllowedFields($upsertFlag)) {
                        $criteria = $this->upsertCriteriaBuilder->getUpsertFindEntityCriteria(
                            $metadata,
                            $upsertFlag,
                            $context->getRequestData(),
                            $this->buildUpsertFlagPointer(),
                            $context
                        );
                        if (null !== $criteria) {
                            try {
                                $entity = $this->entityLoader->findEntityBy(
                                    $resolvedEntityClass,
                                    $criteria,
                                    $config,
                                    $metadata,
                                    $context->getRequestType()
                                );
                            } catch (NonUniqueResultException) {
                                $context->addError(
                                    Error::createConflictValidationError(
                                        'The upsert operation founds more than one entity.'
                                    )
                                );
                            }
                            if (null !== $entity) {
                                $context->setId($this->entityIdHelper->getEntityIdentifier($entity, $metadata));
                            }
                        }
                    } else {
                        $this->addUpsertFlagValidationError(
                            $context,
                            $this->getUpsertByFieldsIsNotAllowedErrorMessage($upsertFlag)
                        );
                    }
                } elseif ($upsertConfig->isAllowedById()) {
                    $entity = $this->entityLoader->findEntity(
                        $resolvedEntityClass,
                        $context->getId(),
                        $config,
                        $metadata,
                        $context->getRequestType()
                    );
                } else {
                    $this->addUpsertFlagValidationError(
                        $context,
                        'The upsert operation cannot use the entity identifier to find an entity.'
                    );
                }
                if (null !== $entity) {
                    $context->setExisting(true);
                    $context->setResult($entity);
                }
            }
        }
    }

    private function addUpsertFlagValidationError(CreateContext $context, string $detail): void
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

    private function getUpsertByFieldsIsNotAllowedErrorMessage(array $fieldNames): string
    {
        return
            'The upsert operation cannot use '
            . (\count($fieldNames) > 1 ? 'these fields' : 'this field')
            . ' to find an entity.';
    }
}
