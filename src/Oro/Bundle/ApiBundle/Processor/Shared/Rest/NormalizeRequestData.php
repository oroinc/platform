<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prepares REST API request data to be processed by Symfony Forms.
 */
class NormalizeRequestData implements ProcessorInterface
{
    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /** @var FormContext */
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
        /** @var FormContext $context */

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
        $metadata = $this->context->getMetadata();
        $fieldNames = array_keys($data);
        foreach ($fieldNames as $fieldName) {
            $associationMetadata = $metadata->getAssociation($fieldName);
            if (null !== $associationMetadata) {
                $targetEntityClass = $associationMetadata->getTargetClassName();
                $targetMetadata = $associationMetadata->getTargetMetadata();
                if ($associationMetadata->isCollection()) {
                    foreach ($data[$fieldName] as $key => &$value) {
                        $value = $this->normalizeRelationId(
                            $fieldName . '.' . $key,
                            $targetEntityClass,
                            $value,
                            $targetMetadata
                        );
                    }
                } elseif (null === $data[$fieldName]) {
                    $data[$fieldName] = [];
                } else {
                    $data[$fieldName] = $this->normalizeRelationId(
                        $fieldName,
                        $targetEntityClass,
                        $data[$fieldName],
                        $targetMetadata
                    );
                }
            }
        }

        return $data;
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
            return $this->entityIdTransformer->reverseTransform($entityId, $entityMetadata);
        } catch (\Exception $e) {
            $error = Error::createValidationError(Constraint::ENTITY_ID)
                ->setInnerException($e)
                ->setSource(ErrorSource::createByPropertyPath($propertyPath));
            $this->context->addError($error);
        }

        return $entityId;
    }
}
