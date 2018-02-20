<?php

namespace Oro\Bundle\EntityBundle\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class EntityReferenceToStringTransformer implements DataTransformerInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($entity)
    {
        if (null === $entity) {
            return '';
        }

        if (!is_object($entity)) {
            throw new UnexpectedTypeException($entity, 'object');
        }

        try {
            $entityClass = ClassUtils::getClass($entity);
            $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        } catch (NotManageableEntityException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return json_encode(
            [
                'entityClass' => $entityClass,
                'entityId'    => $entityId,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (empty($value)) {
            return;
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        $data = json_decode($value, true);

        if (!is_array($data)) {
            throw new TransformationFailedException('Expected an array after decoding a string.');
        }
        if (empty($data)) {
            return null;
        }

        if (empty($data['entityClass'])) {
            throw new TransformationFailedException(
                'Expected an array with "entityClass" element after decoding a string.'
            );
        }
        if (empty($data['entityId'])) {
            throw new TransformationFailedException(
                'Expected an array with "entityId" element after decoding a string.'
            );
        }

        try {
            $entity = $this->doctrineHelper->getEntityReference($data['entityClass'], $data['entityId']);
        } catch (NotManageableEntityException $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return $entity;
    }
}
