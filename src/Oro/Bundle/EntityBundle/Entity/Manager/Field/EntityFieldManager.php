<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager\Field;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityBundle\Form\EntityField\Handler\EntityApiBaseHandler;
use Oro\Bundle\EntityBundle\Form\EntityField\FormBuilder;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;

/**
 * Class EntityFieldManager
 * @package Oro\Bundle\EntityBundle\Entity\Manager\Field
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EntityFieldManager
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

    /** @var EntityFieldValidator */
    protected $entityFieldValidator;

    /**
     * @param Registry $registry
     * @param FormBuilder $formBuilder
     * @param EntityApiBaseHandler $handler
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param OwnershipMetadataProvider $ownershipMetadataProvider
     * @param EntityFieldValidator $entityFieldValidator
     */
    public function __construct(
        Registry $registry,
        FormBuilder $formBuilder,
        EntityApiBaseHandler $handler,
        EntityRoutingHelper $entityRoutingHelper,
        OwnershipMetadataProvider $ownershipMetadataProvider,
        EntityFieldValidator $entityFieldValidator
    ) {
        $this->registry = $registry;
        $this->em = $this->registry->getManager();
        $this->formBuilder = $formBuilder;
        $this->handler = $handler;
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->entityFieldValidator = $entityFieldValidator;
    }

    /**
     * @param $entity
     * @param $content
     *
     * @return array
     */
    public function update($entity, $content)
    {
        $this->entityFieldValidator->validate($entity, $content);

        return $this->processUpdate($entity, $content);
    }

    /**
     * @param $entity
     * @param $content
     *
     * @return array
     */
    protected function processUpdate($entity, $content)
    {
        $form = $this->formBuilder->build($entity, $content);
        $data = $this->presetData($entity);

        foreach ($content as $fieldName => $fieldValue) {
            $fieldValue = $this->cleanupValue($fieldValue);
            $data[$fieldName] = $this->prepareValueForForm($entity, $fieldName, $fieldValue);
        }

        $changeSet = $this->handler->process($entity, $form, $data, 'PATCH');

        return array($form, $changeSet);
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
     *
     * @return \Doctrine\Common\Persistence\Mapping\ClassMetadata
     */
    protected function getMetaData($entity)
    {
        $className = ClassUtils::getClass($entity);
        $em = $this->registry->getManager();

        return $em->getClassMetadata($className);
    }

    /**
     * @param $fieldValue
     * @return string
     */
    protected function cleanupValue($fieldValue)
    {
        if (is_string($fieldValue)) {
            $fieldValue = trim($fieldValue);
        }

        return $fieldValue;
    }
}
