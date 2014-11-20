<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\UserSubscriber;
use Oro\Bundle\UserBundle\Form\Type\UserType;

class UserTypeTest extends \PHPUnit_Framework_TestCase
{
    const MY_PROFILE_ROUTE    = 'oro_user_profile_update';
    const OTHER_PROFILE_ROUTE = 'oro_user_update';
    const RULE_BUSINESS_UNIT  = 'oro_business_unit_view';
    const RULE_ORGANIZATION   = 'oro_organization_view';
    const RULE_GROUP          = 'oro_user_group_view';
    const RULE_ROLE           = 'oro_user_role_view';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $securityInterface;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $securityFacade;

    protected function setUp()
    {
        $this->securityInterface = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContextInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade    = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider addEntityFieldsDataProvider
     * @param $permissions
     * @param $isMyProfile
     */
    public function testAddEntityFields($permissions, $isMyProfile)
    {
        $user = new User();
        $user->setId(1);
        $order = 0;

        foreach ($permissions as $rule => $isGranted) {
            $this->securityFacade->expects($this->at($order))
                ->method('isGranted')
                ->with($rule)
                ->will($this->returnValue($isGranted));
            $order++;
        }

        $request = new Request();
        $request->attributes->add(array('_route' => $isMyProfile ? self::MY_PROFILE_ROUTE : self::OTHER_PROFILE_ROUTE));

        $formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $userSubscriber = new UserSubscriber($formFactory, $this->securityInterface);

        $order   = 0;
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('addEventSubscriber', 'add', 'getFormFactory'))
            ->getMock();
        $builder->expects($this->at($order))
            ->method('getFormFactory')
            ->will($this->returnValue($formFactory));
        $builder->expects($this->at(++$order))
            ->method('addEventSubscriber')
            ->with($userSubscriber)
            ->will($this->returnSelf());

        $this->mockSetDefaultUserFields($builder, $order);

        if ($permissions[self::RULE_ROLE]) {
            $builder->expects($this->at(++$order))
                ->method('add')
                ->with('roles', 'entity')
                ->will($this->returnValue($builder));
        }
        if ($permissions[self::RULE_GROUP]) {
            $arr = array(
                'label'     => 'oro.user.groups.label',
                'class'     => 'OroUserBundle:Group',
                'property'  => 'name',
                'multiple'  => true,
                'expanded'  => true,
                'required'  => false,
                'read_only' => $isMyProfile,
                'disabled'  => $isMyProfile,
                'translatable_options' => false
            );
            $builder->expects($this->at(++$order))
                ->method('add')
                ->with('groups', 'entity', $arr)
                ->will($this->returnValue($builder));
        }
        if ($permissions[self::RULE_BUSINESS_UNIT] && $permissions[self::RULE_ORGANIZATION]) {
            $builder->expects($this->at(++$order))
                ->method('add')
                ->with('organizations', 'oro_organizations_select')
                ->will($this->returnValue($builder));
        }
        $builder->expects($this->at(++$order))
            ->method('add')
            ->with('plainPassword', 'repeated')
            ->will($this->returnValue($builder));
        $builder->expects($this->at(++$order))
            ->method('add')
            ->with('emails', 'collection')
            ->will($this->returnValue($builder));
        $builder->expects($this->at(++$order))
            ->method('add')
            ->with('tags', 'oro_tag_select')
            ->will($this->returnValue($builder));
        $builder->expects($this->at(++$order))
            ->method('add')
            ->with('imapConfiguration', 'oro_imap_configuration')
            ->will($this->returnValue($builder));
        $builder->expects($this->at(++$order))
            ->method('add')
            ->with('change_password', 'oro_change_password')
            ->will($this->returnValue($builder));
        $builder->expects($this->at(++$order))
            ->method('add')
            ->with('avatar', 'oro_image')
            ->will($this->returnValue($builder));
        $builder->expects($this->at(++$order))
            ->method('add')
            ->with('inviteUser', 'checkbox')
            ->will($this->returnValue($builder));

        $type = new UserType($this->securityInterface, $this->securityFacade, $request);
        $type->buildForm($builder, []);
    }

    /**
     * @return array
     */
    public function addEntityFieldsDataProvider()
    {
        return array(
            'own profile with all permission' => array(
                'permissions' => array(
                    self::RULE_ROLE          => true,
                    self::RULE_GROUP         => true,
                    self::RULE_ORGANIZATION  => true,
                    self::RULE_BUSINESS_UNIT => true,
                ),
                'isMyProfile' => true
            ),
            'other profile with all permission' => array(
                'permissions' => array(
                    self::RULE_ROLE          => false,
                    self::RULE_GROUP         => true,
                    self::RULE_ORGANIZATION  => true,
                    self::RULE_BUSINESS_UNIT => true,
                ),
                'isMyProfile' => false
            ),
            'own profile without permission for role' => array(
                'permissions' => array(
                    self::RULE_ROLE          => false,
                    self::RULE_GROUP         => true,
                    self::RULE_ORGANIZATION  => true,
                    self::RULE_BUSINESS_UNIT => true,
                ),
                'isMyProfile' => true
            ),
            'own profile without permission for group' => array(
                'permissions' => array(
                    self::RULE_ROLE          => true,
                    self::RULE_GROUP         => false,
                    self::RULE_ORGANIZATION  => true,
                    self::RULE_BUSINESS_UNIT => true,
                ),
                'isMyProfile' => true
            ),
            'own profile without permission for business unit' => array(
                'permissions' => array(
                    self::RULE_ROLE          => true,
                    self::RULE_GROUP         => true,
                    self::RULE_ORGANIZATION  => true,
                    self::RULE_BUSINESS_UNIT => false,
                ),
                'isMyProfile' => true
            ),
            'own profile without all permission' => array(
                'permissions' => array(
                    self::RULE_ROLE          => false,
                    self::RULE_GROUP         => false,
                    self::RULE_ORGANIZATION  => true,
                    self::RULE_BUSINESS_UNIT => false,
                ),
                'isMyProfile' => true
            ),
        );
    }

    /**
     * @param $builder \PHPUnit_Framework_MockObject_MockObject
     * @param $order
     */
    protected function mockSetDefaultUserFields($builder, &$order = -1)
    {
        $parameters = array(
            array('username', 'text'),
            array('email', 'email'),
            array('namePrefix', 'text'),
            array('firstName', 'text'),
            array('middleName', 'text'),
            array('lastName', 'text'),
            array('nameSuffix', 'text'),
            array('birthday', 'oro_date')
        );

        foreach ($parameters as $param) {
            $builder->expects($this->at(++$order))
                ->method('add')
                ->with($param[0], $param[1])
                ->will($this->returnValue($builder));
        }
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMockForAbstractClass('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults');
        $type = new UserType($this->securityInterface, $this->securityFacade, new Request());
        $type->setDefaultOptions($resolver);
    }
}
