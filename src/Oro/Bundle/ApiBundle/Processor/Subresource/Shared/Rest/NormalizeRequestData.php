<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prepares REST API request data for a relationship to be processed by Symfony Forms.
 */
class NormalizeRequestData implements ProcessorInterface
{
    /** @var EntityIdTransformerRegistry */
    protected $entityIdTransformerRegistry;

    /** @var ChangeRelationshipContext */
    protected $context;

    /**
     * @param EntityIdTransformerRegistry $entityIdTransformerRegistry
     */
    public function __construct(EntityIdTransformerRegistry $entityIdTransformerRegistry)
    {
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
        $associationValue = reset($data);

        $associationName = $this->context->getAssociationName();
        $associationMetadata = $this->context->getParentMetadata()->getAssociation($associationName);
        if (null !== $associationMetadata) {
            $targetEntityClass = $associationMetadata->getTargetClassName();
            $targetMetadata = $associationMetadata->getTargetMetadata();
            if ($this->context->isCollection()) {
                $associationData = [];
                foreach ($associationValue as $key => $value) {
                    $associationData[] = $this->normalizeRelationId(
                        $associationName . '.' . $key,
                        $targetEntityClass,
                        $value,
                        $targetMetadata
                    );
                }
            } elseif (null !== $associationValue) {
                $associationData = $this->normalizeRelationId(
                    $associationName,
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
     * @param EntityMetadata $entityMetadata
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    protected function normalizeRelationId($propertyPath, $entityClass, $entityId, EntityMetadata $entityMetadata)
    {
        return [
            'class' => $entityClass,
            'id'    => $this->normalizeEntityId($propertyPath, $entityId, $entityMetadata)
        ];
    }

    /**
     * @param string         $propertyPath
     * @param mixed          $entityId
     * @param EntityMetadata $entityMetadata
     *
     * @return mixed
     */
    protected function normalizeEntityId($propertyPath, $entityId, EntityMetadata $entityMetadata)
    {
        try {
            return $this->getEntityIdTransformer($this->context->getRequestType())
                ->reverseTransform($entityId, $entityMetadata);
        } catch (\Exception $e) {
            $error = Error::createValidationError(Constraint::ENTITY_ID)
                ->setInnerException($e)
                ->setSource(ErrorSource::createByPropertyPath($propertyPath));
            $this->context->addError($error);
        }

        return $entityId;
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
}
