<?php

namespace Oro\Bundle\DataGridBundle\Manager\Api;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\PropertyAccess\PropertyAccess;

class EntityManagerApi
{
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function getEntity($className, $entityId)
    {
        return $this->registry->getManager()->find($className, $entityId);
    }

    public function updateField($entity, $fieldName, $fieldValue)
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $accessor->setValue($entity, $fieldName, $fieldValue);

        $em = $this->registry->getManager();

        $em->persist($entity);
        $em->flush();
    }
}
