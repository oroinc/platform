<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared\Rest;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;

/**
 * Prepares REST API request data to be processed by Symfony Forms.
 */
class NormalizeRequestData implements ProcessorInterface
{
    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /** @var ChangeRelationshipContext */
    protected $context;

    /**
     * @param EntityIdTransformerInterface $entityIdTransformer
     */
    public function __construct(EntityIdTransformerInterface $entityIdTransformer)
    {
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
            if ($this->context->isCollection()) {
                $associationData = [];
                foreach ($associationValue as $key => $value) {
                    $associationData[] = $this->normalizeRelationId(
                        $associationName . '.' . $key,
                        $targetEntityClass,
                        $value
                    );
                }
            } elseif (null !== $associationValue) {
                $associationData = $this->normalizeRelationId(
                    $associationName,
                    $targetEntityClass,
                    $associationValue
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
     * @param string $propertyPath
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    protected function normalizeRelationId($propertyPath, $entityClass, $entityId)
    {
        return [
            'class' => $entityClass,
            'id'    => $this->normalizeEntityId($propertyPath, $entityClass, $entityId)
        ];
    }

    /**
     * @param string $propertyPath
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return mixed
     */
    protected function normalizeEntityId($propertyPath, $entityClass, $entityId)
    {
        try {
            return $this->entityIdTransformer->reverseTransform($entityClass, $entityId);
        } catch (\Exception $e) {
            $error = Error::createValidationError(Constraint::ENTITY_ID)
                ->setInnerException($e)
                ->setSource(ErrorSource::createByPropertyPath($propertyPath));
            $this->context->addError($error);
        }

        return $entityId;
    }
}
