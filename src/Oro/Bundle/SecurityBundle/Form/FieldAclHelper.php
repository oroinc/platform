<?php

namespace Oro\Bundle\SecurityBundle\Form;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Acl\Voter\EntityClassResolverUtil;
use Oro\Bundle\SecurityBundle\Validator\Constraints\FieldAccessGranted;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Provides methods for checking access to entity fields.
 */
class FieldAclHelper
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private ConfigManager $configManager,
        private DoctrineHelper $doctrineHelper
    ) {
    }

    /**
     * Checks whether the field ACL feature is enabled for the given entity type.
     *
     * @param string $entityClass
     *
     * @return bool
     */
    public function isFieldAclEnabled($entityClass)
    {
        $entityConfig = $this->getEntityConfig($entityClass);
        if (null === $entityConfig) {
            return false;
        }

        return
            $entityConfig->get('field_acl_supported')
            && $entityConfig->get('field_acl_enabled');
    }

    /**
     * Checks whether a field should be displayed even if a user does not have VIEW permission for it.
     *
     * @param string $entityClass
     *
     * @return bool
     */
    public function isRestrictedFieldsVisible($entityClass)
    {
        $entityConfig = $this->getEntityConfig($entityClass);
        if (null === $entityConfig) {
            return true;
        }

        return (bool)$entityConfig->get('show_restricted_fields');
    }

    /**
     * Checks whether the displaying of the given field is granted.
     *
     * @param mixed  $entity
     * @param string $fieldName
     *
     * @return bool
     */
    public function isFieldViewGranted(object $entity, string $fieldName)
    {
        $className = EntityClassResolverUtil::getEntityClass($entity);
        if (!$this->isFieldAclEnabled($className)) {
            return true;
        }

        return $this->authorizationChecker->isGranted('VIEW', new FieldVote($entity, $fieldName));
    }

    /**
     * Checks whether the modification of the given field is granted.
     *
     * @param mixed  $entity
     * @param string $fieldName
     *
     * @return bool
     */
    public function isFieldModificationGranted(object $entity, $fieldName)
    {
        $className = EntityClassResolverUtil::getEntityClass($entity);
        if (!$this->isFieldAclEnabled($className)) {
            return true;
        }

        $permission = $this->doctrineHelper->isNewEntity($entity)
            ? 'CREATE'
            : 'EDIT';

        return $this->authorizationChecker->isGranted($permission, new FieldVote($entity, $fieldName));
    }

    /**
     * Checks whether the rendering of the given field is granted.
     */
    public function isFieldAvailable(object $entity, string $fieldName): bool
    {
        $className = EntityClassResolverUtil::getEntityClass($entity);
        if (!$this->isFieldAclEnabled($className)) {
            return true;
        }

        if ($this->isRestrictedFieldsVisible($className)) {
            return true;
        }

        return $this->isFieldModificationGranted($entity, $fieldName);
    }

    /**
     * Adds FieldAccessGranted constraint violation to the given form field errors.
     * If the form field has other errors, they are removed.
     */
    public function addFieldModificationDeniedFormError(FormInterface $formField)
    {
        // clear all other validation errors
        if ($formField instanceof Form && $formField->getErrors()->count()) {
            $clearClosure = \Closure::bind(
                function (Form $form, $fieldName) {
                    $form->{$fieldName} = [];
                },
                $formField,
                Form::class
            );
            $clearClosure($formField, 'errors');
        }

        $constraint = new FieldAccessGranted();
        $message = $constraint->message;
        $violation = new ConstraintViolation(
            $message,
            $message,
            [],
            '',
            '',
            '',
            null,
            null,
            $constraint
        );
        $formField->addError(new FormError($message, $message, [], null, $violation));
    }

    /**
     * @param string $entityClass
     *
     * @return ConfigInterface|null
     */
    private function getEntityConfig($entityClass)
    {
        $entityConfig = null;
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)
            && $this->configManager->hasConfig($entityClass)
        ) {
            $entityConfig = $this->configManager->getEntityConfig('security', $entityClass);
        }

        return $entityConfig;
    }

    private function getClassName(string|object $entityOrClassName): string
    {
        return is_object($entityOrClassName) ? ClassUtils::getRealClass($entityOrClassName) : $entityOrClassName;
    }
}
