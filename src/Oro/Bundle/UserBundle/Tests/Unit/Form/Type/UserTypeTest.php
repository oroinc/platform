<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\FormBundle\Form\Type\OroBirthdayType;
use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationsSelectType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\EventListener\UserSubscriber;
use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Oro\Bundle\UserBundle\Form\Provider\RolesChoicesForUserProvider;
use Oro\Bundle\UserBundle\Form\Type\ChangePasswordType;
use Oro\Bundle\UserBundle\Form\Type\UserType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserTypeTest extends \PHPUnit\Framework\TestCase
{
    private const MY_PROFILE_ROUTE = 'oro_user_profile_update';
    private const OTHER_PROFILE_ROUTE = 'oro_user_update';
    private const RULE_BUSINESS_UNIT = 'oro_business_unit_view';
    private const RULE_ORGANIZATION = 'oro_organization_view';
    private const RULE_GROUP = 'oro_user_group_view';
    private const RULE_ROLE = 'oro_user_role_view';

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var PasswordFieldOptionsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $optionsProvider;

    /** @var RolesChoicesForUserProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $rolesChoicesForUserProvider;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->optionsProvider = $this->createMock(PasswordFieldOptionsProvider::class);
        $this->rolesChoicesForUserProvider = $this->createMock(RolesChoicesForUserProvider::class);
    }

    /**
     * @dataProvider addEntityFieldsDataProvider
     */
    public function testAddEntityFields(array $permissions, bool $isMyProfile)
    {
        $user = new User();
        $user->setId(1);

        $withForIsGranted = [];
        $willForIsGranted = [];
        foreach ($permissions as $rule => $isGranted) {
            $withForIsGranted[] = [$rule];
            $willForIsGranted[] = $isGranted;
        }
        $this->authorizationChecker->expects($this->exactly(count($permissions)))
            ->method('isGranted')
            ->withConsecutive(...$withForIsGranted)
            ->willReturnOnConsecutiveCalls(...$willForIsGranted);

        $request = new Request();
        $request->attributes->add(['_route' => $isMyProfile ? self::MY_PROFILE_ROUTE : self::OTHER_PROFILE_ROUTE]);

        $formFactory = $this->createMock(FormFactory::class);

        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->once())
            ->method('getFormFactory')
            ->willReturn($formFactory);
        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with(new UserSubscriber($formFactory, $this->tokenAccessor))
            ->willReturnSelf();

        $formFields = [
            ['username', TextType::class],
            ['email', EmailType::class],
            ['phone', TextType::class],
            ['namePrefix', TextType::class],
            ['firstName', TextType::class],
            ['middleName', TextType::class],
            ['lastName', TextType::class],
            ['nameSuffix', TextType::class],
            ['birthday', OroBirthdayType::class]
        ];

        if ($permissions[self::RULE_ROLE]) {
            $formFields[] = ['userRoles', EntityType::class];
        }

        $attr = [];
        if ($isMyProfile) {
            $attr['readonly'] = true;
        }

        if ($permissions[self::RULE_GROUP]) {
            $formFields[] = ['groups', EntityType::class, [
                'label'                => 'oro.user.groups.label',
                'class'                => Group::class,
                'choice_label'         => 'name',
                'multiple'             => true,
                'expanded'             => true,
                'required'             => false,
                'attr'                 => $attr,
                'disabled'             => $isMyProfile,
                'translatable_options' => false
            ]];
        }
        if ($permissions[self::RULE_BUSINESS_UNIT] && $permissions[self::RULE_ORGANIZATION]) {
            $formFields[] = ['organizations', OrganizationsSelectType::class];
        }
        $formFields[] = ['emails', CollectionType::class];
        $formFields[] = ['change_password', ChangePasswordType::class];
        $formFields[] = ['avatar', ImageType::class];
        $formFields[] = ['inviteUser', CheckboxType::class];

        $builder->expects($this->exactly(count($formFields)))
            ->method('add')
            ->withConsecutive(...$formFields)
            ->willReturnSelf();

        $requestStack = new RequestStack();
        $requestStack->push($request);
        $type = new UserType(
            $this->authorizationChecker,
            $this->tokenAccessor,
            $requestStack,
            $this->optionsProvider,
            $this->rolesChoicesForUserProvider
        );
        $type->buildForm($builder, []);
    }

    public function addEntityFieldsDataProvider(): array
    {
        return [
            'own profile with all permission'                  => [
                'permissions' => [
                    self::RULE_ROLE          => true,
                    self::RULE_GROUP         => true,
                    self::RULE_ORGANIZATION  => true,
                    self::RULE_BUSINESS_UNIT => true,
                ],
                'isMyProfile' => true
            ],
            'other profile with all permission'                => [
                'permissions' => [
                    self::RULE_ROLE          => false,
                    self::RULE_GROUP         => true,
                    self::RULE_ORGANIZATION  => true,
                    self::RULE_BUSINESS_UNIT => true,
                ],
                'isMyProfile' => false
            ],
            'own profile without permission for role'          => [
                'permissions' => [
                    self::RULE_ROLE          => false,
                    self::RULE_GROUP         => true,
                    self::RULE_ORGANIZATION  => true,
                    self::RULE_BUSINESS_UNIT => true,
                ],
                'isMyProfile' => true
            ],
            'own profile without permission for group'         => [
                'permissions' => [
                    self::RULE_ROLE          => true,
                    self::RULE_GROUP         => false,
                    self::RULE_ORGANIZATION  => true,
                    self::RULE_BUSINESS_UNIT => true,
                ],
                'isMyProfile' => true
            ],
            'own profile without permission for business unit' => [
                'permissions' => [
                    self::RULE_ROLE          => true,
                    self::RULE_GROUP         => true,
                    self::RULE_ORGANIZATION  => true,
                    self::RULE_BUSINESS_UNIT => false,
                ],
                'isMyProfile' => true
            ],
            'own profile without all permission'               => [
                'permissions' => [
                    self::RULE_ROLE          => false,
                    self::RULE_GROUP         => false,
                    self::RULE_ORGANIZATION  => true,
                    self::RULE_BUSINESS_UNIT => false,
                ],
                'isMyProfile' => true
            ],
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults');
        $requestStack = new RequestStack();
        $requestStack->push(new Request());
        $type = new UserType(
            $this->authorizationChecker,
            $this->tokenAccessor,
            $requestStack,
            $this->optionsProvider,
            $this->rolesChoicesForUserProvider
        );
        $type->configureOptions($resolver);
    }
}
