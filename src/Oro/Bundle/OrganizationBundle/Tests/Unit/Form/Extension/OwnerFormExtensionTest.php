<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager;
use Oro\Bundle\OrganizationBundle\Form\EventListener\OwnerFormSubscriber;
use Oro\Bundle\OrganizationBundle\Form\Extension\OwnerFormExtension;
use Oro\Bundle\OrganizationBundle\Form\Type\BusinessUnitSelectAutocomplete;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\UserAclSelectType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class OwnerFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|OwnershipMetadataProviderInterface */
    private $ownershipMetadataProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|BusinessUnitManager */
    private $businessUnitManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface */
    private $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormBuilder */
    private $builder;

    /** @var \PHPUnit\Framework\MockObject\MockObject|User */
    private $user;

    /** @var array */
    private $organizations;

    /** @var ArrayCollection */
    private $businessUnits;

    /** @var string */
    private $fieldName;

    /** @var string */
    private $fieldLabel;

    /** @var string */
    private $entityClassName;

    /** @var OwnerFormExtension */
    private $extension;

    /** @var Organization */
    private $organization;

    /** @var EntityOwnerAccessor|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityOwnerAccessor;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->willReturn(true);

        $this->ownershipMetadataProvider =
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface')
                ->disableOriginalConstructor()
                ->getMock();
        $this->businessUnitManager =
            $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Manager\BusinessUnitManager')
                ->disableOriginalConstructor()
                ->getMock();
        $this->businessUnitManager->expects($this->any())
            ->method('getBusinessUnitIds')
            ->will(
                $this->returnValue(
                    array(1, 2)
                )
            );
        $organization = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->getMock();
        $this->organizations = array($organization);
        $businessUnit = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
            ->disableOriginalConstructor()
            ->getMock();
        $businessUnit->expects($this->any())->method('getOrganization')->will($this->returnValue($organization));
        $this->businessUnits = new ArrayCollection(array($businessUnit));
        $this->user = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $this->user->expects($this->any())->method('getId')->will($this->returnValue(1));
        $this->user->expects($this->any())->method('getBusinessUnits')->will($this->returnValue($this->businessUnits));
        $this->organization = new Organization();
        $this->organization->setId(1);
        $this->entityClassName = get_class($this->user);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $config = $this->getMockBuilder('Symfony\Component\Form\FormConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->any())->method('getCompound')->will($this->returnValue(true));
        $config->expects($this->any())->method('getDataClass')->will($this->returnValue($this->entityClassName));
        $this->builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->builder->expects($this->any())->method('getFormConfig')->will($this->returnValue($config));
        $this->builder->expects($this->any())->method('getOption')->with('required')->will($this->returnValue(true));
        $this->fieldName = 'owner';
        $this->fieldLabel = 'oro.user.owner.label';

        $aclVoter = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter')
            ->disableOriginalConstructor()
            ->getMock();

        $treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityOwnerAccessor = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\EntityOwnerAccessor')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityOwnerAccessor->expects($this->any())
            ->method('getOwner')
            ->willReturnCallback(
                function ($entity) {
                    return $entity->getOwner();
                }
            );

        $this->extension = new OwnerFormExtension(
            $this->doctrineHelper,
            $this->ownershipMetadataProvider,
            $this->businessUnitManager,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $aclVoter,
            $treeProvider,
            $this->entityOwnerAccessor
        );
    }

    public function testNotCompoundForm()
    {
        $config = $this->getMockBuilder('Symfony\Component\Form\FormConfigInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->any())->method('getCompound')->will($this->returnValue(false));

        $this->builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->builder->expects($this->any())->method('getFormConfig')->will($this->returnValue($config));
        $this->builder->expects($this->never())
            ->method('add');

        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->extension->buildForm($this->builder, array('ownership_disabled' => false));
    }

    public function testAnonymousUser()
    {
        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue('anon.'));

        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');
        $this->builder->expects($this->never())
            ->method('add');

        $this->extension->buildForm($this->builder, array('ownership_disabled' => false));
    }

    /**
     * Testing case with user owner type and change owner permission granted
     */
    public function testUserOwnerBuildFormGranted()
    {
        $this->mockConfigs(array('is_granted' => true, 'owner_type' => OwnershipType::OWNER_TYPE_USER));
        $this->builder->expects($this->once())->method('add')->with(
            $this->fieldName,
            UserAclSelectType::class
        );
        $this->extension->buildForm($this->builder, array('ownership_disabled' => false));
    }

    /**
     * Testing case with user owner type and change owner permission isn't granted
     */
    public function testUserOwnerBuildFormNotGranted()
    {
        $this->mockConfigs(array('is_granted' => false, 'owner_type' => OwnershipType::OWNER_TYPE_USER));
        $this->builder->expects($this->never())->method('add');
        $this->extension->buildForm($this->builder, array('ownership_disabled' => false));
    }

    /**
     * Testing case with business unit owner type and change owner permission granted
     */
    public function testBusinessUnitOwnerBuildFormGranted()
    {
        $this->mockConfigs(array('is_granted' => true, 'owner_type' => OwnershipType::OWNER_TYPE_BUSINESS_UNIT));

        $this->builder->expects($this->once())->method('add')->with(
            $this->fieldName,
            BusinessUnitSelectAutocomplete::class,
            array(
                'placeholder' => 'oro.business_unit.form.choose_business_user',
                'label' => 'oro.user.owner.label',
                'configs' => [
                    'multiple' => false,
                    'allowClear' => false,
                    'autocomplete_alias' => 'business_units_owner_search_handler',
                    'component' => 'tree-autocomplete'
                ],
                'required' => false,
                'autocomplete_alias' => 'business_units_owner_search_handler'
            )
        );
        $this->extension->buildForm($this->builder, array('ownership_disabled' => false));
    }

    /**
     * Testing case with business unit owner type and change owner permission granted, but view business unit not.
     */
    public function testBusinessUnitOwnerBuildFormAssignGrantedViewBusinessUnitNotGranted()
    {
        $this->tokenAccessor->expects($this->any())->method('getOrganization')
            ->will($this->returnValue($this->organization));
        $this->tokenAccessor->expects($this->any())->method('getOrganizationId')
            ->will($this->returnValue($this->organization->getId()));
        $this->tokenAccessor->expects($this->any())->method('getUser')
            ->will($this->returnValue($this->user));

        $this->authorizationChecker->expects($this->any())->method('isGranted')
            ->withConsecutive(['CREATE', 'entity:' . $this->entityClassName], ['VIEW', 'entity:' . BusinessUnit::class])
            ->willReturnOnConsecutiveCalls(true, false);
        $metadata = new OwnershipMetadata(OwnershipType::OWNER_TYPE_BUSINESS_UNIT, 'owner', 'owner_id');
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getMetadata')
            ->with($this->entityClassName)
            ->will($this->returnValue($metadata));

        /** @var AclVoter|\PHPUnit\Framework\MockObject\MockObject $aclVoter */
        $aclVoter = $this->getMockBuilder(AclVoter::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var OwnerTreeProvider|\PHPUnit\Framework\MockObject\MockObject $treeProvider */
        $treeProvider = $this->getMockBuilder(OwnerTreeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ClassMetadataInfo|\PHPUnit\Framework\MockObject\MockObject $classMetadata */
        $classMetadata = $this->getMockBuilder(ClassMetadataInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $classMetadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('name'));

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
           ->method('getClassMetadata')
           ->will($this->returnValue($classMetadata));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->extension = new OwnerFormExtension(
            $this->doctrineHelper,
            $this->ownershipMetadataProvider,
            $this->businessUnitManager,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $aclVoter,
            $treeProvider,
            $this->entityOwnerAccessor
        );

        $this->builder->expects($this->any())
            ->method('get')
            ->with($this->fieldName)
            ->willReturn($this->builder);

        $this->builder->expects($this->once())->method('add')->with(
            $this->fieldName,
            HiddenType::class
        );

        $this->extension->buildForm($this->builder, ['ownership_disabled' => false]);
    }

    /**
     * Testing case with business unit owner type and change owner permission isn't granted
     */
    public function testBusinessUnitOwnerBuildFormNotGranted()
    {
        $this->mockConfigs(array('is_granted' => false, 'owner_type' => OwnershipType::OWNER_TYPE_BUSINESS_UNIT));
        $this->builder->expects($this->once())->method('add')->with(
            $this->fieldName,
            EntityType::class,
            [
                'class' => BusinessUnit::class,
                'choice_label' => 'name',
                'mapped' => true,
                'required' => true,
                'constraints' => array(new NotBlank()),
                'label' => 'oro.user.owner.label',
                'translatable_options' => false,
                'query_builder' => function () {
                },
            ]
        );
        $this->extension->buildForm($this->builder, array('ownership_disabled' => false));
    }

    /**
     * Testing case with organization owner type and change owner permission granted
     */
    public function testOrganizationOwnerBuildFormGranted()
    {
        $this->mockConfigs(array('is_granted' => true, 'owner_type' => OwnershipType::OWNER_TYPE_ORGANIZATION));
        $this->builder->expects($this->never())->method('add');
        $this->extension->buildForm($this->builder, array('ownership_disabled' => false));
    }

    /**
     * Testing case with organization owner type and change owner permission isn't granted
     */
    public function testOrganizationOwnerBuildFormNotGranted()
    {
        $this->mockConfigs(array('is_granted' => false, 'owner_type' => OwnershipType::OWNER_TYPE_ORGANIZATION));
        $this->builder->expects($this->never())->method('add');
        $this->extension->buildForm($this->builder, array('ownership_disabled' => false));
    }

    public function testEventListener()
    {
        $this->mockConfigs(array('is_granted' => false, 'owner_type' => OwnershipType::OWNER_TYPE_ORGANIZATION));
        $this->builder->expects($this->never())
            ->method('addEventSubscriber');
        $this->extension->buildForm($this->builder, array('ownership_disabled' => false));
    }

    /**
     * Test case, when business unit not assigned and not available for user
     */
    public function testDefaultOwnerUnavailableBusinessUnit()
    {
        $this->mockConfigs(['is_granted' => true, 'owner_type' => OwnershipType::OWNER_TYPE_BUSINESS_UNIT]);

        $businessUnit = $this->createMock(BusinessUnit::class);
        $this->user->expects($this->any())
            ->method('getOwner')
            ->will($this->returnValue($businessUnit));

        $isAssignGranted = true;
        $this->builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with(
                new OwnerFormSubscriber(
                    $this->doctrineHelper,
                    $this->fieldName,
                    $this->fieldLabel,
                    $isAssignGranted,
                    null
                )
            );

        $this->extension->buildForm($this->builder, ['ownership_disabled' => false]);
    }

    protected function mockConfigs(array $values)
    {
        $this->tokenAccessor->expects($this->any())->method('getOrganization')
            ->will($this->returnValue($this->organization));
        $this->tokenAccessor->expects($this->any())->method('getOrganizationId')
            ->will($this->returnValue($this->organization->getId()));
        $this->tokenAccessor->expects($this->any())->method('getUser')
            ->will($this->returnValue($this->user));

        $this->authorizationChecker->expects($this->any())->method('isGranted')
            ->will($this->returnValue($values['is_granted']));
        $metadata = OwnershipType::OWNER_TYPE_NONE === $values['owner_type']
            ? new OwnershipMetadata($values['owner_type'])
            : new OwnershipMetadata($values['owner_type'], 'owner', 'owner_id');
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getMetadata')
            ->with($this->entityClassName)
            ->will($this->returnValue($metadata));

        $aclVoter = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Voter\AclVoter')
            ->disableOriginalConstructor()
            ->getMock();

        $treeProvider = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\OwnerTreeProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new OwnerFormExtension(
            $this->doctrineHelper,
            $this->ownershipMetadataProvider,
            $this->businessUnitManager,
            $this->authorizationChecker,
            $this->tokenAccessor,
            $aclVoter,
            $treeProvider,
            $this->entityOwnerAccessor
        );
    }

    public function testPreSubmit()
    {
        $this->mockConfigs(
            array(
                'is_granted' => true,
                'owner_type' => OwnershipType::OWNER_TYPE_USER
            )
        );
        $businessUnit = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
            ->disableOriginalConstructor()
            ->getMock();
        $businessUnit->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));
        $this->user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $newUser = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $newUser->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(2));
        $this->user->expects($this->any())
            ->method('getOwner')
            ->will($this->returnValue($newUser));
        $ownerForm = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->any())
            ->method('get')
            ->will($this->returnValue($ownerForm));
        $ownerForm->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($this->user));
        $form->expects($this->any())
            ->method('has')
            ->will($this->returnValue(true));
        $form->expects($this->any())
            ->method('getParent')
            ->will($this->returnValue(false));
        $form->expects($this->once())
            ->method('getNormData')
            ->will($this->returnValue($this->entityClassName));
        $this->businessUnitManager->expects($this->once())
            ->method('canUserBeSetAsOwner')
            ->will($this->returnValue(false));
        $event = new FormEvent($form, $this->user);

        $this->extension->preSubmit($event);

        $ownerForm->expects($this->once())
            ->method('addError')
            ->with(new FormError('You have no permission to set this owner'));

        $this->extension->postSubmit($event);
    }

    public function testPreSetData()
    {
        $this->mockConfigs(array('is_granted' => true, 'owner_type' => OwnershipType::OWNER_TYPE_USER));
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($this->builder));
        $form->expects($this->any())
            ->method('getParent')
            ->will($this->returnValue(false));
        $form->expects($this->any())
            ->method('has')
            ->will($this->returnValue(true));
        $this->user->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1));
        $businessUnit = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit')
            ->disableOriginalConstructor()
            ->getMock();
        $this->user->expects($this->any())
            ->method('getOwner')
            ->will($this->returnValue($businessUnit));
        $form->expects($this->once())
            ->method('remove');
        $event = new FormEvent($form, $this->user);
        $this->extension->preSetData($event);
    }
}
