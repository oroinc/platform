<?php

namespace Oro\Bundle\OrganizationBundle\Form\Extension;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\OrganizationBundle\Event\RecordOwnerDataListener;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Form\EventListener\OwnerFormSubscriber;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

/**
 * Class OwnerFormExtension
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OwnerFormExtension extends AbstractTypeExtension
{
    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var OwnershipMetadataProvider
     */
    protected $ownershipMetadataProvider;

    /**
     * @var BusinessUnitManager
     */
    protected $businessUnitManager;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $fieldLabel = 'Owner';

    /**
     * @var bool
     */
    protected $isAssignGranted;

    /**
     * @var string
     */
    protected $accessLevel;

    /**
     * @var User
     */
    protected $currentUser;

    /**
     * @var AclVoter
     */
    protected $aclVoter;

    /**
     * @var OwnerTreeProvider
     */
    protected $treeProvider;

    /**
     * @var int
     */
    protected $oldOwner;

    /**
     * @param SecurityContextInterface $securityContext
     * @param ManagerRegistry $managerRegistry
     * @param OwnershipMetadataProvider $ownershipMetadataProvider
     * @param BusinessUnitManager $businessUnitManager
     * @param SecurityFacade $securityFacade
     * @param TranslatorInterface $translator
     * @param AclVoter $aclVoter
     * @param OwnerTreeProvider $treeProvider
     */
    public function __construct(
        SecurityContextInterface $securityContext,
        ManagerRegistry $managerRegistry,
        OwnershipMetadataProvider $ownershipMetadataProvider,
        BusinessUnitManager $businessUnitManager,
        SecurityFacade $securityFacade,
        TranslatorInterface $translator,
        AclVoter $aclVoter,
        OwnerTreeProvider $treeProvider
    ) {
        $this->securityContext = $securityContext;
        $this->managerRegistry = $managerRegistry;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->businessUnitManager = $businessUnitManager;
        $this->securityFacade = $securityFacade;
        $this->translator = $translator;
        $this->fieldName = RecordOwnerDataListener::OWNER_FIELD_NAME;
        $this->aclVoter = $aclVoter;
        $this->treeProvider = $treeProvider;
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return 'form';
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     * @throws \LogicException when getOwner method isn't implemented for entity with ownership type
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $formConfig = $builder->getFormConfig();
        if (!$formConfig->getCompound()) {
            return;
        }

        $dataClassName = $formConfig->getDataClass();
        if (!$dataClassName) {
            return;
        }

        $user = $this->getCurrentUser();
        if (!$user) {
            return;
        }

        $metadata = $this->getMetadata($dataClassName);
        if (!$metadata) {
            return;
        }

        $this->checkIsGranted('CREATE', 'entity:' . $dataClassName);
        $defaultOwner = null;

        if ($metadata->isUserOwned() && $this->isAssignGranted) {
            $this->addUserOwnerField($builder, $dataClassName);
            $defaultOwner = $user;
        } elseif ($metadata->isBusinessUnitOwned()) {
            $this->addBusinessUnitOwnerField($builder, $user, $dataClassName);
            $defaultOwner = $this->getCurrentBusinessUnit();
        } elseif ($metadata->isOrganizationOwned()) {
            $this->addOrganizationOwnerField($builder, $user);
            $defaultOwner = $this->getCurrentOrganization();
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            array($this, 'preSetData')
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            array($this, 'preSubmit')
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            array($this, 'postSubmit')
        );

        /**
         * Adding subscriber to hide owner field for update pages if assign permission is not granted
         * and set default owner value
         */
        $builder->addEventSubscriber(
            new OwnerFormSubscriber(
                $this->managerRegistry,
                $this->fieldName,
                $this->fieldLabel,
                $this->isAssignGranted,
                $defaultOwner
            )
        );
    }

    /**
     * Save old owner id of record
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        if ($event->getForm()->has($this->fieldName)
            && is_object($event->getForm()->get($this->fieldName)->getData())
        ) {
            $this->oldOwner = $event->getForm()->get($this->fieldName)->getData()->getId();
        } else {
            $this->oldOwner = null;
        }
    }

    /**
     * Validate owner
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form  = $event->getForm();
        if ($form->getParent() || !$form->has($this->fieldName)) {
            return;
        }

        $entity = $event->getData();
        // Check if we have owner in data.
        // In case Business unit entity, owner(parent) is not required.
        // For other entities, form without owner will not be valid because owner is required.
        if (!is_object($event->getData()->getOwner())) {
            return;
        }

        $newOwnerId = $entity->getOwner()->getId();
        //validate only if owner was changed or then we are on create page
        if (is_null($event->getData()->getId())
            || ($this->oldOwner && $newOwnerId && $this->oldOwner !== $newOwnerId)
        ) {
            $metadata = $this->getMetadata($form->getNormData());
            if ($metadata) {
                $isCorrect = true;
                if ($metadata->isUserOwned()) {
                    $isCorrect = $this->businessUnitManager->canUserBeSetAsOwner(
                        $this->getCurrentUser(),
                        $newOwnerId,
                        $this->accessLevel,
                        $this->treeProvider
                    );
                } elseif ($metadata->isBusinessUnitOwned()) {
                    $isCorrect = in_array($newOwnerId, $this->getBusinessUnitIds());
                }

                if (!$isCorrect) {
                    $form->get($this->fieldName)->addError(
                        new FormError(
                            'You have no permission to set this owner'
                        )
                    );
                }
            }
        }
    }

    /**
     * Process form after data is set and remove/disable owner field depending on permissions
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        if ($form->getParent()) {
            return;
        }
        $entity = $event->getData();

        if (is_object($entity)
            && $entity->getId()
            && $form->has($this->fieldName)
        ) {
            $permission = 'ASSIGN';
            $this->checkIsGranted($permission, $entity);
            $owner = $entity->getOwner();
            $dataClassName = get_class($entity);
            $metadata = $this->getMetadata($dataClassName);

            if ($metadata) {
                if ($this->isAssignGranted) {
                    if ($metadata->isUserOwned()) {
                        $form->remove($this->fieldName);
                        $this->addUserOwnerField($form, $dataClassName, $permission, $owner, $entity->getId());
                    } elseif ($metadata->isBusinessUnitOwned()) {
                        $form->remove($this->fieldName);
                        $this->addBusinessUnitOwnerField($form, $this->getCurrentUser(), $dataClassName);
                    }
                } else {
                    $form->remove($this->fieldName);
                }
            }
        }
    }

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param $dataClass
     * @param string $permission
     * @param array $data
     * @param int $entityId
     */
    protected function addUserOwnerField($builder, $dataClass, $permission = "CREATE", $data = null, $entityId = 0)
    {
        /**
         * Showing user owner box for entities with owner type USER if assign permission is
         * granted.
         */
        if ($this->isAssignGranted || $permission == 'ASSIGN') {
            $formBuilder = $builder instanceof FormInterface ? $builder->getConfig() : $builder;
            $isRequired = $formBuilder->getOption('required');

            $builder->add(
                $this->fieldName,
                'oro_user_acl_select',
                array(
                    'required' => true,
                    'constraints' => $isRequired ? array(new NotBlank()) : array(),
                    'autocomplete_alias' => 'acl_users',
                    'data' => $data,
                    'configs' => [
                        'width' => '400px',
                        'placeholder' => 'oro.user.form.choose_user',
                        'result_template_twig' => 'OroUserBundle:User:Autocomplete/result.html.twig',
                        'selection_template_twig' => 'OroUserBundle:User:Autocomplete/selection.html.twig',
                        'extra_config' => 'acl_user_autocomplete',
                        'permission' => $permission,
                        'entity_name' => str_replace('\\', '_', $dataClass),
                        'entity_id' => $entityId
                    ]
                )
            );
        }
    }

    /**
     * @param FormBuilderInterface $builder
     * @param User $user
     * @param string $className
     */
    protected function addBusinessUnitOwnerField($builder, User $user, $className)
    {
        /**
         * Owner field is required for all entities except business unit
         */
        $businessUnitClass = 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';
        if ($className != $businessUnitClass && !is_subclass_of($className, $businessUnitClass)) {
            $validation = array(
                'constraints' => array(new NotBlank()),
                'required' => true,
            );
            $emptyValueLabel = 'oro.business_unit.form.choose_business_user';
        } else {
            $validation = array(
                'required' => false
            );
            $emptyValueLabel = 'oro.business_unit.form.none_business_user';
            $this->fieldLabel = 'oro.organization.businessunit.parent.label';
        }

        if ($this->isAssignGranted) {
            /**
             * If assign permission is granted, showing all available business units
             */
            $builder->add(
                $this->fieldName,
                'oro_business_unit_tree_select',
                array_merge(
                    array(
                        'empty_value' => $this->translator->trans($emptyValueLabel),
                        'mapped' => true,
                        'label' => $this->fieldLabel,
                        'business_unit_ids' => $this->getBusinessUnitIds(),
                        'configs'     => array(
                            'is_translated_option' => true,
                            'is_safe'              => true,
                        )
                    ),
                    $validation
                )
            );
        } else {
            $businessUnits = $user->getBusinessUnits();
            if (count($businessUnits)) {
                $builder->add(
                    $this->fieldName,
                    'entity',
                    array_merge(
                        array(
                            'class' => 'OroOrganizationBundle:BusinessUnit',
                            'property' => 'name',
                            'choices' => $businessUnits,
                            'mapped' => true,
                            'label' => $this->fieldLabel,
                        ),
                        $validation
                    )
                );
            }
        }
    }

    /**
     * @return null|BusinessUnit
     */
    protected function getCurrentBusinessUnit()
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return null;
        }

        $businessUnits = $user->getBusinessUnits();
        if (!$this->isAssignGranted) {
            return $businessUnits->first();
        }

        // if assign is granted then only allowed business units can be used
        $allowedBusinessUnits = $this->businessUnitManager->getBusinessUnitIds();

        /** @var BusinessUnit $businessUnit */
        foreach ($businessUnits as $businessUnit) {
            if (in_array($businessUnit->getId(), $allowedBusinessUnits)) {
                return $businessUnit;
            }
        }

        return null;
    }

    /**
     * @return null|User
     */
    protected function getCurrentUser()
    {
        if (null === $this->currentUser) {
            $token = $this->securityContext->getToken();
            if (!$token) {
                $this->currentUser = false;
                return false;
            }

            /** @var User $user */
            $user = $token->getUser();
            if (!$user || is_string($user)) {
                $this->currentUser = false;
                return false;
            }

            $this->currentUser = $user;
        }

        return $this->currentUser;
    }

    /**
     * @return bool|Organization
     */
    protected function getCurrentOrganization()
    {
        $businessUnit = $this->getCurrentBusinessUnit();
        if (!$businessUnit) {
            return true;
        }

        return $businessUnit->getOrganization();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param User $user
     */
    protected function addOrganizationOwnerField(FormBuilderInterface $builder, User $user)
    {
        $fieldOptions = array(
            'class' => 'OroOrganizationBundle:Organization',
            'property' => 'name',
            'mapped' => true,
            'required' => true,
            'constraints' => array(new NotBlank())
        );
        if (!$this->isAssignGranted) {
            $organizations = array();
            $bu = $user->getBusinessUnits();
            /** @var $businessUnit BusinessUnit */
            foreach ($bu as $businessUnit) {
                $organizations[] = $businessUnit->getOrganization();
            }
            $fieldOptions['choices'] = $organizations;
        }
        $builder->add($this->fieldName, 'entity', $fieldOptions);
    }

    /**
     * Check is granting user to object in given permission
     *
     * @param string $permission
     * @param object|string $object
     */
    protected function checkIsGranted($permission, $object)
    {
        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        $this->isAssignGranted = $this->securityFacade->isGranted($permission, $object);
        $this->accessLevel = $observer->getAccessLevel();
    }

    /**
     * Get metadata for entity
     *
     * @param object|string $entity
     * @return bool|OwnershipMetadata
     * @throws \LogicException
     */
    protected function getMetadata($entity)
    {
        if (is_object($entity)) {
            $dataClassName = get_class($entity);
        } else {
            $dataClassName = $entity;
        }
        $metadata = $this->ownershipMetadataProvider->getMetadata($dataClassName);

        if ($metadata->hasOwner()) {
            if (!method_exists($dataClassName, 'getOwner')) {
                throw new \LogicException(
                    sprintf('Method getOwner must be implemented for %s entity.', $dataClassName)
                );
            }

            return $metadata;
        } else {
            return false;
        }
    }

    /**
     * Get business units ids for current user for current access level
     *
     * @return array
     *  value -> business unit id
     */
    protected function getBusinessUnitIds()
    {
        if (AccessLevel::SYSTEM_LEVEL == $this->accessLevel) {
            return $this->businessUnitManager->getBusinessUnitIds();
        } elseif (AccessLevel::LOCAL_LEVEL == $this->accessLevel) {
            return $this->treeProvider->getTree()->getUserBusinessUnitIds($this->currentUser->getId());
        } elseif (AccessLevel::DEEP_LEVEL === $this->accessLevel) {
            return $this->treeProvider->getTree()->getUserSubordinateBusinessUnitIds($this->currentUser->getId());
        } elseif (AccessLevel::GLOBAL_LEVEL === $this->accessLevel) {
            return $this->treeProvider->getTree()->getBusinessUnitsIdByUserOrganizations($this->currentUser->getId());
        }
    }
}
