<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Handler\EntityApiBaseHandler;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\EntityManager\FormBuilder;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;

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

    /** @var  EntityRoutingHelper */
    protected $entityRoutingHelper;

    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadataProvider;

    /**
     * @param Registry $registry
     * @param FormBuilder $formBuilder
     * @param EntityApiBaseHandler $handler
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param OwnershipMetadataProvider $ownershipMetadataProvider
     */
    public function __construct(
        Registry $registry,
        FormBuilder $formBuilder,
        EntityApiBaseHandler $handler,
        EntityRoutingHelper $entityRoutingHelper,
        OwnershipMetadataProvider $ownershipMetadataProvider
    ) {
        $this->registry = $registry;
        $this->em = $this->registry->getManager();
        $this->formBuilder = $formBuilder;
        $this->handler = $handler;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
    }

    /**
     * @param $entity
     *
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
        $form = $this->getForm($entity);
        $data = $this->presetData($entity);

        foreach ($content as $fieldName => $fieldValue) {
            if ($this->hasAccessEditFiled($fieldName)) {
                $valueForForm = $this->prepareValueForForm($entity, $fieldName, $fieldValue);
                $valueForEntity = $this->prepareValueForEntity($entity, $fieldName, $fieldValue);
                $accessor->setValue($entity, $fieldName, $valueForEntity);
                $data[$fieldName] = $valueForForm;
                $form = $this->formBuilder->add($form, $entity, $fieldName);
            }
        }

        $this->handler->process($entity, $form, $data, 'PATCH');

        return $form;
    }

    /**
     * @param $entity
     *
     * @return array
     */
    protected function presetData($entity)
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $data = [];
        $metadata = $this->getMetadataConfig($entity);
        if (!$metadata || $metadata->isGlobalLevelOwned()) {
            return $data;
        }

        $owner = $accessor->getValue($entity, $metadata->getOwnerFieldName());
        if ($owner) {
            $data[$metadata->getOwnerFieldName()] = $accessor->getValue($owner, 'id');
        }

        return $data;
    }

    /**
     * @param $entity - object | class name entity
     *
     * @return bool|OwnershipMetadataInterface
     */
    protected function getMetadataConfig($entity)
    {
        if (is_object($entity)) {
            $entity = ClassUtils::getClass($entity);
        }

        $metadata = $this->ownershipMetadataProvider->getMetadata($entity);

        return $metadata->hasOwner()
            ? $metadata
            : false;
    }

    /**
     * @param $fieldName
     *
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
     *
     * @return bool
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function prepareValueForForm($entity, $fieldName, $fieldValue)
    {
        /** @var ClassMetadata $metaData */
        $metaData = $this->getMetaData($entity);

        // search simple field
        if ($metaData->hasField($fieldName)) {
            $fieldInfo = $metaData->getFieldMapping($fieldName);

            $fieldType = $fieldInfo['type'];
            if (in_array($fieldType, ['boolean'])) {
                $fieldValue = (bool)$fieldValue;
            }
        }

        return $fieldValue;
    }

    /**
     * @param $entity
     * @param $fieldName
     * @param $fieldValue
     *
     * @return \DateTime
     */
    protected function prepareValueForEntity($entity, $fieldName, $fieldValue)
    {
        /** @var ClassMetadata $metaData */
        $metaData = $this->getMetaData($entity);

        // search simple field
        if ($metaData->hasField($fieldName)) {
            $fieldInfo = $metaData->getFieldMapping($fieldName);

            $fieldType = $fieldInfo['type'];
            if (in_array($fieldType, ['datetime','date'])) {
                $fieldValue = new \DateTime($fieldValue);
            }

            if (in_array($fieldType, ['boolean'])) {
                $fieldValue = (bool)$fieldValue;
            }
        }

        if ($metaData->hasAssociation($fieldName)) {
            $fieldInfo = $metaData->getAssociationMapping($fieldName);

            $entity = $this->entityRoutingHelper->getEntity($fieldInfo['targetEntity'], $fieldValue);
            $fieldValue = $entity;
        }

        return $fieldValue;
    }

    /**
     * @param $entity
     *
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    protected function getMetaData($entity)
    {
        $className = ClassUtils::getClass($entity);
        $em = $this->registry->getManager();

        return $em->getClassMetadata($className);
    }
}
