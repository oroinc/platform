<?php

namespace Oro\Bundle\AddressBundle\Api\Form\DataTransformer;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms a string represents the identifier of address type
 * to Oro\Bundle\AddressBundle\Entity\AddressType object.
 */
class AddressTypeToIdTransformer implements DataTransformerInterface
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
    {
        if (null === $value) {
            return null;
        }
        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }
        if ('' === $value) {
            throw new TransformationFailedException('Expected not empty a string.');
        }

        return $this->getEntity($value);
    }

    /**
     * @throws TransformationFailedException if the address type does not exist
     */
    private function getEntity(string $id): AddressType
    {
        $entity = $this->doctrineHelper->getEntityManagerForClass(AddressType::class)
            ->find(AddressType::class, $id);
        if (null === $entity) {
            throw new TransformationFailedException(sprintf(
                'The address type with "%s" identifier does not exist.',
                $id
            ));
        }

        return $entity;
    }
}
