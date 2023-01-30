<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prepares REST API request data for a relationship to be processed by Symfony Forms.
 */
class NormalizeRequestData implements ProcessorInterface
{
    private EntityIdTransformerRegistry $entityIdTransformerRegistry;
    private ?ChangeRelationshipContext $context = null;

    public function __construct(EntityIdTransformerRegistry $entityIdTransformerRegistry)
    {
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

        $this->context = $context;
        try {
            $context->setRequestData($this->normalizeData($context->getRequestData()));
        } finally {
            $this->context = null;
        }
    }

    private function normalizeData(array $data): array
    {
        $associationValue = reset($data);
        $rootKey = (string)key($data);

        $associationName = $this->context->getAssociationName();
        $associationMetadata = $this->context->getParentMetadata()->getAssociation($associationName);
        if (null !== $associationMetadata) {
            $targetEntityClass = $associationMetadata->getTargetClassName();
            $targetMetadata = $associationMetadata->getTargetMetadata();
            if ($this->context->isCollection()) {
                $associationData = [];
                foreach ($associationValue as $key => $value) {
                    $associationData[] = $this->normalizeRelationId(
                        $rootKey . ConfigUtil::PATH_DELIMITER . (string)$key,
                        $targetEntityClass,
                        $value,
                        $targetMetadata
                    );
                }
            } elseif (null !== $associationValue) {
                $associationData = $this->normalizeRelationId(
                    $rootKey,
                    $targetEntityClass,
                    $associationValue,
                    $targetMetadata
                );
            } else {
                $associationData = null;
            }
        } else {
            $associationData = $associationValue;
        }

        return [$associationName => $associationData];
    }

    /**
     * @param string         $propertyPath
     * @param string         $entityClass
     * @param mixed          $entityId
     * @param EntityMetadata $metadata
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    private function normalizeRelationId(
        string $propertyPath,
        string $entityClass,
        mixed $entityId,
        EntityMetadata $metadata
    ): array {
        return [
            'class' => $entityClass,
            'id'    => $this->normalizeEntityId($propertyPath, $entityId, $metadata)
        ];
    }

    private function normalizeEntityId(string $propertyPath, mixed $entityId, EntityMetadata $metadata): mixed
    {
        try {
            $normalizedId = $this->getEntityIdTransformer($this->context->getRequestType())
                ->reverseTransform($entityId, $metadata);
            if (null === $normalizedId) {
                $this->context->addNotResolvedIdentifier(
                    'requestData' . ConfigUtil::PATH_DELIMITER . $propertyPath,
                    new NotResolvedIdentifier($entityId, $metadata->getClassName())
                );
            }

            return $normalizedId;
        } catch (\Exception $e) {
            $this->context->addError(
                Error::createValidationError(Constraint::ENTITY_ID)
                    ->setInnerException($e)
                    ->setSource(ErrorSource::createByPropertyPath($propertyPath))
            );
        }

        return $entityId;
    }

    private function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }
}
