<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * Loads data from "included" section of the request data to the Context.
 */
class NormalizeIncludedData implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityInstantiator */
    protected $entityInstantiator;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /** @var FormContext */
    protected $context;

    /**
     * @param DoctrineHelper               $doctrineHelper
     * @param EntityInstantiator           $entityInstantiator
     * @param ValueNormalizer              $valueNormalizer
     * @param EntityIdTransformerInterface $entityIdTransformer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityInstantiator $entityInstantiator,
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerInterface $entityIdTransformer
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityInstantiator = $entityInstantiator;
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformer = $entityIdTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        $includedData = $context->getIncludedData();
        if (null === $includedData) {
            $includedData = $this->normalizeIncludedData($context->getRequestData());
            $context->setIncludedData($includedData);
        }

        if (empty($includedData) || null !== $context->getIncludedEntities()) {
            // no included data or they are already converted to included entities collection
            return;
        }

        $this->context = $context;
        try {
            $includedEntities = $this->loadIncludedEntities($includedData);
            if (null !== $includedEntities) {
                $context->setIncludedEntities($includedEntities);
            }
        } finally {
            $this->context = null;
        }
    }

    /**
     * @param array $requestData
     *
     * @return array
     */
    protected function normalizeIncludedData(array $requestData)
    {
        $includedData = !empty($requestData[JsonApiDoc::INCLUDED])
            ? $requestData[JsonApiDoc::INCLUDED]
            : [];

        $result = [];
        foreach ($includedData as $index => $data) {
            $result[$index] = [JsonApiDoc::DATA => $data];
        }

        return $result;
    }

    /**
     * @param array $includedData
     *
     * @return IncludedEntityCollection|null
     */
    protected function loadIncludedEntities(array $includedData)
    {
        $includedEntities = new IncludedEntityCollection();

        $includedPointer = $this->buildPointer('', JsonApiDoc::INCLUDED);
        foreach ($includedData as $index => $data) {
            $pointer = $this->buildPointer($includedPointer, $index);
            $data = $this->getDataProperty($data, $pointer);
            $entityClass = $this->getEntityClass(
                $this->buildPointer($pointer, JsonApiDoc::TYPE),
                $data[JsonApiDoc::TYPE]
            );
            if (null !== $entityClass) {
                $updateFlag = $this->getUpdateFlag($pointer, $data, $entityClass);
                if (null !== $updateFlag) {
                    $entityId = $this->getEntityId(
                        $this->buildPointer($pointer, JsonApiDoc::ID),
                        $entityClass,
                        $data[JsonApiDoc::ID],
                        $updateFlag
                    );
                    if (null !== $entityId) {
                        $entity = $this->getEntity($pointer, $entityClass, $entityId, $updateFlag);
                        if (null !== $entity) {
                            $includedEntities->add(
                                $entity,
                                $entityClass,
                                $entityId,
                                new IncludedEntityData($pointer, $index, $updateFlag)
                            );
                        }
                    }
                }
            }
        }

        return !$this->context->hasErrors() ? $includedEntities : null;
    }

    /**
     * @param mixed  $data
     * @param string $pointer
     *
     * @return array
     */
    protected function getDataProperty($data, $pointer)
    {
        if (!is_array($data)) {
            throw new RuntimeException(
                sprintf('The "%s" element should be an array.', $pointer)
            );
        }
        if (!array_key_exists(JsonApiDoc::DATA, $data)) {
            throw new RuntimeException(
                sprintf('The "%s" element should have "%s" property.', $pointer, JsonApiDoc::DATA)
            );
        }
        $data = $data[JsonApiDoc::DATA];
        if (!is_array($data)) {
            throw new RuntimeException(
                sprintf('The "%s" property of "%s" element should be an array.', JsonApiDoc::DATA, $pointer)
            );
        }

        return $data;
    }

    /**
     * @param string $pointer
     * @param array  $data
     * @param string $entityClass
     *
     * @return bool|null
     */
    protected function getUpdateFlag($pointer, $data, $entityClass)
    {
        if (empty($data[JsonApiDoc::META]) || !array_key_exists('_update', $data[JsonApiDoc::META])) {
            return false;
        }
        $flag = $data[JsonApiDoc::META]['_update'];
        if (true === $flag || false === $flag) {
            if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
                $this->addValidationError(
                    Constraint::VALUE,
                    $this->buildPointer($this->buildPointer($pointer, JsonApiDoc::META), '_update'),
                    'Only manageable entity can be updated.'
                );

                return null;
            }

            return $flag;
        }

        $this->addValidationError(
            Constraint::VALUE,
            $this->buildPointer($this->buildPointer($pointer, JsonApiDoc::META), '_update'),
            'This value should be boolean.'
        );

        return null;
    }

    /**
     * @param string $pointer
     * @param string $entityType
     *
     * @return string|null
     */
    protected function getEntityClass($pointer, $entityType)
    {
        $entityClass = ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->context->getRequestType(),
            false
        );
        if ($entityClass) {
            return $entityClass;
        }

        $this->addValidationError(Constraint::ENTITY_TYPE, $pointer);

        return null;
    }

    /**
     * @param string $pointer
     * @param string $entityClass
     * @param mixed  $entityId
     * @param bool   $updateFlag
     *
     * @return mixed
     */
    protected function getEntityId($pointer, $entityClass, $entityId, $updateFlag)
    {
        if (!$updateFlag) {
            return $entityId;
        }
        try {
            return $this->entityIdTransformer->reverseTransform($entityClass, $entityId);
        } catch (\Exception $e) {
            $this->addValidationError(Constraint::ENTITY_ID, $pointer)
                ->setInnerException($e);
        }

        return null;
    }

    /**
     * @param string $pointer
     * @param string $entityClass
     * @param mixed  $entityId
     * @param bool   $updateFlag
     *
     * @return object|null
     */
    protected function getEntity($pointer, $entityClass, $entityId, $updateFlag)
    {
        return $updateFlag
            ? $this->getExistingEntity($pointer, $entityClass, $entityId)
            : $this->entityInstantiator->instantiate($entityClass);
    }

    /**
     * @param string $pointer
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return object|null
     */
    protected function getExistingEntity($pointer, $entityClass, $entityId)
    {
        $entity = $this->doctrineHelper->getEntityManagerForClass($entityClass)->find($entityClass, $entityId);
        if (null !== $entity) {
            return $entity;
        }

        $this->addValidationError(Constraint::ENTITY, $pointer, 'The entity does not exist.');

        return null;
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
     * @param string|null $detail
     *
     * @return Error
     */
    protected function addValidationError($title, $pointer = null, $detail = null)
    {
        $error = Error::createValidationError($title);
        if (null !== $pointer) {
            $error->setSource(ErrorSource::createByPointer($pointer));
        }
        if (null !== $detail) {
            $error->setDetail($detail);
        }
        $this->context->addError($error);

        return $error;
    }
}
