<?php

namespace Oro\Bundle\OrganizationBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Form\EventListener\OwnerFormSubscriber;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Class OwnerFormExtension
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OwnerFormExtension extends AbstractTypeExtension
{
    /** @var SecurityContextInterface */
    protected $securityContext;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var OwnershipMetadataProvider */
    protected $ownershipMetadataProvider;

    /** @var BusinessUnitManager */
    protected $businessUnitManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var string */
    protected $fieldName;

    /** @var string */
    protected $fieldLabel = 'oro.user.owner.label';

    /** @var bool */
    protected $isAssignGranted;

    /** @var string */
    protected $accessLevel;

    /** @var User */
    protected $currentUser;

    /** @var AclVoter */
    protected $aclVoter;

    /** @var OwnerTreeProvider */
    protected $treeProvider;

    /** @var int */
    protected $oldOwner;

    /**
     * @param SecurityContextInterface  $securityContext
     * @param ManagerRegistry           $managerRegistry
     * @param OwnershipMetadataProvider $ownershipMetadataProvider
     * @param BusinessUnitManager       $businessUnitManager
     * @param SecurityFacade            $securityFacade
     * @param AclVoter                  $aclVoter
     * @param OwnerTreeProvider         $treeProvider
     */
    public function __construct(
        SecurityContextInterface $securityContext,
        ManagerRegistry $managerRegistry,
        OwnershipMetadataProvider $ownershipMetadataProvider,
        BusinessUnitManager $businessUnitManager,
        SecurityFacade $securityFacade,
        AclVoter $aclVoter,
        OwnerTreeProvider $treeProvider
    ) {
        $this->securityContext = $securityContext;
        $this->managerRegistry = $managerRegistry;
        $this->ownershipMetadataProvider = $ownershipMetadataProvider;
        $this->businessUnitManager = $businessUnitManager;
        $this->securityFacade = $securityFacade;
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
     * @param array                $options
     * @throws \LogicException when getOwner method isn't implemented for entity with ownership type
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['ownership_disabled']) {
            return;
        }

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
        if (!$metadata || $metadata->isOrganizationOwned()) {
            return;
        }

        $this->fieldName = $metadata->getOwnerFieldName();

        $this->checkIsGranted('CREATE', 'entity:' . $dataClassName);
        $defaultOwner = null;

        if ($metadata->isUserOwned() && $this->isAssignGranted) {
            $this->addUserOwnerField($builder, $dataClassName);
            $defaultOwner = $user;
        } elseif ($metadata->isBusinessUnitOwned()) {
            $this->addBusinessUnitOwnerField($builder, $user, $dataClassName);
            if (!$this->checkIsBusinessUnitEntity($dataClassName)) {
                $defaultOwner = $this->getCurrentBusinessUnit();
            }
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, array($this, 'preSetData'));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'preSubmit'));
        $builder->addEventListener(FormEvents::POST_SUBMIT, array($this, 'postSubmit'));

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
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'ownership_disabled' => false,
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
        $form = $event->getForm();
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

        $newOwner = $entity->getOwner();
        //validate only if owner was changed or then we are on create page
        if (is_null($event->getData()->getId())
            || ($this->oldOwner && $newOwner->getId() && $this->oldOwner !== $newOwner->getId())
        ) {
            $metadata = $this->getMetadata($form->getNormData());
            if ($metadata) {
                $isCorrect = true;
                if ($metadata->isUserOwned()) {
                    $isCorrect = $this->businessUnitManager->canUserBeSetAsOwner(
                        $this->getCurrentUser(),
                        $newOwner,
                        $this->accessLevel,
                        $this->treeProvider,
                        $this->securityContext->getToken()->getOrganizationContext()
                    );
                } elseif ($metadata->isBusinessUnitOwned()) {
                    $isCorrect = in_array($newOwner->getId(), $this->getBusinessUnitIds());
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
        ) {
            $permission = 'ASSIGN';
            $this->checkIsGranted($permission, $entity);
            $owner = $entity->getOwner();
            $dataClassName = ClassUtils::getClass($entity);
            $metadata = $this->getMetadata($dataClassName);

            if ($metadata) {
                if ($form->has($this->fieldName)) {
                    $form->remove($this->fieldName);
                }
                if ($this->isAssignGranted) {
                    if ($metadata->isUserOwned()) {
                        $this->addUserOwnerField($form, $dataClassName, $permission, $owner, $entity->getId());
                    } elseif ($metadata->isBusinessUnitOwned()) {
                        $this->addBusinessUnitOwnerField($form, $this->getCurrentUser(), $dataClassName);
                    }
                }
            }
        }
    }

    /**
     * @param FormBuilderInterface|FormInterface $builder
     * @param                                    $dataClass
     * @param string                             $permission
     * @param array                              $data
     * @param int                                $entityId
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

            $options = array(
                'label'              => $this->fieldLabel,
                'required'           => true,
                'constraints'        => $isRequired ? array(new NotBlank()) : array(),
                'autocomplete_alias' => 'acl_users',
                'configs'            => [
                    'placeholder'             => 'oro.user.form.choose_user',
                    'result_template_twig'    => 'OroUserBundle:User:Autocomplete/result.html.twig',
                    'selection_template_twig' => 'OroUserBundle:User:Autocomplete/selection.html.twig',
                    'extra_config'            => 'acl_user_autocomplete',
                    'permission'              => $permission,
                    'entity_name'             => str_replace('\\', '_', $dataClass),
                    'entity_id'               => $entityId
                ]
            );

            if (null !== $data) {
                $options['data'] = $data;
            }

            $builder->add(
                $this->fieldName,
                'oro_user_acl_select',
                $options
            );
        }
    }

    /**
     * Check if current entity is BusinessUnit
     *
     * @param string $className
     * @return bool
     */
    protected function checkIsBusinessUnitEntity($className)
    {
        $businessUnitClass = $this->ownershipMetadataProvider->getBusinessUnitClass();
        if ($className != $businessUnitClass && !is_subclass_of($className, $businessUnitClass)) {
            return false;
        }

        return true;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param User                 $user
     * @param string               $className
     */
    protected function addBusinessUnitOwnerField($builder, User $user, $className)
    {
        /**
         * Owner field is required for all entities except business unit
         */
        if (!$this->checkIsBusinessUnitEntity($className)) {
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
                        'empty_value'       => $emptyValueLabel,
                        'mapped'            => true,
                        'label'             => $this->fieldLabel,
                        'business_unit_ids' => $this->getBusinessUnitIds(),
                        'configs'           => array(
                            'is_translated_option' => true,
                            'is_safe' => true,
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
     * @return int|null
     */
    protected function getOrganizationContextId()
    {
        $token = $this->securityContext->getToken();
        if ($token instanceof OrganizationContextTokenInterface) {
            return $token->getOrganizationContext()->getId();
        }

        return null;
    }

    /**
     * Check is granting user to object in given permission
     *
     * @param string        $permission
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
            $dataClassName = ClassUtils::getClass($entity);
        } else {
            $dataClassName = $entity;
        }
        $metadata = $this->ownershipMetadataProvider->getMetadata($dataClassName);

        if ($metadata->hasOwner()) {
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
            return $this->treeProvider->getTree()->getUserBusinessUnitIds(
                $this->currentUser->getId(),
                $this->getOrganizationContextId()
            );
        } elseif (AccessLevel::DEEP_LEVEL === $this->accessLevel) {
            return $this->treeProvider->getTree()->getUserSubordinateBusinessUnitIds(
                $this->currentUser->getId(),
                $this->getOrganizationContextId()
            );
        } elseif (AccessLevel::GLOBAL_LEVEL === $this->accessLevel) {
            return $this->businessUnitManager->getBusinessUnitIds($this->getOrganizationContextId());
        }

        return [];
    }
}
