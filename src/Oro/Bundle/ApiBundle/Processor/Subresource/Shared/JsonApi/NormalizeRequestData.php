<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prepares JSON:API request data for a relationship to be processed by Symfony Forms.
 */
class NormalizeRequestData implements ProcessorInterface
{
    private ValueNormalizer $valueNormalizer;
    private EntityIdTransformerRegistry $entityIdTransformerRegistry;
    private ?ChangeRelationshipContext $context = null;

    public function __construct(
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerRegistry $entityIdTransformerRegistry
    ) {
        $this->valueNormalizer = $valueNormalizer;
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
        $associationName = $this->context->getAssociationName();
        $targetMetadata = $this->getAssociationMetadata($associationName)->getTargetMetadata();
        $path = '';
        $pointer = $this->buildPointer('', JsonApiDoc::DATA);
        if ($this->context->isCollection()) {
            $associationData = [];
            foreach ($data[JsonApiDoc::DATA] as $key => $value) {
                $associationData[] = $this->normalizeRelationId(
                    $this->buildPath($path, $key),
                    $this->buildPointer($pointer, $key),
                    $value[JsonApiDoc::TYPE],
                    $value[JsonApiDoc::ID],
                    $targetMetadata
                );
            }
        } elseif (null !== $data[JsonApiDoc::DATA]) {
            $associationData = $this->normalizeRelationId(
                $path,
                $pointer,
                $data[JsonApiDoc::DATA][JsonApiDoc::TYPE],
                $data[JsonApiDoc::DATA][JsonApiDoc::ID],
                $targetMetadata
            );
        } else {
            $associationData = null;
        }

        $result = [$associationName => $associationData];
        if (!empty($data[JsonApiDoc::META])) {
            $parentMetadata = $this->context->getParentMetadata();
            if (null !== $parentMetadata) {
                foreach ($data[JsonApiDoc::META] as $name => $value) {
                    if ($parentMetadata->hasMetaProperty($name)) {
                        $result[$name] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param string         $path
     * @param string         $pointer
     * @param string         $entityType
     * @param mixed          $entityId
     * @param EntityMetadata $metadata
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    private function normalizeRelationId(
        string $path,
        string $pointer,
        string $entityType,
        mixed $entityId,
        EntityMetadata $metadata
    ): array {
        $entityClass = $this->normalizeEntityClass(
            $this->buildPointer($pointer, JsonApiDoc::TYPE),
            $entityType
        );
        if ($entityClass) {
            $entityId = $this->normalizeEntityId(
                $this->buildPath($path, JsonApiDoc::ID),
                $this->buildPointer($pointer, JsonApiDoc::ID),
                $entityId,
                $metadata
            );
        }

        return [
            'class' => $entityClass ?: $entityType,
            'id'    => $entityId
        ];
    }

    private function normalizeEntityId(string $path, string $pointer, mixed $entityId, EntityMetadata $metadata): mixed
    {
        try {
            $normalizedId = $this->getEntityIdTransformer($this->context->getRequestType())
                ->reverseTransform($entityId, $metadata);
            if (null === $normalizedId) {
                $this->context->addNotResolvedIdentifier(
                    'requestData' . ConfigUtil::PATH_DELIMITER . $path,
                    new NotResolvedIdentifier($entityId, $metadata->getClassName())
                );
            }

            return $normalizedId;
        } catch (\Exception $e) {
            $this->context->addError(
                Error::createValidationError(Constraint::ENTITY_ID)
                    ->setInnerException($e)
                    ->setSource(ErrorSource::createByPointer($pointer))
            );
        }

        return $entityId;
    }

    private function normalizeEntityClass(string $pointer, string $entityType): ?string
    {
        try {
            return ValueNormalizerUtil::convertToEntityClass(
                $this->valueNormalizer,
                $entityType,
                $this->context->getRequestType()
            );
        } catch (\Exception $e) {
            $this->context->addError(
                Error::createValidationError(Constraint::ENTITY_TYPE)
                    ->setInnerException($e)
                    ->setSource(ErrorSource::createByPointer($pointer))
            );
        }

        return null;
    }

    private function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }

    private function buildPath(string $parentPath, string $property): string
    {
        return '' !== $parentPath
            ? $parentPath . ConfigUtil::PATH_DELIMITER . $property
            : $property;
    }

    private function buildPointer(string $parentPointer, string $property): string
    {
        return $parentPointer . '/' . $property;
    }

    private function getAssociationMetadata(string $associationName): AssociationMetadata
    {
        $associationMetadata = $this->context->getParentMetadata()->getAssociation($associationName);
        if (null === $associationMetadata) {
            throw new RuntimeException(sprintf(
                'The metadata for association "%s::%s" does not exist.',
                $this->context->getParentClassName(),
                $associationName
            ));
        }

        return $associationMetadata;
    }
}
