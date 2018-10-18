<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\Event;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\NotificationBundle\Form\EventListener\AdditionalEmailsSubscriber;
use Oro\Bundle\NotificationBundle\Form\EventListener\ContactInformationEmailsSubscriber;
use Oro\Bundle\NotificationBundle\Form\Type\EmailNotificationEntityChoiceType;
use Oro\Bundle\NotificationBundle\Form\Type\EmailNotificationType;
use Oro\Bundle\NotificationBundle\Form\Type\RecipientListType;
use Oro\Bundle\NotificationBundle\Provider\ContactInformationEmailsProvider;
use Oro\Bundle\NotificationBundle\Tests\Unit\Form\Type\Stub\Select2TranslatableEntityTypeStub;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\OrganizationUserAclMultiSelectType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EmailNotificationTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $registry */
    protected $registry;

    /** @var EmailNotificationType */
    protected $formType;

    protected function setUp()
    {
        /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject $tokenStorage */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->any())->method('getToken')->willReturn($this->getToken());

        $this->configProvider = $this->createMock(ConfigProvider::class);

        /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->any())
            ->method('generate')
            ->with('oro_email_emailtemplate_index')
            ->willReturn('test/url');

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->any())
            ->method('getAssociationMappings')
            ->willReturn([]);
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);
        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        $contactInformationEmailsProvider = $this->createMock(ContactInformationEmailsProvider::class);

        $this->formType = new EmailNotificationType(
            new BuildTemplateFormSubscriber($tokenStorage),
            new AdditionalEmailsSubscriber($this->registry, $this->getTranslator(), $configManager),
            $router,
            new ContactInformationEmailsSubscriber($contactInformationEmailsProvider)
        );

        parent::setUp();
    }

    /**
     * @dataProvider submitProvider
     *
     * @param EmailNotification $defaultData
     * @param array $submittedData
     * @param EmailNotification $expectedData
     */
    public function testSubmit(EmailNotification $defaultData, array $submittedData, EmailNotification $expectedData)
    {
        $form = $this->factory->create(EmailNotificationType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $entity = new EmailNotification();
        $entity->setRecipientList(new RecipientList());

        return [
            'question without submitted data' => [
                'defaultData' => $entity,
                'submittedData' => [],
                'expectedData' => clone $entity
            ],
            'altered existing question' => [
                'defaultData' => $this->getEntity(EmailNotification::class, ['id' => 42]),
                'submittedData' => [
                    'entityName' => 'user',
                    'event' => 100,
                    'template' => 200,
                    'recipientList' => [
                        'groups' => [1],
                        'users' => '3',
                        'email' => 'test@example.com'
                    ]
                ],
                'expectedData' => $this->getEntity(
                    EmailNotification::class,
                    [
                        'id' => 42,
                        'entityName' => User::class,
                        'event' => new Event('test'),
                        'template' => new EmailTemplate('test'),
                        'recipientList' => $this->getEntity(
                            RecipientList::class,
                            [
                                'users' => new ArrayCollection([$this->getUser()]),
                                'groups' => new ArrayCollection([new Group()]),
                                'email' => 'test@example.com'
                            ]
                        )
                    ]
                )
            ]
        ];
    }

    public function testFinishView()
    {
        $childFormView1 = new FormView();
        $childFormView1->vars['id'] = 'entity_name_id';
        $childFormView1->vars['name'] = 'entityName';

        $childFormView2 = new FormView();
        $childFormView2->vars['id'] = 'unsupported_id';
        $childFormView2->vars['name'] = 'unsupported';

        $formView = new FormView();
        $formView->children = [
            'entityName' => $childFormView1,
            'unsupported' => $childFormView2
        ];

        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);

        $this->formType->finishView($formView, $form, []);

        $this->assertArrayHasKey('listenChangeElements', $formView->vars);
        $this->assertEquals(['#entity_name_id'], $formView->vars['listenChangeElements']);
    }

    /**
     * @return UsernamePasswordOrganizationToken
     */
    protected function getToken()
    {
        return new UsernamePasswordOrganizationToken(new User(2), ['test'], 'key', new Organization(3));
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $select2EntityType = new EntityTypeStub(
            [100 => new Event('test')],
            'oro_select2_entity',
            ['configs' => [], 'property' => null]
        );

        $select2TranslatableEntityType = new Select2TranslatableEntityTypeStub(
            [200 => new EmailTemplate('test')],
            'oro_select2_translatable_entity',
            ['configs' => []]
        );

        $entityType = new EntityTypeStub([1 => new Group()], 'entity', ['property' => null]);

        $recipientListType = new RecipientListType();

        $userOrganizationType = new EntityTypeStub(
            [null => new ArrayCollection(), 3 => new ArrayCollection([$this->getUser()])],
            'oro_user_organization_acl_multiselect',
            ['configs' => []]
        );

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    EmailNotificationEntityChoiceType::class => new EntityTypeStub(
                        ['user' => User::class, 'stdClass' => \stdClass::class],
                        EmailNotificationEntityChoiceType::NAME,
                        ['configs' => []]
                    ),
                    Select2EntityType::class => $select2EntityType,
                    Select2TranslatableEntityType::class => $select2TranslatableEntityType,
                    EntityType::class => $entityType,
                    RecipientListType::class => $recipientListType,
                    OrganizationUserAclMultiSelectType::class => $userOrganizationType
                ],
                [
                    FormType::class => [
                        new TooltipFormExtension($this->configProvider, $this->createMock(Translator::class))
                    ],
                ]
            ),
            $this->getValidatorExtension(false)
        ];
    }

    /**
     * @return User
     */
    private function getUser()
    {
        static $user;

        if (empty($user)) {
            $user = new User();
        }

        return $user;
    }
}
