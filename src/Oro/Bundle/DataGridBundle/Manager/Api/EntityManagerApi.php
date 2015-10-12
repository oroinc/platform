<?php

namespace Oro\Bundle\DataGridBundle\Manager\Api;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Rhumsaa\Uuid\Console\Exception;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Oro\Bundle\DataGridBundle\Manager\Api\EntityManager\FormBuilder;

class EntityManagerApi
{
    protected $registry;

    protected $formBuilder;

    protected $em;

    public function __construct(
        Registry $registry,
        FormBuilder $formBuilder
    )
    {
        $this->registry = $registry;
        $this->formBuilder = $formBuilder;

        $this->em = $this->registry->getManager();
    }

    public function getEntity($className, $entityId)
    {
        return $this->registry->getManager()->find($className, $entityId);
    }

    public function hasAccessEditFiled($fieldName)
    {
        $deniedFields = [
            'id'
        ];

        if (isset($deniedFields[$fieldName])) {
            return false;
        }

        return true;
    }

    public function updateField($entity, $fieldName, $fieldValue)
    {
        if ($this->hasAccessEditFiled($fieldName)) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $oldVakue = $fieldValue;
            $fieldValue = $this->prepareFieldValue($entity, $fieldName, $fieldValue);

            $accessor->setValue($entity, $fieldName, $fieldValue);

            $form = $this->formBuilder->getForm($entity, $fieldName);

            $form->submit([
                $fieldName => $oldVakue,
                'owner' => $entity->getOwner()->getId()
            ]);

            if ($form->isValid()) {
                $em = $this->registry->getManager();
                $em->persist($entity);
                $em->flush();
            }
        } else {
            throw new Exception("Field can`t be changed");
        }
    }

    protected function prepareFieldValue($entity, $fieldName, $fieldValue)
    {
        $className = get_class($entity);
        $em = $this->registry->getManager();
        $metaData = $em->getClassMetadata($className);
        $accessor = PropertyAccess::createPropertyAccessor();
        $fieldInfo = $accessor->getValue($metaData->fieldMappings, '['.$fieldName.']');
        $fieldType = $fieldInfo['type'];

        if ($fieldType == 'datetime') {
            $fieldValue = new \DateTime($fieldValue);
        }

        return $fieldValue;
    }
}
