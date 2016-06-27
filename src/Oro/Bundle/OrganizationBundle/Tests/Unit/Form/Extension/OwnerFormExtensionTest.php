<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Validator\Constraints\NotBlank;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Form\Extension\OwnerFormExtension;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

class OwnerFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $ownershipMetadataProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $businessUnitManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $builder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $user;

    private $organizations;

    private $businessUnits;

    private $fieldName;

    private $entityClassName;

    /**
     * @var OwnerFormExtension
     */
    private $extension;

    /**
     * @var Organization
     */
    private $organization;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
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
            $this->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider')
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
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
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
            $this->securityFacade,
            $aclVoter,
            $treeProvider,
            $this->entityOwnerAccessor
        );
    }

    public function testGetExtendedType()
    {
        $this->assertEquals('form', $this->extension->getExtendedType());
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
            'oro_user_acl_select'
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
            'oro_business_unit_select_autocomplete',
            array(
                'empty_value' => 'oro.business_unit.form.choose_business_user',
                'label' => 'oro.user.owner.label',
                'configs' => [
                    'multiple' => false,
                    'allowClear' => false,
                ],
                'required' => false,
                'autocomplete_alias' => 'business_units_owner_search_handler'
            )
        );
        $this->extension->buildForm($this->builder, array('ownership_disabled' => false));
    }

    /**
     * Testing case with business unit owner type and change owner permission isn't granted
     */
    public function testBusinessUnitOwnerBuildFormNotGranted()
    {
        $this->mockConfigs(array('is_granted' => false, 'owner_type' => OwnershipType::OWNER_TYPE_BUSINESS_UNIT));
        $this->builder->expects($this->once())->method('add')->with(
            $this->fieldName,
            'entity',
            array(
                'class' => 'OroOrganizationBundle:BusinessUnit',
                'property' => 'name',
                'choices' => $this->businessUnits,
                'mapped' => true,
                'required' => true,
                'constraints' => array(new NotBlank()),
                'label' => 'oro.user.owner.label',
                'translatable_options' => false
            )
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

    protected function mockConfigs(array $values)
    {
        $this->securityFacade->expects($this->any())->method('getOrganization')
            ->will($this->returnValue($this->organization));
        $this->securityFacade->expects($this->any())->method('getOrganizationId')
            ->will($this->returnValue($this->organization->getId()));
        $this->securityFacade->expects($this->any())->method('getLoggedUser')
            ->will($this->returnValue($this->user));

        $this->securityFacade->expects($this->any())->method('isGranted')
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
            $this->securityFacade,
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
