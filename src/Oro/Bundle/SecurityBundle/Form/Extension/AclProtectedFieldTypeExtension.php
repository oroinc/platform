<?php

namespace Oro\Bundle\SecurityBundle\Form\Extension;

use Psr\Log\LoggerInterface;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Validator\ConstraintViolation;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Validator\Constraints\FieldAccessGranted;

class AclProtectedFieldTypeExtension extends AbstractTypeExtension
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var LoggerInterface */
    protected $logger;

    /** @var bool */
    protected $showRestricted = true;

    /**
     * @var array List of non accessable fields with commited data
     */
    protected $disabledFields = [];

    /**
     * @param SecurityFacade      $securityFacade
     * @param EntityClassResolver $entityClassResolver
     * @param DoctrineHelper      $doctrineHelper
     * @param ConfigProvider      $configProvider
     * @param LoggerInterface     $logger
     */
    public function __construct(
        SecurityFacade $securityFacade,
        EntityClassResolver $entityClassResolver,
        DoctrineHelper $doctrineHelper,
        ConfigProvider $configProvider,
        LoggerInterface $logger
    ) {
        $this->securityFacade = $securityFacade;
        $this->entityClassResolver = $entityClassResolver;
        $this->doctrineHelper = $doctrineHelper;
        $this->configProvider = $configProvider;
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
            if ($this->isFormGranted($childForm)) {
                continue;
            }

            $show = $this->showRestricted && $entity ?
                $this->securityFacade->isGranted(
                    'VIEW',
                    new FieldVote($entity, $this->getPropertyByForm($childForm))
                ) :
                false;

            if ($show) {
                $view->children[$childName]->vars['read_only'] = true;
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
        $entity = $event->getData();
        $className = $event->getForm()->getConfig()->getDataClass();
        if (!$entity instanceof $className) {
            return;
        }
        $form = $event->getForm();

        $clearFormErrors = function (Form $form, $fieldName) {
            $form->$fieldName = [];
        };

        foreach ($this->disabledFields as $field) {
            /** @var Form $fieldInstance */
            $fieldInstance = $form->get($field);
            // Clear all other validation errors
            if ($fieldInstance->getErrors()->count()) {
                $clearClosure = \Closure::bind($clearFormErrors, $fieldInstance, 'Symfony\Component\Form\Form');
                $clearClosure($fieldInstance, 'errors');
            }

            // add Field ACL validation error
            $fieldInstance->addError($this->getFieldForbiddenFormError());
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
        foreach ($form->all() as $childForm) {
            if ($this->isFormGranted($childForm)) {
                continue;
            }

            $fieldName = $childForm->getName();
            if (isset($data[$fieldName]) && $data[$fieldName] && $data[$fieldName] !== $childForm->getData()) {
                $this->disabledFields[] = $childForm->getName();
            }
            $data[$childForm->getName()] = $childForm->getData();
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
        $className = empty($options['data_class']) ? false : $options['data_class'];
        if (!$className || !$this->entityClassResolver->isEntity($className)) {
            // apply extension only to forms that bound to entities
            // cause there's no way to get object identifier for non-entity (can be any field, or even without it)
            return false;
        }

        try {
            $securityConfig    = $this->configProvider->getConfig($className);
            $isFieldAclEnabled = ($securityConfig->get('field_acl_supported')
                && $securityConfig->get('field_acl_enabled'));
            $this->showRestricted = $securityConfig->get('show_restricted_fields');
        } catch (\Exception $e) {
            $isFieldAclEnabled = false;
            $this->showRestricted = true;
        }

        return $isFieldAclEnabled;
    }

    /**
     * Check if current session allowed to modify form
     *
     * @param FormInterface $form
     *
     * @return bool
     */
    protected function isFormGranted(FormInterface $form)
    {
        $entity = $this->getEntityByForm($form->getParent());
        if (!$entity) {
            return true;
        }

        $isNewEntity = is_null($this->doctrineHelper->getSingleEntityIdentifier($entity));

        return $this->securityFacade->isGranted(
            $isNewEntity ? 'CREATE' : 'EDIT',
            new FieldVote($entity, $this->getPropertyByForm($form))
        );
    }

    /**
     * @param FormInterface $form
     *
     * @return bool|object entity or fals
     */
    protected function getEntityByForm(FormInterface $form)
    {
        $isMapped  = $form->getConfig()->getMapped();
        $className = $form->getConfig()->getDataClass();
        $entity    = $form->getData();

        if ($isMapped && $entity instanceof $className) {
            return $entity;
        } else {
            return false;
        }
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
        $propertyPath = $form->getConfig()->getPropertyPath();
        $isMapped  = $form->getConfig()->getMapped();

        return $isMapped && $propertyPath && $propertyPath->getLength() == 1 ? (string)$propertyPath : $form->getName();
    }

    /**
     * in case if we have error in the non accessable fields - add validation error.
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
            $errorsArray[] = $error = new FormError(
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
                        'Non accessable field `%s` detected in form `%s`. Validation errors: %s',
                        $fieldName,
                        $form->getName(),
                        $errorsString
                    )
                );
            }
        }
    }

    /**
     * Get Form Error with FieldAccessGranted constraint
     *
     * @return FormError
     */
    protected function getFieldForbiddenFormError()
    {
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

        return new FormError($message, $message, [], null, $violation);
    }
}
