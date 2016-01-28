<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager\Field;

use FOS\RestBundle\Util\Codes;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Exception\EntityHasFieldException;
use Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException;

class EntityFieldValidator
{
    /** @var Registry */
    protected $registry;

    /** @var CustomGridFieldValidatorInterface[]|array */
    protected $validators;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry, TranslatorInterface $translator)
    {
        $this->registry   = $registry;
        $this->translator = $translator;
        $this->validators = [];
    }

    /**
     * @param CustomGridFieldValidatorInterface $validator
     * @param string                            $key
     */
    public function addValidator(CustomGridFieldValidatorInterface $validator, $key)
    {
        $this->validators[$key] = $validator;
    }

    /**
     * @param Object $entity
     * @param array  $content
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

        if ($this->hasCustomFieldValidator($entity)) {
            $this->customFieldValidation($entity, $keys);
        }
    }

    /**
     * @param Object $entity
     *
     * @return bool
     */
    protected function hasCustomFieldValidator($entity)
    {
        return array_key_exists(
            $this->getClassName($entity),
            $this->validators
        );
    }

    /**
     * @param Object $entity
     *
     * @return string
     */
    protected function getClassName($entity)
    {
        return str_replace('\\', '_', ClassUtils::getClass($entity));
    }

    /**
     * @param Object $entity
     * @param array  $fieldList
     *
     * @throws FieldUpdateAccessException
     */
    protected function customFieldValidation($entity, $fieldList)
    {
        $customValidator = $this->getCustomValidator($entity);

        foreach ($fieldList as $field) {
            $isValid = $customValidator->hasAccessEditField($entity, $field);

            if (false === $isValid) {
                throw new FieldUpdateAccessException(
                    $this->translator->trans('oro.entity.controller.message.access_denied'),
                    Codes::HTTP_FORBIDDEN
                );
            }
        }
    }

    /**
     * @param Object $entity
     *
     * @return CustomGridFieldValidatorInterface
     */
    protected function getCustomValidator($entity)
    {
        $entityName = $this->getClassName($entity);

        return $this->validators[$entityName];
    }

    /**
     * @param Object $entity
     * @param string $fieldName
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
     * @param string $fieldName
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
     * @param Object $entity
     * @param string $fieldName
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
     * @param Object $entity
     *
     * @return ClassMetadata
     */
    protected function getMetaData($entity)
    {
        $className = ClassUtils::getClass($entity);
        $em        = $this->registry->getManager();

        return $em->getClassMetadata($className);
    }
}
