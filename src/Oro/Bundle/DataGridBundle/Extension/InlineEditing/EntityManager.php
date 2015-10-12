<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditings;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\EntityManager\FormBuilder;

class EntityManager
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

    public function updateFields($entity, $content)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $fromData = [
            'owner' => $entity->getOwner()->getId()
        ];

        $form = $this->formBuilder->getForm($entity);

        foreach ($content as $fieldName => $fieldValue) {
            if ($this->hasAccessEditFiled($fieldName)) {
                $oldVakue = $fieldValue;
                $fieldValue = $this->prepareFieldValue($entity, $fieldName, $fieldValue);
                $accessor->setValue($entity, $fieldName, $fieldValue);

                $fromData[$fieldName] = $oldVakue;
                $form = $this->formBuilder->add($form, $entity, $fieldName);
            }
        }

        if (count($fromData) > 1) {
            $form->submit($fromData);

            if ($form->isValid()) {
                $em = $this->registry->getManager();
                $em->persist($entity);
                $em->flush();
            }
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
