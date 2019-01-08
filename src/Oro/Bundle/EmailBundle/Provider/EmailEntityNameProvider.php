<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Add email to resolved entity names using "email" format
 */
class EmailEntityNameProvider implements EntityNameProviderInterface
{
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getName($format, $locale, $entity)
    {
        if (!$this->isSupported($format)) {
            return false;
        }

        if ($entity instanceof EmailOwnerInterface) {
            $fields = $entity->getEmailFields();
            foreach ($fields as $field) {
                try {
                    $email = $this->propertyAccessor->getValue($entity, $field);
                    if (!$email) {
                        continue;
                    }

                    $firstName = $entity->getFirstName();
                    $lastName = $entity->getLastName();
                    if ($firstName && $lastName) {
                        return sprintf('%s %s - %s', $firstName, $lastName, $email);
                    }

                    if ($firstName || $lastName) {
                        return sprintf('%s - %s', $firstName ?? $lastName, $email);
                    }

                    return $email;
                } catch (NoSuchPropertyException $e) {
                }
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getNameDQL($format, $locale, $className, $alias)
    {
        return false;
    }

    /**
     * @param string $format
     * @return bool
     */
    private function isSupported(string $format): bool
    {
        return $format === 'email';
    }
}
