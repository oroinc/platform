<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\EntityManager;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class FormBuilder
{
    protected $fieldTypeMap = [
        'string' => 'text',
        'datetime' => 'oro_datetime',
        'date' => 'oro_date'
    ];

    /** @var FormFactory */
    protected $formFactory;

    /** @var Registry */
    protected $registry;

    /**
     * @param FormFactory $formFactory
     * @param Registry $registry
     */
    public function __construct(
        FormFactory $formFactory,
        Registry $registry
    ) {
        $this->formFactory = $formFactory;
        $this->registry = $registry;
    }

    /**
     * @param $entity
     * @return FormInterface
     */
    public function getForm($entity)
    {
        $form = $this->formFactory->createBuilder('form', $entity, array('csrf_protection' => false))
            ->getForm();

        return $form;
    }

    /**
     * @param FormInterface $form
     * @param $entity
     * @param $fieldName
     *
     * @return FormInterface
     */
    public function add(FormInterface $form, $entity, $fieldName)
    {
        $fieldType = $this->getAssociationType($entity, $fieldName);
        $form = $form->add($fieldName, $fieldType);

        return $form;
    }

    /**
     * @param $entity
     * @param $fieldName
     * @return string
     */
    protected function getAssociationType($entity, $fieldName)
    {
        $className = get_class($entity);
        $em = $this->registry->getManager();
        $metaData = $em->getClassMetadata($className);
        $accessor = PropertyAccess::createPropertyAccessor();
        $fieldInfo = $accessor->getValue($metaData->fieldMappings, '['.$fieldName.']');
        $fieldType = $fieldInfo['type'];

        $type = $fieldType;
        if (array_key_exists($fieldType, $this->fieldTypeMap)) {
            $type = $this->fieldTypeMap[$fieldType];
        }

        return $type;
    }
}
