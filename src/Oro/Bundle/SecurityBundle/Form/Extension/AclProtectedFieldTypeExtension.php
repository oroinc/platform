<?php

namespace Oro\Bundle\SecurityBundle\Form\Extension;

use Oro\Bundle\SecurityBundle\Form\FieldAclHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AclProtectedFieldTypeExtension extends AbstractTypeExtension
{
    /** @var FieldAclHelper */
    protected $fieldAclHelper;

    /** @var LoggerInterface */
    protected $logger;

    /** @var bool */
    protected $showRestricted = true;

    /** @var array List of non accessable fields with commited data */
    protected $disabledFields = [];

    /**
     * @param FieldAclHelper  $fieldAclHelper
     * @param LoggerInterface $logger
     */
    public function __construct(FieldAclHelper $fieldAclHelper, LoggerInterface $logger)
    {
        $this->fieldAclHelper = $fieldAclHelper;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')
            ? 'Symfony\Component\Form\Extension\Core\Type\FormType'
            : 'form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        // Filter submitted data and ignore data for restricted fields
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit'], -255);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->isApplicable($options)) {
            return;
        }

        $entity = $this->getEntityByForm($form);
        $hiddenFieldsWithErrors = [];
        /** @var FormInterface $childForm */
        foreach ($form as $childName => $childForm) {
            if ($this->isFormGranted($entity, $childForm)) {
                continue;
            }

            if ($this->isViewGranted($entity, $childForm)) {
                $view->children[$childName]->vars['attr']['readonly'] = true;
                if (count($view->children[$childName]->children)) {
                    foreach ($view->children[$childName]->children as $child) {
                        $child->vars['attr']['readonly'] = true;
                    }
                }
            } else {
                $view->children[$childName]->setRendered();
                if ($childForm->getErrors()->count()) {
                    $hiddenFieldsWithErrors[$childName] = (string)$childForm->getErrors();
                }
            }
        }

        $this->processHiddenFieldsWithErrors($hiddenFieldsWithErrors, $view, $form);
    }

    /**
     * Used on post submit to add validation errors
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $entity = $event->getData();
        $className = $form->getConfig()->getDataClass();
        if ($entity instanceof $className) {
            foreach ($this->disabledFields as $field) {
                $this->fieldAclHelper->addFieldModificationDeniedFormError($form->get($field));
            }
        }
    }

    /**
     * Validate input data. If form data contain data for forbidden fields - set the original data for such fields and
     * collect this fields to add validation error.
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (empty($data)) {
            return;
        }

        $form = $event->getForm();
        $entity = $this->getEntityByForm($form);
        if (null === $entity) {
            return;
        }

        foreach ($form->all() as $childForm) {
            if ($this->isFormGranted($entity, $childForm)) {
                continue;
            }

            $fieldName = $childForm->getName();
            if (isset($data[$fieldName]) && $data[$fieldName]) {
                if (empty($childForm->all()) && $data[$fieldName] !== $childForm->getData()) {
                    $this->disabledFields[] = $fieldName;
                    $data[$fieldName] = $childForm->getData();
                }

                if (count($childForm->all())) {
                    foreach ($childForm->all() as $child) {
                        /** @var Form $child */
                        $childFieldName = $child->getName();
                        if (isset($data[$fieldName][$childFieldName])
                            && $data[$fieldName][$childFieldName] != $child->getViewData()
                        ) {
                            if (!isset($this->disabledFields[$fieldName])) {
                                $this->disabledFields[] = $fieldName;
                            }
                            $data[$fieldName][$child->getName()] = $child->getViewData();
                        }
                    }
                }
            }
        }

        $event->setData($data);
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    protected function isApplicable(array $options)
    {
        if (empty($options['data_class'])) {
            return false;
        }

        $className = $options['data_class'];
        $isFieldAclEnabled = $this->fieldAclHelper->isFieldAclEnabled($className);

        $this->showRestricted = true;
        if ($isFieldAclEnabled) {
            $this->showRestricted = $this->fieldAclHelper->isRestrictedFieldsVisible($className);
        }

        return $isFieldAclEnabled;
    }

    /**
     * Check if current session allowed to modify form
     *
     * @param bool|object   $entity
     * @param FormInterface $form
     *
     * @return bool
     */
    protected function isFormGranted($entity, FormInterface $form)
    {
        if (!is_object($entity)) {
            return true;
        }

        return $this->fieldAclHelper->isFieldModificationGranted($entity, $this->getPropertyByForm($form));
    }

    /**
     * @param bool|object   $entity
     * @param FormInterface $form
     *
     * @return bool
     */
    protected function isViewGranted($entity, FormInterface $form)
    {
        if (!$this->showRestricted || !is_object($entity)) {
            return false;
        }

        return $this->fieldAclHelper->isFieldViewGranted($entity, $this->getPropertyByForm($form));
    }

    /**
     * @param FormInterface $form
     *
     * @return object|null
     */
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

    /**
     * Return class property form mapped to
     *
     * @param FormInterface $form
     *
     * @return string
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
     * in case if we have error in the non accessible fields - add validation error.
     *
     * @param array         $hiddenFieldsWithErrors
     * @param FormView      $view
     * @param FormInterface $form
     */
    protected function processHiddenFieldsWithErrors(array $hiddenFieldsWithErrors, FormView $view, FormInterface $form)
    {
        if (count($hiddenFieldsWithErrors)) {
            $viewErrors = array_key_exists('errors', $view->vars) ? $view->vars['errors'] : [];
            $errorsArray = [];
            foreach ($viewErrors as $error) {
                $errorsArray[] = $error;
            }
            $errorsArray[] = new FormError(
                sprintf(
                    'The form contains fields "%s" that are required or not valid but you have no access to them. '
                    . 'Please contact your administrator to solve this issue.',
                    implode(', ', array_keys($hiddenFieldsWithErrors))
                )
            );
            $view->vars['errors'] = new FormErrorIterator($form, $errorsArray);
            foreach ($hiddenFieldsWithErrors as $fieldName => $errorsString) {
                $this->logger->error(
                    sprintf(
                        'Non accessible field `%s` detected in form `%s`. Validation errors: %s',
                        $fieldName,
                        $form->getName(),
                        $errorsString
                    )
                );
            }
        }
    }
}
