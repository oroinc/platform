<?php

namespace Oro\Bundle\DataGridBundle\Manager\Api;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Form\FormFactory;

class EntityManagerApi
{
    protected $registry;

    protected $formFactory;

    protected $em;

    public function __construct(
        Registry $registry,
        FormFactory $formFactory
    )
    {
        $this->registry = $registry;
        $this->formFactory = $formFactory;
        $this->em = $this->registry->getManager();
    }

    public function getEntity($className, $entityId)
    {
        return $this->registry->getManager()->find($className, $entityId);
    }

    public function updateField($entity, $fieldName, $fieldValue)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($entity, $fieldName, $fieldValue);

        $form = $this->generateForm($entity, $fieldName);
        $form->submit([
            $fieldName => $fieldValue,
            'owner' => $entity->getOwner()->getId()
        ]);
        if ($form->isValid()) {
            $em = $this->registry->getManager();
            $em->persist($entity);
            $em->flush();
        }
    }

    protected function generateForm($entity, $fieldName)
    {
        $type = 'text';

        $form = $this->formFactory->createBuilder('form', $entity, array('csrf_protection' => false))
            ->add($fieldName, $type)
            ->getForm();

        return $form;
    }
}
