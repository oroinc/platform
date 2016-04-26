<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\Rest;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;

/**
 * Converts JSON API data to plain array.
 */
class NormalizeRequestData implements ProcessorInterface
{
    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

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

        $context->setRequestData(
            $this->normalizeData($context->getRequestData(), $context->getMetadata())
        );
    }

    /**
     * @param array          $data
     * @param EntityMetadata $metadata
     *
     * @return array
     */
    protected function normalizeData(array $data, EntityMetadata $metadata)
    {
        $fieldNames = array_keys($data);
        foreach ($fieldNames as $fieldName) {
            $associationMetadata = $metadata->getAssociation($fieldName);
            if (null !== $associationMetadata) {
                $targetEntityClass = $associationMetadata->getTargetClassName();
                if ($associationMetadata->isCollection()) {
                    foreach ($data[$fieldName] as &$value) {
                        $value = $this->normalizeRelationId($targetEntityClass, $value);
                    }
                } elseif (null === $data[$fieldName]) {
                    $data[$fieldName] = [];
                } else {
                    $data[$fieldName] = $this->normalizeRelationId($targetEntityClass, $data[$fieldName]);
                }
            }
        }

        return $data;
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return array ['class' => entity class, 'id' => entity id]
     */
    protected function normalizeRelationId($entityClass, $entityId)
    {
        return [
            'class' => $entityClass,
            'id'    => $this->entityIdTransformer->reverseTransform($entityClass, $entityId)
        ];
    }
}
