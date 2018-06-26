<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prepares JSON.API request data for a relationship to be processed by Symfony Forms.
 */
class NormalizeRequestData implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformerRegistry */
    protected $entityIdTransformerRegistry;

    /** @var ChangeRelationshipContext */
    protected $context;

    /**
     * @param ValueNormalizer             $valueNormalizer
     * @param EntityIdTransformerRegistry $entityIdTransformerRegistry
     */
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
    public function process(ContextInterface $context)
    {
        /** @var ChangeRelationshipContext $context */

        $this->context = $context;
        try {
            $context->setRequestData($this->normalizeData($context->getRequestData()));
        } finally {
            $this->context = null;
        }
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function normalizeData(array $data)
    {
        $associationName = $this->context->getAssociationName();
        $targetMetadata = $this->getAssociationMetadata($associationName)->getTargetMetadata();
        $dataPointer = $this->buildPointer('', JsonApiDoc::DATA);
        if ($this->context->isCollection()) {
            $associationData = [];
            foreach ($data[JsonApiDoc::DATA] as $key => $value) {
                $associationData[] = $this->normalizeRelationId(
                    $this->buildPointer($dataPointer, $key),
                    $value[JsonApiDoc::TYPE],
                    $value[JsonApiDoc::ID],
                    $targetMetadata
                );
            }
        } elseif (null !== $data[JsonApiDoc::DATA]) {
            $associationData = $this->normalizeRelationId(
                $dataPointer,
                $data[JsonApiDoc::DATA][JsonApiDoc::TYPE],
                $data[JsonApiDoc::DATA][JsonApiDoc::ID],
                $targetMetadata
            );
        } else {
            $associationData = null;
        }

        return [$associationName => $associationData];
    }

    /**
     * @param string         $pointer
     * @param string         $entityType
     * @param mixed          $entityId
     * @param EntityMetadata $entityMetadata
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    protected function normalizeRelationId($pointer, $entityType, $entityId, EntityMetadata $entityMetadata)
    {
        $entityClass = $this->normalizeEntityClass(
            $this->buildPointer($pointer, JsonApiDoc::TYPE),
            $entityType
        );
        if ($entityClass) {
            $entityId = $this->normalizeEntityId(
                $this->buildPointer($pointer, JsonApiDoc::ID),
                $entityId,
                $entityMetadata
            );
        }

        return [
            'class' => $entityClass ?: $entityType,
            'id'    => $entityId
        ];
    }

    /**
     * @param string         $pointer
     * @param mixed          $entityId
     * @param EntityMetadata $entityMetadata
     *
     * @return mixed
     */
    protected function normalizeEntityId($pointer, $entityId, EntityMetadata $entityMetadata)
    {
        try {
            return $this->getEntityIdTransformer($this->context->getRequestType())
                ->reverseTransform($entityId, $entityMetadata);
        } catch (\Exception $e) {
            $error = Error::createValidationError(Constraint::ENTITY_ID)
                ->setInnerException($e)
                ->setSource(ErrorSource::createByPointer($pointer));
            $this->context->addError($error);
        }

        return $entityId;
    }

    /**
     * @param string $pointer
     * @param string $entityType
     *
     * @return string|null
     */
    protected function normalizeEntityClass($pointer, $entityType)
    {
        try {
            return ValueNormalizerUtil::convertToEntityClass(
                $this->valueNormalizer,
                $entityType,
                $this->context->getRequestType()
            );
        } catch (\Exception $e) {
            $error = Error::createValidationError(Constraint::ENTITY_TYPE)
                ->setInnerException($e)
                ->setSource(ErrorSource::createByPointer($pointer));
            $this->context->addError($error);
        }

        return null;
    }

    /**
     * @param RequestType $requestType
     *
     * @return EntityIdTransformerInterface
     */
    protected function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }

    /**
     * @param string $parentPath
     * @param string $property
     *
     * @return string
     */
    protected function buildPointer($parentPath, $property)
    {
        return $parentPath . '/' . $property;
    }

    /**
     * @param string $associationName
     *
     * @return AssociationMetadata
     */
    private function getAssociationMetadata($associationName)
    {
        $associationMetadata = $this->context->getParentMetadata()->getAssociation($associationName);
        if (null === $associationMetadata) {
            throw new RuntimeException(\sprintf(
                'The metadata for association "%s::%s" does not exist.',
                $this->context->getParentClassName(),
                $associationName
            ));
        }

        return $associationMetadata;
    }
}
