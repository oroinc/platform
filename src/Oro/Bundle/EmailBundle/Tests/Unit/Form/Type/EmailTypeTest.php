<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Form\Type\ContextsSelectType;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Form\Type\EmailAddressFromType;
use Oro\Bundle\EmailBundle\Form\Type\EmailAddressRecipientsType;
use Oro\Bundle\EmailBundle\Form\Type\EmailOriginFromType;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestMailbox;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\TestUserEmailOrigin;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\DataCollectorTranslator;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailTypeTest extends TypeTestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var EmailRenderer|\PHPUnit\Framework\MockObject\MockObject */
    private $emailRenderer;

    /** @var EmailModelBuilderHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $emailModelBuilderHelper;

    /** @var MailboxManager|\PHPUnit\Framework\MockObject\MockObject */
    private $mailboxManager;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var EmailOriginHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $emailOriginHelper;

    /** @var EmailType */
    private $formType;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->emailRenderer = $this->createMock(EmailRenderer::class);
        $this->emailModelBuilderHelper = $this->createMock(EmailModelBuilderHelper::class);
        $this->mailboxManager = $this->createMock(MailboxManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->em = $this->createMock(EntityManager::class);
        $this->emailOriginHelper = $this->createMock(EmailOriginHelper::class);

        $this->formType = new EmailType(
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->tokenAccessor,
            $this->emailRenderer,
            $this->emailModelBuilderHelper,
            $this->createMock(ConfigManager::class)
        );

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $user = new User();
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $relatedEmailsProvider = $this->createMock(RelatedEmailsProvider::class);
        $relatedEmailsProvider->expects($this->any())
            ->method('getEmails')
            ->with($user)
            ->willReturn(['john@example.com' => 'John Smith <john@example.com>']);

        $htmlTagProvider = $this->createMock(HtmlTagProvider::class);
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);

        $this->doctrine->expects($this->any())
            ->method('getManager')
            ->willReturn($this->em);

        $metadata = $this->createMock(ClassMetadataInfo::class);
        $metadata->expects($this->any())
            ->method('getName');
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->any())
            ->method('find');
        $this->em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        $validator = $this->createMock(ValidatorInterface::class);
        $validator->expects($this->any())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());
        $validator->expects($this->any())
            ->method('getMetadataFor')
            ->with(Form::class)
            ->willReturn($this->createMock(ClassMetadata::class));

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    TranslatableEntityType::class => $this->createMock(TranslatableEntityType::class),
                    new OroRichTextType(
                        $this->createMock(ConfigManager::class),
                        $htmlTagProvider,
                        $this->createMock(ContextInterface::class),
                        $this->createMock(HtmlTagHelper::class)
                    ),
                    new OroResizeableRichTextType(),
                    new ContextsSelectType(
                        $this->em,
                        $this->createMock(EntityConfigManager::class),
                        $this->createMock(DataCollectorTranslator::class),
                        $this->createMock(EventDispatcher::class),
                        $this->createMock(EntityNameResolver::class),
                        $this->createMock(FeatureChecker::class)
                    ),
                    new EmailAddressFromType($this->tokenAccessor, $relatedEmailsProvider, $this->mailboxManager),
                    new EmailAddressRecipientsType($this->createMock(ConfigManager::class)),
                    new EmailOriginFromType(
                        $this->tokenAccessor,
                        $relatedEmailsProvider,
                        $this->emailModelBuilderHelper,
                        $this->mailboxManager,
                        $this->doctrine,
                        $this->emailOriginHelper
                    )
                ],
                []
            ),
            new ValidatorExtension($validator),
        ];
    }

    /**
     * @dataProvider messageDataProvider
     */
    public function testSubmitValidData(array $formData, array $to, array $cc, array $bcc)
    {
        $body = $formData['body'] ?? '';

        $user = new User();
        $origin = new TestUserEmailOrigin(1);
        $origin->setUser($user);

        $mailBox = new TestMailbox(1);
        $mailBox->setEmail('john@example.com');
        $mailBox->setOrigin($origin);
        $response = [$mailBox];

        $this->mailboxManager->expects(self::once())
            ->method('findAvailableMailboxes')
            ->willReturn($response);

        $form = $this->factory->create(EmailType::class);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());

        /** @var Email $result */
        $result = $form->getData();
        $this->assertInstanceOf(Email::class, $result);
        $this->assertEquals('test_grid', $result->getGridName());
        $this->assertEquals($to, $result->getTo());
        $this->assertEquals($cc, $result->getCc());
        $this->assertEquals($bcc, $result->getBcc());
        $this->assertEquals($formData['subject'], $result->getSubject());
        $this->assertEquals($body, $result->getBody());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => Email::class, 'csrf_token_id' => 'email', 'csrf_protection' => true]);

        $this->formType->configureOptions($resolver);
    }

    public function messageDataProvider(): array
    {
        return [
            [
                [
                    'gridName' => 'test_grid',
                    'origin'=>'1|john@example.com',
                    'to' => implode(EmailRecipientsHelper::EMAIL_IDS_SEPARATOR, [
                        'John Smith 1 <john1@example.com>',
                        '\"John Smith 2\" <john2@example.com>',
                        'john3@example.com',
                    ]),
                    'subject' => 'Test subject',
                    'type' => 'text',
                    'attachments' => [],
                    'template' => new EmailTemplate(),
                ],
                ['John Smith 1 <john1@example.com>', '\"John Smith 2\" <john2@example.com>', 'john3@example.com'],
                [],
                [],
            ],
            [
                [
                    'gridName' => 'test_grid',
                    'origin'=>'1|john@example.com',
                    'to' => implode(EmailRecipientsHelper::EMAIL_IDS_SEPARATOR, [
                        'John Smith 1 <john1@example.com>',
                        '\"John Smith 2\" <john2@example.com>',
                        'john3@example.com',
                    ]),
                    'cc' => implode(EmailRecipientsHelper::EMAIL_IDS_SEPARATOR, [
                        'John Smith 4 <john4@example.com>',
                        '\"John Smith 5\" <john5@example.com>',
                        'john6@example.com',
                    ]),
                    'bcc' => implode(EmailRecipientsHelper::EMAIL_IDS_SEPARATOR, [
                        'John Smith 7 <john7@example.com>',
                        '\"John Smith 8\" <john8@example.com>',
                        'john9@example.com',
                    ]),
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                    'type' => 'text',
                    'template' => new EmailTemplate(),
                ],
                ['John Smith 1 <john1@example.com>', '\"John Smith 2\" <john2@example.com>', 'john3@example.com'],
                ['John Smith 4 <john4@example.com>', '\"John Smith 5\" <john5@example.com>', 'john6@example.com'],
                ['John Smith 7 <john7@example.com>', '\"John Smith 8\" <john8@example.com>', 'john9@example.com'],
            ],
        ];
    }

    /**
     * @dataProvider fillFormByTemplateProvider
     */
    public function testFillFormByTemplate(Email $inputData = null, array $expectedData = [])
    {
        $emailTemplate = $this->createEmailTemplate();
        $this->emailRenderer->expects($this->any())
            ->method('compileMessage')
            ->with($emailTemplate)
            ->willReturn([$emailTemplate->getSubject(), $emailTemplate->getContent()]);

        $this->mailboxManager->expects($this->any())
            ->method('findAvailableMailboxes')
            ->willReturn([]);

        $form = $this->factory->create(EmailType::class, $inputData);

        $formData = $form->getData();

        $propertyAccess = PropertyAccess::createPropertyAccessor();
        foreach ($expectedData as $propertyPath => $expectedValue) {
            $value = $propertyAccess->getValue($formData, $propertyPath);
            $this->assertEquals($expectedValue, $value);
        }
    }

    public function fillFormByTemplateProvider(): array
    {
        return [
            'template is not empty' => [
                'inputData' => (new Email())->setTemplate($this->createEmailTemplate()),
                'expectedData' => [
                    'subject' => 'Test Subject',
                    'body' => 'Test Body',
                ],
            ],
            'template and subject is not empty' => [
                'inputData' => (new Email())
                    ->setTemplate($this->createEmailTemplate())
                    ->setSubject('New Test Subject'),
                'expectedData' => [
                    'subject' => 'New Test Subject',
                    'body' => 'Test Body',
                ],
            ],
            'template and body is not empty' => [
                'inputData' => (new Email())
                    ->setTemplate($this->createEmailTemplate())
                    ->setBody('New Test Body'),
                'expectedData' => [
                    'subject' => 'Test Subject',
                    'body' => 'New Test Body',
                ],
            ],
            'template, subject and body is not empty' => [
                'inputData' => (new Email())
                    ->setTemplate($this->createEmailTemplate())
                    ->setSubject('New Test Subject')
                    ->setBody('New Test Body'),
                'expectedData' => [
                    'subject' => 'New Test Subject',
                    'body' => 'New Test Body',
                ],
            ],
        ];
    }

    private function createEmailTemplate(): EmailTemplate
    {
        $template = new EmailTemplate();
        $template
            ->setName('test_name')
            ->setSubject('Test Subject')
            ->setContent('Test Body');

        return $template;
    }
}
