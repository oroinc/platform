<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\UserSubscriber;
use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Oro\Bundle\UserBundle\Form\Type\UserType;
use Oro\Bundle\FormBundle\Form\Type\OroBirthdayType;

class UserTypeTest extends \PHPUnit_Framework_TestCase
{
    const MY_PROFILE_ROUTE    = 'oro_user_profile_update';
    const OTHER_PROFILE_ROUTE = 'oro_user_update';
    const RULE_BUSINESS_UNIT  = 'oro_business_unit_view';
    const RULE_ORGANIZATION   = 'oro_organization_view';
    const RULE_GROUP          = 'oro_user_group_view';
    const RULE_ROLE           = 'oro_user_role_view';

    /** @var AuthorizationCheckerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenAccessor;

    /** @var PasswordFieldOptionsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $optionsProvider;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->optionsProvider = $this->createMock(PasswordFieldOptionsProvider::class);
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
            $this->authorizationChecker->expects($this->at($order))
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

        $userSubscriber = new UserSubscriber($formFactory, $this->tokenAccessor);

        $order   = 0;
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('addEventSubscriber', 'add', 'getFormFactory', 'addEventListener'))
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
                ->with('roles', EntityType::class)
                ->will($this->returnValue($builder));
        }
        if ($permissions[self::RULE_GROUP]) {
            $arr = array(
                'label'     => 'oro.user.groups.label',
                'class'     => 'OroUserBundle:Group',
                'choice_label' => 'name',
                'multiple'  => true,
                'expanded'  => true,
                'required'  => false,
                'read_only' => $isMyProfile,
                'disabled'  => $isMyProfile,
                'translatable_options' => false
            );
            $builder->expects($this->at(++$order))
                ->method('add')
                ->with('groups', EntityType::class, $arr)
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
            ->with('emails', 'collection')
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

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $type = new UserType($this->authorizationChecker, $this->tokenAccessor, $requestStack, $this->optionsProvider);
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
            array('phone', 'text'),
            array('namePrefix', 'text'),
            array('firstName', 'text'),
            array('middleName', 'text'),
            array('lastName', 'text'),
            array('nameSuffix', 'text'),
            array('birthday', OroBirthdayType::class)
        );

        foreach ($parameters as $param) {
            $builder->expects($this->at(++$order))
                ->method('add')
                ->with($param[0], $param[1])
                ->will($this->returnValue($builder));
        }
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults');
        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $type = new UserType(
            $this->authorizationChecker,
            $this->tokenAccessor,
            $requestStack,
            $this->optionsProvider
        );
        $type->configureOptions($resolver);
    }
}
