<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager\Field;

use FOS\RestBundle\Util\Codes;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Exception\EntityHasFieldException;
use Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException;

class EntityFieldValidator
{
    /** @var Registry */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param $entity
     * @param $content
     *
     * @throws EntityHasFieldException
     * @throws FieldUpdateAccessException
     */
    public function validate($entity, $content)
    {
        $keys = array_keys($content);
        foreach ($keys as $fieldName) {
            $this->validateFieldName($entity, $fieldName);
        }
    }

    /**
     * @param $entity
     * @param $fieldName
     *
     * @throws FieldUpdateAccessException
     * @throws EntityHasFieldException
     */
    protected function validateFieldName($entity, $fieldName)
    {
        if (!$this->hasField($entity, $fieldName)) {
            throw new EntityHasFieldException('oro.entity.controller.message.field_not_found', Codes::HTTP_NOT_FOUND);
        }

        if (!$this->hasAccessEditFiled($fieldName)) {
            throw new FieldUpdateAccessException('oro.entity.controller.message.access_denied', Codes::HTTP_FORBIDDEN);
        }
    }

    /**
     * @param $fieldName
     *
     * @return bool
     */
    protected function hasAccessEditFiled($fieldName)
    {
        $blackList = EntityFieldBlackList::getValues();
        if ((in_array($fieldName, $blackList))) {
            return false;
        }

        return true;
    }

    /**
     * @param $entity
     * @param $fieldName
     *
     * @return bool
     */
    protected function hasField($entity, $fieldName)
    {
        /** @var ClassMetadata $metaData */
        $metaData = $this->getMetaData($entity);

        return $metaData->hasField($fieldName) || $metaData->hasAssociation($fieldName);
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
