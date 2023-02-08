<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\NotificationBundle\Form\EventListener\AdditionalEmailsSubscriber;
use Oro\Bundle\NotificationBundle\Form\EventListener\ContactInformationEmailsSubscriber;
use Oro\Bundle\NotificationBundle\Form\Type\EmailNotificationEntityChoiceType;
use Oro\Bundle\NotificationBundle\Form\Type\EmailNotificationType;
use Oro\Bundle\NotificationBundle\Form\Type\RecipientListType;
use Oro\Bundle\NotificationBundle\Provider\ChainAdditionalEmailAssociationProvider;
use Oro\Bundle\NotificationBundle\Provider\ContactInformationEmailsProvider;
use Oro\Bundle\NotificationBundle\Tests\Unit\Form\Type\Stub\Select2TranslatableEntityTypeStub;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Form\Type\OrganizationUserAclMultiSelectType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EmailNotificationTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var EmailNotificationType */
    private $formType;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($this->getToken());

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->any())
            ->method('generate')
            ->with('oro_email_emailtemplate_index')
            ->willReturn('test/url');

        $this->formType = new EmailNotificationType(
            new BuildTemplateFormSubscriber($tokenStorage),
            new AdditionalEmailsSubscriber($this->createMock(ChainAdditionalEmailAssociationProvider::class)),
            $router,
            new ContactInformationEmailsSubscriber($this->createMock(ContactInformationEmailsProvider::class)),
            [
                'test_1',
                'test_2'
            ]
        );

        parent::setUp();
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(EmailNotification $defaultData, array $submittedData, EmailNotification $expectedData)
    {
        $form = $this->factory->create(EmailNotificationType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    public function submitProvider(): array
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
                    'eventName' => 'test_1',
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
                        'eventName' => 'test_1',
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

        $form = $this->createMock(FormInterface::class);

        $this->formType->finishView($formView, $form, []);

        $this->assertArrayHasKey('listenChangeElements', $formView->vars);
        $this->assertEquals(['#entity_name_id'], $formView->vars['listenChangeElements']);
    }

    private function getToken(): UsernamePasswordOrganizationToken
    {
        return new UsernamePasswordOrganizationToken(new User(), 'test', 'key', new Organization());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    EmailNotificationEntityChoiceType::class => new EntityTypeStub(
                        ['user' => User::class, 'stdClass' => \stdClass::class],
                        ['configs' => []]
                    ),
                    Select2TranslatableEntityType::class => new Select2TranslatableEntityTypeStub(
                        [200 => new EmailTemplate('test')],
                        ['configs' => []]
                    ),
                    EntityType::class => new EntityTypeStub([1 => new Group()], ['property' => null]),
                    OrganizationUserAclMultiSelectType::class => new EntityTypeStub(
                        [null => new ArrayCollection(), 3 => new ArrayCollection([$this->getUser()])],
                        ['configs' => []]
                    ),
                    new RecipientListType()
                ],
                [
                    FormType::class => [new TooltipFormExtensionStub($this)]
                ]
            ),
            $this->getValidatorExtension()
        ];
    }

    private function getUser(): User
    {
        static $user;

        if (empty($user)) {
            $user = new User();
        }

        return $user;
    }
}
