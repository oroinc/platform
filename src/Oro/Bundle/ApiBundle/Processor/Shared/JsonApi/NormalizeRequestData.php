<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Prepares JSON.API request data to be processed by Symfony Forms.
 */
class NormalizeRequestData implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /** @var FormContext */
    protected $context;

    /**
     * @param ValueNormalizer              $valueNormalizer
     * @param EntityIdTransformerInterface $entityIdTransformer
     */
    public function __construct(ValueNormalizer $valueNormalizer, EntityIdTransformerInterface $entityIdTransformer)
    {
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformer = $entityIdTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $requestData = $context->getRequestData();
        if (!array_key_exists(JsonApiDoc::DATA, $requestData)) {
            // the request data are already normalized
            return;
        }

        $this->context = $context;
        try {
            $context->setRequestData($this->normalizeData($requestData[JsonApiDoc::DATA], $context->getMetadata()));
            $this->context = null;
        } catch (\Exception $e) {
            $this->context = null;
            throw $e;
        }
    }

    /**
     * @param array               $data
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    protected function normalizeData(array $data, EntityMetadata $metadata = null)
    {
        $relations = array_key_exists(JsonApiDoc::RELATIONSHIPS, $data)
            ? $this->normalizeRelationships($data[JsonApiDoc::RELATIONSHIPS], $metadata)
            : [];

        return !empty($data[JsonApiDoc::ATTRIBUTES])
            ? array_merge($data[JsonApiDoc::ATTRIBUTES], $relations)
            : $relations;
    }

    /**
     * @param array               $relationships
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    protected function normalizeRelationships(array $relationships, EntityMetadata $metadata = null)
    {
        $relations = [];
        $relationshipsPointer = $this->buildPointer(
            $this->buildPointer('', JsonApiDoc::DATA),
            JsonApiDoc::RELATIONSHIPS
        );
        foreach ($relationships as $name => $value) {
            $relationshipsDataItemPointer = $this->buildPointer(
                $this->buildPointer($relationshipsPointer, $name),
                JsonApiDoc::DATA
            );
            $relationData = $value[JsonApiDoc::DATA];

            // Relation data can be null in case to-one and an empty array in case to-many relation.
            // In this case we should process this relation data as empty relation
            if (null === $relationData || empty($relationData)) {
                $relations[$name] = [];
                continue;
            }

            $associationMetadata = null !== $metadata
                ? $metadata->getAssociation($name)
                : null;
            if (ArrayUtil::isAssoc($relationData)) {
                $relations[$name] = $this->normalizeRelationshipItem(
                    $relationshipsDataItemPointer,
                    $relationData,
                    $associationMetadata
                );
            } else {
                foreach ($relationData as $key => $collectionItem) {
                    $relations[$name][] = $this->normalizeRelationshipItem(
                        $this->buildPointer($relationshipsDataItemPointer, $key),
                        $collectionItem,
                        $associationMetadata
                    );
                }
            }
        }

        return $relations;
    }

    /**
     * @param string                   $pointer
     * @param array                    $data
     * @param AssociationMetadata|null $associationMetadata
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    protected function normalizeRelationshipItem(
        $pointer,
        array $data,
        AssociationMetadata $associationMetadata = null
    ) {
        $entityClass = $this->normalizeEntityClass(
            $this->buildPointer($pointer, JsonApiDoc::TYPE),
            $data[JsonApiDoc::TYPE]
        );
        $entityId = $data[JsonApiDoc::ID];
        if (false !== strpos($entityClass, '\\')) {
            if (null !== $associationMetadata
                && !in_array($entityClass, $associationMetadata->getAcceptableTargetClassNames(), true)
            ) {
                $this->addValidationError(Constraint::ENTITY_TYPE, $this->buildPointer($pointer, JsonApiDoc::TYPE))
                    ->setDetail('Not acceptable entity type.');
            } else {
                $entityId = $this->normalizeEntityId(
                    $this->buildPointer($pointer, JsonApiDoc::ID),
                    $entityClass,
                    $entityId
                );
            }
        }

        return [
            'class' => $entityClass,
            'id'    => $entityId
        ];
    }

    /**
     * @param string $pointer
     * @param string $entityType
     *
     * @return string
     */
    protected function normalizeEntityClass($pointer, $entityType)
    {
        $entityClass = ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->context->getRequestType(),
            false
        );
        if (null === $entityClass) {
            $this->addValidationError(Constraint::ENTITY_TYPE, $pointer);
            $entityClass = $entityType;
        }

        return $entityClass;
    }

    /**
     * @param string $pointer
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return mixed
     */
    protected function normalizeEntityId($pointer, $entityClass, $entityId)
    {
        try {
            return $this->entityIdTransformer->reverseTransform($entityClass, $entityId);
        } catch (\Exception $e) {
            $this->addValidationError(Constraint::ENTITY_ID, $pointer)
                ->setInnerException($e);
        }

        return $entityId;
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
     * @param string      $title
     * @param string|null $pointer
     *
     * @return Error
     */
    protected function addValidationError($title, $pointer = null)
    {
        $error = Error::createValidationError($title);
        if (null !== $pointer) {
            $error->setSource(ErrorSource::createByPointer($pointer));
        }
        $this->context->addError($error);

        return $error;
    }
}
