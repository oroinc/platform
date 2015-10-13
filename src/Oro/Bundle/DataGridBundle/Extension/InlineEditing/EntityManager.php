<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Handler\EntityApiBaseHandler;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\EntityManager\FormBuilder;

class EntityManager
{
    /** @var Registry */
    protected $registry;

    /** @var FormBuilder */
    protected $formBuilder;

    /** @var ObjectManager */
    protected $em;

    /** @var EntityApiBaseHandler */
    protected $handler;

    /**
     * @param Registry $registry
     * @param FormBuilder $formBuilder
     * @param EntityApiBaseHandler $handler
     */
    public function __construct(Registry $registry, FormBuilder $formBuilder, EntityApiBaseHandler $handler)
    {
        $this->registry = $registry;
        $this->em = $this->registry->getManager();
        $this->formBuilder = $formBuilder;
        $this->handler = $handler;
    }

    /**
     * @param $entity
     * @return FormInterface
     */
    public function getForm($entity)
    {
        return $this->formBuilder->getForm($entity);
    }

    /**
     * @param $entity
     * @param $content
     *
     * @return FormInterface
     */
    public function update($entity, $content)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $formData = [
            'owner' => $entity->getOwner()->getId()
        ];

        $form = $this->getForm($entity);

        foreach ($content as $fieldName => $fieldValue) {
            if ($this->hasAccessEditFiled($fieldName)) {
                $oldVakue = $fieldValue;
                $fieldValue = $this->prepareFieldValue($entity, $fieldName, $fieldValue);
                $accessor->setValue($entity, $fieldName, $fieldValue);

                $formData[$fieldName] = $oldVakue;
                $form = $this->formBuilder->add($form, $entity, $fieldName);
            }
        }
        $this->handler->process($entity, $form, $formData, 'PATCH');

        return $form;
    }

    /**
     * @param $fieldName
     * @return bool
     */
    protected function hasAccessEditFiled($fieldName)
    {
        $blackList = FieldsBlackList::getValues();
        if ((in_array($fieldName, $blackList))) {
            return false;
        }

        return true;
    }

    /**
     * @param $entity
     * @param $fieldName
     * @param $fieldValue
     * @return \DateTime
     */
    protected function prepareFieldValue($entity, $fieldName, $fieldValue)
    {
        $className = get_class($entity);
        $em = $this->registry->getManager();
        $metaData = $em->getClassMetadata($className);
        $accessor = PropertyAccess::createPropertyAccessor();
        $fieldInfo = $accessor->getValue($metaData->fieldMappings, '['.$fieldName.']');
        $fieldType = $fieldInfo['type'];

        if ($fieldType === 'datetime') {
            $fieldValue = new \DateTime($fieldValue);
        }

        return $fieldValue;
    }
}
