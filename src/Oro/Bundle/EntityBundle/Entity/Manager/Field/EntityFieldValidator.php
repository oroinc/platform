<?php

namespace Oro\Bundle\EntityBundle\Entity\Manager\Field;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Exception\EntityHasFieldException;
use Oro\Bundle\EntityBundle\Exception\FieldUpdateAccessException;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates entity fields.
 */
class EntityFieldValidator
{
    /** @var CustomGridFieldValidatorInterface[]|array */
    protected $validators;

    public function __construct(
        private ManagerRegistry $registry,
        private TranslatorInterface $translator,
        private FieldHelper $fieldHelper
    ) {
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
                    Response::HTTP_FORBIDDEN
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
            throw new EntityHasFieldException(
                'oro.entity.controller.message.field_not_found',
                Response::HTTP_NOT_FOUND
            );
        }

        if (!$this->hasAccessEditFiled($fieldName)) {
            throw new FieldUpdateAccessException(
                'oro.entity.controller.message.access_denied',
                Response::HTTP_FORBIDDEN
            );
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

        return $metaData->hasField($fieldName)
            || $metaData->hasAssociation($fieldName)
            || $this->fieldHelper->getFieldConfig('enum', get_class($entity), $fieldName);
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
