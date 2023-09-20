<?php

namespace Oro\Bundle\SecurityBundle\Form\Extension;

use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Form extension that check access to fields.
 * It cannot be registered with form.type_extension tag because
 * this extension should be registered as first extension for all forms.
 */
class AclProtectedFieldTypeExtension extends AbstractTypeExtension
{
    /** @var FieldAclHelper */
    protected $fieldAclHelper;

    /** @var LoggerInterface */
    protected $logger;

    /** @var bool */
    protected $showRestricted = false;

    /** @var array List of non accessible fields with committed data */
    protected $disabledFields = [];

    /**
     * This option indicates the field for which check the permission (this is necessary if the name of
     * the form field does not match the name of the entity field name).
     */
    public const CHECK_FIELD_NAME = 'check_field_name';

    public function __construct(FieldAclHelper $fieldAclHelper, LoggerInterface $logger)
    {
        $this->fieldAclHelper = $fieldAclHelper;
        $this->logger = $logger;
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit'], -10);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        $entity = $this->getEntityByForm($form);
        if (!$entity) {
            return;
        }

        $this->removeTrashFields($view, $form);
        $this->filterProtectedFields($view, $form, $entity);
    }

    /**
     * Removes 'trash' view fields that can be added to removed fields in finishView method of the form.
     */
    private function removeTrashFields(FormView $view, FormInterface $form): void
    {
        foreach (array_keys($view->children) as $fieldName) {
            if (!$form->has($fieldName) && !$view->children[$fieldName] instanceof FormView) {
                unset($view->children[$fieldName]);
            }
        }
    }

    private function filterProtectedFields(FormView $view, FormInterface $form, object $entity): void
    {
        foreach ($view as $fieldName => $fieldView) {
            $aclField = $this->getAclField($form, $fieldName);
            $readonly = !$this->fieldAclHelper->isFieldModificationGranted($entity, $aclField);
            if ($this->showRestricted) {
                $this->processRestrictedFields($fieldView, $readonly);
            } elseif ($readonly) {
                $view->offsetUnset($fieldName);
            }
        }
    }

    private function processRestrictedFields(FormView $view, bool $readonly): void
    {
        $view->vars['attr']['readonly'] = $readonly;
        $view->vars['disabled'] = $readonly;
        foreach ($view->children as $childForm) {
            $this->processRestrictedFields($childForm, $readonly);
        }
    }

    /**
     * For security reasons, all fields that are not available for modification are ignored or remove from form.
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if (empty($data)) {
            return;
        }

        [$protectedFields, $permittedData] = $this->processProtectedFields($form, $data);
        if ($protectedFields) {
            $this->processProtectedFieldsErrors($form, $protectedFields);
        }

        $event->setData($permittedData);
    }

    private function processProtectedFields(FormInterface $form, array $data): array
    {
        $protectedFields = [];
        $entity = $this->getEntityByForm($form);
        if (null === $entity?->getId()) {
            return [$protectedFields, $data];
        }

        foreach ($form->all() as $field) {
            $aclField = $this->getAclField($form, $field->getName());
            if (!$this->fieldAclHelper->isFieldModificationGranted($entity, $aclField)) {
                if (!$this->showRestricted && isset($data[$field->getName()])) {
                    $protectedFields[] = $field->getName();
                } else {
                    $form->remove($field->getName());
                }

                unset($data[$field->getName()]);
            }
        }

        return [$protectedFields, $data];
    }

    protected function isApplicable(array $options)
    {
        if (empty($options['data_class'])) {
            return false;
        }

        $className = $options['data_class'];
        $isFieldAclEnabled = $this->fieldAclHelper->isFieldAclEnabled($className);
        if ($isFieldAclEnabled) {
            $this->showRestricted = $this->fieldAclHelper->isRestrictedFieldsVisible($className);
        }

        return $isFieldAclEnabled;
    }

    protected function getEntityByForm(FormInterface $form)
    {
        $result = null;

        $config = $form->getConfig();
        if ($config->getMapped()) {
            $data = $form->getData();
            $className = $config->getDataClass();
            if ($data instanceof $className) {
                $result = $data;
            }
        }

        return $result;
    }

    private function getAclField(FormInterface $form, string $fieldName): ?string
    {
        if ($form->has($fieldName)) {
            $field = $form->get($fieldName);
            $config = $field->getConfig();

            return $config->getOption(self::CHECK_FIELD_NAME) ?? $fieldName;
        }

        return $fieldName;
    }

    /**
     * In case if we have an error in the non-accessible fields - add validation error.
     */
    private function processProtectedFieldsErrors(FormInterface $form, array $protectedFields): void
    {
        $message = 'You do not have access to change the fields: %s.';
        $form->addError(new FormError(sprintf($message, implode(', ', $protectedFields))));
        foreach ($protectedFields as $fieldName) {
            $message = sprintf('Non accessible field `%s` detected in form `%s`.', $fieldName, $form->getName());
            $this->logger->error($message);
        }
    }

    /**
     * @deprecated since 5.1, will be removed in 6.0
     */
    public function postSubmit(FormEvent $event)
    {
    }

    /**
     * @deprecated since 5.1, will be removed in 6.0
     */
    protected function isFormGranted($entity, FormInterface $form)
    {
        if (!$this->showRestricted || !is_object($entity)) {
            return false;
        }

        return $this->fieldAclHelper->isFieldViewGranted($entity, $this->getPropertyByForm($form));
    }

    /**
     * @deprecated since 5.1, will be removed in 6.0
     */
    protected function isViewGranted($entity, FormInterface $form)
    {
        if (!$this->showRestricted || !is_object($entity)) {
            return false;
        }

        return $this->fieldAclHelper->isFieldViewGranted($entity, $this->getPropertyByForm($form));
    }

    /**
     * @deprecated since 5.1, will be removed in 6.0
     */
    protected function getPropertyByForm(FormInterface $form)
    {
        $result = $form->getName();

        $config = $form->getConfig();
        if ($config->getMapped()) {
            $propertyPath = $config->getPropertyPath();
            if (null !== $propertyPath && $propertyPath->getLength() === 1) {
                $result = (string)$propertyPath;
            }
        }

        return $result;
    }

    /**
     * @deprecated since 5.1, will be removed in 6.0
     */
    protected function processHiddenFieldsWithErrors(array $hiddenFieldsWithErrors, FormView $view, FormInterface $form)
    {
        throw new \LogicException('Use processProtectedFieldsErrors method instead processHiddenFieldsWithErrors.');
    }
}
