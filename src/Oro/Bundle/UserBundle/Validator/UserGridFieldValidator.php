<?php

namespace Oro\Bundle\UserBundle\Validator;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;

use Oro\Bundle\EntityBundle\Entity\Manager\Field\CustomGridFieldValidatorInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EntityBundle\Exception\IncorrectEntityException;

class UserGridFieldValidator implements CustomGridFieldValidatorInterface
{
    /** @var PropertyAccessor */
    protected $accessor;

    /** @var  SecurityFacade */
    protected $securityFacade;

    /**
     * UserFieldValidator constructor.
     *
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
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

        $currentUser = $this->securityFacade->getLoggedUser();

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
            $this->getPropertyAccessor()->isWritable($entity, $fieldName);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (null === $this->accessor) {
            $this->accessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->accessor;
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
