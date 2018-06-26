<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
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
use Oro\Bundle\EmailBundle\Provider\EmailRenderer;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestMailbox;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\ImapBundle\Tests\Unit\Stub\TestUserEmailOrigin;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EmailTypeTest extends TypeTestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var EmailRenderer|\PHPUnit\Framework\MockObject\MockObject */
    protected $emailRenderer;

    /** @var EmailModelBuilderHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $emailModelBuilderHelper;

    /** @var EmailTemplate */
    protected $emailTemplate;

    /** @var MailboxManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $mailboxManager;

    /** @var Registry|\PHPUnit\Framework\MockObject\MockObject */
    protected $registry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var EmailOriginHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $emailOriginHelper;

    /** @var ConfigManager */
    protected $configManager;

    /** @var HtmlTagProvider */
    protected $htmlTagProvider;

    /** @var ValidatorInterface */
    protected $validator;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->emailRenderer = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRenderer')
            ->disableOriginalConstructor()->getMock();
        $this->emailModelBuilderHelper = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper')
            ->disableOriginalConstructor()->getMock();
        $this->htmlTagProvider = $this->createMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        parent::setUp();
    }

    /**
     * @return EmailType
     */
    protected function createEmailType()
    {
        return new EmailType(
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->emailRenderer,
            $this->emailModelBuilderHelper,
            $this->configManager
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    protected function getExtensions()
    {
        $translatableType = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->disableOriginalConstructor()
            ->getMock();
        $translatableType->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(TranslatableEntityType::NAME));

        // $translatableType = new \Oro\Component\Testing\Unit\Form\Type\Stub\EntityType(
        //     [
        //         'test_name' => (new EmailTemplate())->setName('test_name'),
        //     ],
        //     TranslatableEntityType::NAME
        // );
        $this->mailboxManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager')
            ->disableOriginalConstructor()
            ->getMock();

        $user = new User();
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user));

        $relatedEmailsProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $relatedEmailsProvider->expects($this->any())
            ->method('getEmails')
            ->with($user)
            ->will($this->returnValue(['john@example.com' => 'John Smith <john@example.com>']));

        $this->mailboxManager->expects($this->any())
            ->method('findAvailableMailboxEmails')
            ->will($this->returnValue([]));

        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $helper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailOriginHelper = $this->getMockBuilder('Oro\Bundle\EmailBundle\Tools\EmailOriginHelper')
            ->disableOriginalConstructor()->getMock();

        $emailOriginFromType = new EmailOriginFromType(
            $this->tokenAccessor,
            $relatedEmailsProvider,
            $helper,
            $this->mailboxManager,
            $this->registry,
            $this->emailOriginHelper
        );

        $emailAddressFromType = new EmailAddressFromType(
            $this->tokenAccessor,
            $relatedEmailsProvider,
            $this->mailboxManager
        );
        $emailAddressRecipientsType = new EmailAddressRecipientsType($configManager);

        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $htmlTagProvider = $this->createMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);
        $context = $this->createMock(ContextInterface::class);
        $richTextType = new OroRichTextType($configManager, $htmlTagProvider, $context);
        $resizableRichTextType = new OroResizeableRichTextType($configManager, $htmlTagProvider);
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getName');
        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $repo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->any())
            ->method('find');
        $this->em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $translator = $this->getMockBuilder('Symfony\Component\Translation\DataCollectorTranslator')
            ->disableOriginalConstructor()
            ->getMock();
        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $entityTitleResolver = $this->getMockBuilder(EntityNameResolver::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());
        $this->validator
            ->method('getMetadataFor')
            ->with('Symfony\Component\Form\Form')
            ->willReturn($this->getMockBuilder(ClassMetadata::class)->disableOriginalConstructor()->getMock());

        $contextsSelectType = new ContextsSelectType(
            $this->em,
            $configManager,
            $translator,
            $eventDispatcher,
            $entityTitleResolver,
            $this->createMock(FeatureChecker::class)
        );

        return [
            new PreloadedExtension(
                [
                    EmailType::class => $this->createEmailType(),
                    TranslatableEntityType::class      => $translatableType,
                    $richTextType->getName()          => $richTextType,
                    $resizableRichTextType->getName() => $resizableRichTextType,
                    ContextsSelectType::class          => $contextsSelectType,
                    $emailAddressFromType->getName()       => $emailAddressFromType,
                    $emailAddressRecipientsType->getName() => $emailAddressRecipientsType,
                    $emailOriginFromType->getName() => $emailOriginFromType
                ],
                []
            ),
            new ValidatorExtension($this->validator),
        ];
    }

    /**
     * @dataProvider messageDataProvider
     * @param array $formData
     * @param array $to
     * @param array $cc
     * @param array $bcc
     */
    public function testSubmitValidData($formData, $to, $cc, $bcc)
    {
        $body = '';
        if (isset($formData['body'])) {
            $body = $formData['body'];
        }

        $user = new User();
        $origin = new TestUserEmailOrigin(1);
        $origin->setUser($user);

        $mailBox = new TestMailbox(1);
        $mailBox->setEmail('john@example.com');
        $mailBox->setOrigin($origin);
        $response = [$mailBox];

        $this->mailboxManager->expects(self::once())->method('findAvailableMailboxes')->willReturn($response);
        $this->registry->expects(self::once())->method('getManager')->willReturn($this->em);

        $form = $this->factory->create(EmailType::class);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());

        /** @var Email $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email', $result);
        $this->assertEquals('test_grid', $result->getGridName());
        $this->assertEquals($to, $result->getTo());
        $this->assertEquals($cc, $result->getCc());
        $this->assertEquals($bcc, $result->getBcc());
        $this->assertEquals($formData['subject'], $result->getSubject());
        $this->assertEquals($body, $result->getBody());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'         => 'Oro\Bundle\EmailBundle\Form\Model\Email',
                    'csrf_token_id'      => 'email',
                    'csrf_protection'    => true,
                ]
            );

        $type = $this->createEmailType();
        $type->configureOptions($resolver);
    }

    /**
     * @return array
     */
    public function messageDataProvider()
    {
        return [
            [
                [
                    'gridName' => 'test_grid',
                    'origin'=>'1|john@example.com',
                    'to' => [
                        'John Smith 1 <john1@example.com>',
                        '"John Smith 2" <john2@example.com>',
                        'john3@example.com',
                    ],
                    'subject' => 'Test subject',
                    'type' => 'text',
                    'attachments' => [],
                    'template' => new EmailTemplate(),
                ],
                ['John Smith 1 <john1@example.com>', '"John Smith 2" <john2@example.com>', 'john3@example.com'],
                [],
                [],
            ],
            [
                [
                    'gridName' => 'test_grid',
                    'origin'=>'1|john@example.com',
                    'to' => [
                        'John Smith 1 <john1@example.com>',
                        '"John Smith 2" <john2@example.com>',
                        'john3@example.com',
                    ],
                    'cc' => [
                        'John Smith 4 <john4@example.com>',
                        '"John Smith 5" <john5@example.com>',
                        'john6@example.com',
                    ],
                    'bcc' => [
                        'John Smith 7 <john7@example.com>',
                        '"John Smith 8" <john8@example.com>',
                        'john9@example.com',
                    ],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                    'type' => 'text',
                    'template' => new EmailTemplate(),
                ],
                ['John Smith 1 <john1@example.com>', '"John Smith 2" <john2@example.com>', 'john3@example.com'],
                ['John Smith 4 <john4@example.com>', '"John Smith 5" <john5@example.com>', 'john6@example.com'],
                ['John Smith 7 <john7@example.com>', '"John Smith 8" <john8@example.com>', 'john9@example.com'],
            ],
        ];
    }

    /**
     * @param Email $inputData
     * @param array $expectedData
     *
     * @dataProvider fillFormByTemplateProvider
     */
    public function testFillFormByTemplate(Email $inputData = null, array $expectedData = [])
    {
        $this->markTestSkipped(
            'Test Skipped because of unresolved relation to \Oro\Component\Testing\Unit\Form\Type\Stub\EntityType'
        );
        $emailTemplate = $this->createEmailTemplate();
        $this->emailRenderer
            ->expects($this->any())
            ->method('compileMessage')
            ->with($emailTemplate)
            ->willReturn(
                [
                    $emailTemplate->getSubject(),
                    $emailTemplate->getContent()
                ]
            );

        $formType = $this->createEmailType();
        $form = $this->factory->create($formType, $inputData);

        $formType->fillFormByTemplate(new FormEvent($form, $inputData));

        $formData = $form->getData();

        $propertyAccess = PropertyAccess::createPropertyAccessor();
        foreach ($expectedData as $propertyPath => $expectedValue) {
            $value = $propertyAccess->getValue($formData, $propertyPath);
            $this->assertEquals($expectedValue, $value);
        }
    }

    /**
     * @return array
     */
    public function fillFormByTemplateProvider()
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

    /**
     * @return EmailTemplate
     */
    protected function createEmailTemplate()
    {
        $template = new EmailTemplate();
        $template
            ->setName('test_name')
            ->setSubject('Test Subject')
            ->setContent('Test Body');

        return $template;
    }
}
