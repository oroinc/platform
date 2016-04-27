<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
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
            $context->setRequestData($this->normalizeData($requestData[JsonApiDoc::DATA]));
            $this->context = null;
        } catch (\Exception $e) {
            $this->context = null;
            throw $e;
        }
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function normalizeData(array $data)
    {
        $relations = [];
        if (array_key_exists(JsonApiDoc::RELATIONSHIPS, $data)) {
            $relationshipsPointer = $this->buildPointer(
                $this->buildPointer('', JsonApiDoc::DATA),
                JsonApiDoc::RELATIONSHIPS
            );
            foreach ($data[JsonApiDoc::RELATIONSHIPS] as $name => $value) {
                $relationshipsDataItemPointer = $this->buildPointer(
                    $this->buildPointer($relationshipsPointer, $name),
                    JsonApiDoc::DATA
                );
                $relationData = $value[JsonApiDoc::DATA];

                // Relation data can be null in case -to-one and an empty array in case -to-many relation.
                // In this case we should process this relation data as empty relation
                if (null === $relationData || empty($relationData)) {
                    $relations[$name] = [];
                    continue;
                }

                if (array_keys($relationData) !== range(0, count($relationData) - 1)) {
                    $relations[$name] = $this->normalizeItemData(
                        $relationshipsDataItemPointer,
                        $relationData
                    );
                } else {
                    foreach ($relationData as $key => $collectionItem) {
                        $relations[$name][] = $this->normalizeItemData(
                            $this->buildPointer($relationshipsDataItemPointer, $key),
                            $collectionItem
                        );
                    }
                }
            }
        }

        return !empty($data[JsonApiDoc::ATTRIBUTES])
            ? array_merge($data[JsonApiDoc::ATTRIBUTES], $relations)
            : $relations;
    }

    /**
     * @param string $pointer
     * @param array  $data ['type' => entity type, 'id' => entity id]
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    protected function normalizeItemData($pointer, array $data)
    {
        $entityClass = $this->normalizeEntityClass(
            $this->buildPointer($pointer, JsonApiDoc::TYPE),
            $data[JsonApiDoc::TYPE]
        );
        $entityId = $this->normalizeEntityId(
            $this->buildPointer($pointer, JsonApiDoc::ID),
            $entityClass,
            $data[JsonApiDoc::ID]
        );

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
            $error = Error::createValidationError(Constraint::ENTITY_TYPE)
                ->setSource(ErrorSource::createByPointer($pointer));
            $this->context->addError($error);

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
            $error = Error::createValidationError(Constraint::ENTITY_ID)
                ->setInnerException($e)
                ->setSource(ErrorSource::createByPointer($pointer));
            $this->context->addError($error);
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
}
