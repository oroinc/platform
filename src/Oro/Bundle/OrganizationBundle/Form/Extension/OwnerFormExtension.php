<?php

namespace Oro\Bundle\OrganizationBundle\Form\Extension;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\FormBundle\Form\Extension\Traits\FormExtendedTypeTrait;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\OrganizationBundle\Form\EventListener\OwnerFormSubscriber;
use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitSelectAutocomplete;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserAclSelectType;
use Psr\Container\ContainerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Dependently on entity metadata adds user or business unit owned field to a form and a set of events to handle them
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class OwnerFormExtension extends AbstractTypeExtension implements ServiceSubscriberInterface
{
    use FormExtendedTypeTrait;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var ContainerInterface */
    protected $container;

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

    /** @var int */
    protected $oldOwner;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        TokenAccessorInterface $tokenAccessor,
        AuthorizationCheckerInterface $authorizationChecker,
        ContainerInterface $container
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->tokenAccessor = $tokenAccessor;
        $this->authorizationChecker = $authorizationChecker;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'security.acl.voter.basic_permissions' => AclVoterInterface::class,
            'oro_security.owner.ownership_metadata_provider' => OwnershipMetadataProviderInterface::class,
            'oro_security.owner.entity_owner_accessor' => EntityOwnerAccessor::class,
            'oro_security.ownership_tree_provider' => OwnerTreeProvider::class,
            'oro_organization.business_unit_manager' => BusinessUnitManager::class
        ];
    }

    /**
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
                $defaultOwner = $this->getCurrentBusinessUnit(
                    $this->getOrganization()
                );
            }
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'postSubmit']);

        /**
         * Adding subscriber to hide owner field for update pages if assign permission is not granted
         * and set default owner value
         */
        $builder->addEventSubscriber(
            new OwnerFormSubscriber(
                $this->doctrineHelper,
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('ownership_disabled', false);
    }

    /**
     * Save old owner id of record
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
        if (!is_object($this->getEntityOwnerAccessor()->getOwner($event->getData()))) {
            return;
        }

        $newOwner = $this->getEntityOwnerAccessor()->getOwner($entity);
        //validate only if owner was changed or then we are on create page
        if (is_null($event->getData()->getId())
            || ($this->oldOwner && $newOwner->getId() && $this->oldOwner !== $newOwner->getId())
        ) {
            $metadata = $this->getMetadata($form->getNormData());
            if ($metadata instanceof OwnershipMetadata) {
                $isCorrect = true;
                if ($metadata->isUserOwned()) {
                    $isCorrect = $this->getBusinessUnitManager()->canUserBeSetAsOwner(
                        $this->getCurrentUser(),
                        $newOwner,
                        $this->accessLevel,
                        $this->getOwnerTreeProvider(),
                        $this->getOrganization()
                    );
                } elseif ($metadata->isBusinessUnitOwned()) {
                    $isCorrect = $this->isBusinessUnitAvailableForCurrentUser($newOwner);
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
            $owner         = $this->getEntityOwnerAccessor()->getOwner($entity);
            $dataClassName = ClassUtils::getClass($entity);
            $metadata      = $this->getMetadata($dataClassName);

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
            $isRequired  = $formBuilder->getOption('required');

            $options = [
                'label'              => ConfigHelper::getTranslationKey(
                    'entity',
                    'label',
                    $dataClass,
                    $this->fieldName
                ),
                'required'           => true,
                'constraints'        => $isRequired ? [new NotBlank()] : [],
                'autocomplete_alias' => 'acl_users',
                'configs'            => [
                    'placeholder'             => 'oro.user.form.choose_user',
                    'result_template_twig'    => '@OroUser/User/Autocomplete/result.html.twig',
                    'selection_template_twig' => '@OroUser/User/Autocomplete/selection.html.twig',
                    'component'               => 'acl-user-autocomplete',
                    'permission'              => $permission,
                    'entity_name'             => str_replace('\\', '_', $dataClass),
                    'entity_id'               => $entityId
                ]
            ];

            if (null !== $data) {
                $options['data'] = $data;
            }

            $builder->add(
                $this->fieldName,
                UserAclSelectType::class,
                $options
            );
        }
    }

    /**
     * Check if current entity is BusinessUnit
     *
     * @param string $className
     *
     * @return bool
     */
    protected function checkIsBusinessUnitEntity($className)
    {
        $businessUnitClass = $this->getOwnershipMetadataProvider()->getBusinessUnitClass();
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
            $validation      = [
                'constraints' => [new NotBlank()],
                'required'    => true,
            ];
            $emptyValueLabel = 'oro.business_unit.form.choose_business_user';
        } else {
            $validation       = [
                'required' => false
            ];
            $emptyValueLabel  = 'oro.business_unit.form.none_business_user';
            $this->fieldLabel = 'oro.organization.businessunit.parent.label';
        }

        if ($this->isAssignGranted) {
            /**
             * If assign permission is granted, and user able to see business units, showing all available.
             * If not able to see, render default in hidden field.
             */
            if ($this->authorizationChecker->isGranted('VIEW', 'entity:' . BusinessUnit::class)) {
                $builder->add(
                    $this->fieldName,
                    BusinessUnitSelectAutocomplete::class,
                    [
                        'required' => false,
                        'label' => $this->fieldLabel,
                        'autocomplete_alias' => 'business_units_owner_search_handler',
                        'placeholder' => $emptyValueLabel,
                        'configs' => [
                            'multiple' => false,
                            'allowClear'  => false,
                            'autocomplete_alias' => 'business_units_owner_search_handler',
                            'component'   => 'tree-autocomplete',
                        ]
                    ]
                );
            } else {
                // Add hidden input with default owner only during creation process,
                // current user not able to modify this
                if ($builder instanceof FormBuilder) {
                    $transformer  = new EntityToIdTransformer(
                        $this->doctrineHelper->getEntityManager(BusinessUnit::class),
                        BusinessUnit::class
                    );
                    $builder->add(
                        $this->fieldName,
                        HiddenType::class
                    );
                    $builder->get($this->fieldName)->addModelTransformer($transformer);
                }
            }
        } else {
            $businessUnits = $user->getBusinessUnits();
            if (count($businessUnits)) {
                $builder->add(
                    $this->fieldName,
                    EntityType::class,
                    array_merge(
                        [
                            'class'                => BusinessUnit::class,
                            'choice_label'         => 'name',
                            'query_builder'        => function (BusinessUnitRepository $repository) use ($user) {
                                $qb = $repository->createQueryBuilder('bu');
                                $qb->andWhere($qb->expr()->isMemberOf(':user', 'bu.users'));
                                $qb->setParameter('user', $user);

                                return $qb;
                            },
                            'mapped'               => true,
                            'label'                => $this->fieldLabel,
                            'translatable_options' => false
                        ],
                        $validation
                    )
                );
            }
        }
    }

    /**
     * @param Organization $organization
     *
     * @return null|BusinessUnit
     */
    protected function getCurrentBusinessUnit(Organization $organization)
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return null;
        }

        if (!$this->isAssignGranted) {
            return $user->getBusinessUnits()
                ->filter(function (BusinessUnit $businessUnit) use ($organization) {
                    return $businessUnit->getOrganization()->getId() === $organization->getId();
                })
                ->first();
        }

        if ($businessUnit = $this->getBusinessUnitManager()->getCurrentBusinessUnit($user, $organization)) {
            return $businessUnit;
        }

        $owner = $user->getOwner();
        if ($owner instanceof BusinessUnit && $this->isBusinessUnitAvailableForCurrentUser($owner)) {
            return $owner;
        }

        return null;
    }

    /**
     * @return User|null
     */
    protected function getCurrentUser()
    {
        if (null === $this->currentUser) {
            $user = $this->tokenAccessor->getUser();
            if ($user instanceof User) {
                $this->currentUser = $user;
            }
        }

        return $this->currentUser;
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
        $this->getAclVoter()->addOneShotIsGrantedObserver($observer);
        $this->isAssignGranted = $this->authorizationChecker->isGranted($permission, $object);
        $this->accessLevel = $observer->getAccessLevel();
    }

    /**
     * Get metadata for entity
     *
     * @param object|string $entity
     *
     * @return bool|OwnershipMetadataInterface
     * @throws \LogicException
     */
    protected function getMetadata($entity)
    {
        if (is_object($entity)) {
            $entity = ClassUtils::getClass($entity);
        }
        if (!$this->doctrineHelper->isManageableEntityClass($entity)) {
            return false;
        }

        $metadata = $this->getOwnershipMetadataProvider()->getMetadata($entity);

        return $metadata->hasOwner()
            ? $metadata
            : false;
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
            return $this->getBusinessUnitManager()->getBusinessUnitIds($this->getOrganizationId());
        } elseif (AccessLevel::LOCAL_LEVEL == $this->accessLevel) {
            return $this->getOwnerTreeProvider()->getTree()->getUserBusinessUnitIds(
                $this->currentUser->getId(),
                $this->getOrganizationId()
            );
        } elseif (AccessLevel::DEEP_LEVEL === $this->accessLevel) {
            return $this->getOwnerTreeProvider()->getTree()->getUserSubordinateBusinessUnitIds(
                $this->currentUser->getId(),
                $this->getOrganizationId()
            );
        } elseif (AccessLevel::GLOBAL_LEVEL === $this->accessLevel) {
            return $this->getBusinessUnitManager()->getBusinessUnitIds($this->getOrganizationId());
        }

        return [];
    }

    /**
     * @param BusinessUnit $businessUnit
     * @return bool
     */
    protected function isBusinessUnitAvailableForCurrentUser(BusinessUnit $businessUnit)
    {
        return in_array($businessUnit->getId(), $this->getBusinessUnitIds());
    }

    /**
     * Gets organization from the current security token
     *
     * @return bool|Organization
     */
    protected function getOrganization()
    {
        return $this->tokenAccessor->getOrganization();
    }

    /**
     * @return int|null
     */
    protected function getOrganizationId()
    {
        return $this->getOrganization()->getId();
    }

    protected function getAclVoter(): AclVoterInterface
    {
        return $this->container->get('security.acl.voter.basic_permissions');
    }

    protected function getOwnershipMetadataProvider(): OwnershipMetadataProviderInterface
    {
        return $this->container->get('oro_security.owner.ownership_metadata_provider');
    }

    protected function getEntityOwnerAccessor(): EntityOwnerAccessor
    {
        return $this->container->get('oro_security.owner.entity_owner_accessor');
    }

    protected function getOwnerTreeProvider(): OwnerTreeProvider
    {
        return $this->container->get('oro_security.ownership_tree_provider');
    }

    protected function getBusinessUnitManager(): BusinessUnitManager
    {
        return $this->container->get('oro_organization.business_unit_manager');
    }
}
