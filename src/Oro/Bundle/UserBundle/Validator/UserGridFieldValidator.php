<?php

namespace Oro\Bundle\UserBundle\Validator;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\Entity\Manager\Field\CustomGridFieldValidatorInterface;
use Oro\Bundle\EntityBundle\Exception\IncorrectEntityException;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Validates user entity fields to be editable inline in grid.
 */
class UserGridFieldValidator implements CustomGridFieldValidatorInterface
{
    /** @var PropertyAccessorInterface */
    protected $accessor;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        PropertyAccessorInterface $accessor
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->accessor = $accessor;
    }

    /**
     * @param User $entity
     *
     * {@inheritdoc}
     */
    public function hasAccessEditField($entity, $fieldName)
    {
        if (!$entity instanceof User) {
            $className = ClassUtils::getClass($entity);
            throw new IncorrectEntityException(
                sprintf('Entity %s, is not instance of User class', $className)
            );
        }

        $currentUser = $this->tokenAccessor->getUser();

        if ($this->hasField($entity, $fieldName)
            && in_array($fieldName, $this->getCurrentUserFieldBlockList(), true)
            && $currentUser->getId() !== $entity->getId()
        ) {
            return true;
        }

        return $this->hasField($entity, $fieldName)
            && !in_array($fieldName, $this->getCurrentUserFieldBlockList(), true);
    }

    /**
     * @param User $entity
     *
     * {@inheritdoc}
     */
    public function hasField($entity, $fieldName)
    {
        try {
            $this->accessor->isWritable($entity, $fieldName);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    protected function getCurrentUserFieldBlockList()
    {
        return [
            'enabled'
        ];
    }
}
