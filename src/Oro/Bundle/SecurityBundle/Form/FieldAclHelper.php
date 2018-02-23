<?php

namespace Oro\Bundle\SecurityBundle\Form;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SecurityBundle\Validator\Constraints\FieldAccessGranted;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolation;

class FieldAclHelper
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var ConfigManager */
    private $configManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param ConfigManager                 $configManager
     * @param DoctrineHelper                $doctrineHelper
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
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
    public function isFieldViewGranted($entity, $fieldName)
    {
        if (!is_object($entity)) {
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
    public function isFieldModificationGranted($entity, $fieldName)
    {
        if (!is_object($entity)) {
            return true;
        }

        $permission = $this->doctrineHelper->isNewEntity($entity)
            ? 'CREATE'
            : 'EDIT';

        return $this->authorizationChecker->isGranted($permission, new FieldVote($entity, $fieldName));
    }

    /**
     * Adds FieldAccessGranted constraint violation to the given form field errors.
     * If the form field has other errors, they are removed.
     *
     * @param FormInterface $formField
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
}
