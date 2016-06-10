<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
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

    /** @var ChangeRelationshipContext */
    protected $context;

    /**
     * @param ValueNormalizer              $valueNormalizer
     * @param EntityIdTransformerInterface $entityIdTransformer
     */
    public function __construct(
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerInterface $entityIdTransformer
    ) {
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformer = $entityIdTransformer;
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
        $associationData = [];
        $associationName = $this->context->getAssociationName();
        $dataPointer = $this->buildPointer('', JsonApiDoc::DATA);
        if ($this->context->isCollection()) {
            foreach ($data[JsonApiDoc::DATA] as $key => $value) {
                $pointer = $this->buildPointer($dataPointer, $key);
                $targetEntityClass = $this->normalizeEntityClass(
                    $this->buildPointer($pointer, JsonApiDoc::TYPE),
                    $value[JsonApiDoc::TYPE]
                );
                $associationData[] = $this->normalizeRelationId(
                    $this->buildPointer($pointer, JsonApiDoc::ID),
                    $targetEntityClass,
                    $value[JsonApiDoc::ID]
                );
            }
        } elseif (null !== $data[JsonApiDoc::DATA]) {
            $targetEntityClass = $this->normalizeEntityClass(
                $this->buildPointer($dataPointer, JsonApiDoc::TYPE),
                $data[JsonApiDoc::DATA][JsonApiDoc::TYPE]
            );
            $associationData = $this->normalizeRelationId(
                $dataPointer,
                $targetEntityClass,
                $data[JsonApiDoc::DATA][JsonApiDoc::ID]
            );
        }

        return [$associationName => $associationData];
    }

    /**
     * @param string $pointer
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    protected function normalizeRelationId($pointer, $entityClass, $entityId)
    {
        return [
            'class' => $entityClass,
            'id'    => $this->normalizeEntityId($pointer, $entityClass, $entityId)
        ];
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
     * @param string $pointer
     * @param string $entityType
     *
     * @return string
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

        return $entityType;
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
