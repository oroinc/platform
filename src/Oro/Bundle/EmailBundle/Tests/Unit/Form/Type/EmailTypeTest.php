<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\ActivityBundle\Form\Type\ContextsSelectType;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Form\Type\EmailAddressType;
use Oro\Bundle\EmailBundle\Form\Type\EmailAttachmentsType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateSelectType;
use Oro\Bundle\EmailBundle\Form\Type\EmailAddressFromType;
use Oro\Bundle\EmailBundle\Form\Type\EmailAddressRecipientsType;
use Oro\Bundle\UserBundle\Entity\User;

class EmailTypeTest extends TypeTestCase
{
    /**
     * @var \Symfony\Component\Security\Core\SecurityContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityContext;

    /**
     * @var \Oro\Bundle\EmailBundle\Provider\EmailRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailRenderer;

    /**
     * @var \Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailModelBuilderHelper;

    /**
     * @var EmailTemplate
     */
    protected $emailTemplate;

    protected function setUp()
    {
        parent::setUp();
        $this->securityContext  = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->emailRenderer = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRenderer')
            ->disableOriginalConstructor()->getMock();
        $this->emailModelBuilderHelper = $this
            ->getMockBuilder('Oro\Bundle\EmailBundle\Builder\Helper\EmailModelBuilderHelper')
            ->disableOriginalConstructor()->getMock();
        $this->htmlTagProvider = $this->getMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');
    }

    /**
     * @return EmailType
     */
    protected function createEmailType()
    {
        return new EmailType($this->securityContext, $this->emailRenderer, $this->emailModelBuilderHelper);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    protected function getExtensions()
    {
        $emailAddressType  = new EmailAddressType($this->securityContext);
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

        $user = new User();
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $relatedEmailsProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $relatedEmailsProvider->expects($this->any())
            ->method('getEmails')
            ->with($user)
            ->will($this->returnValue(['john@example.com' => 'John Smith <john@example.com>']));

        $mailboxManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mailboxManager->expects($this->any())
            ->method('findAvailableMailboxEmails')
            ->will($this->returnValue([]));

        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $select2ChoiceType = new Select2Type(TranslatableEntityType::NAME);
        $genemuChoiceType  = new Select2Type('choice');
        $emailTemplateList = new EmailTemplateSelectType();
        $attachmentsType   = new EmailAttachmentsType();
        $emailAddressFromType = new EmailAddressFromType($securityFacade, $relatedEmailsProvider, $mailboxManager);
        $emailAddressRecipientsType = new EmailAddressRecipientsType($configManager);

        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $htmlTagProvider = $this->getMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);
        $richTextType = new OroRichTextType($configManager, $htmlTagProvider);
        $resizableRichTextType = new OroResizeableRichTextType($configManager, $htmlTagProvider);
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())
            ->method('getName');
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $repo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->any())
            ->method('find');
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $translator = $this->getMockBuilder('Symfony\Component\Translation\DataCollectorTranslator')
            ->disableOriginalConstructor()
            ->getMock();
        $mapper = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\ObjectMapper')
            ->disableOriginalConstructor()
            ->getMock();
        $securityTokenStorage =
            $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $entityTitleResolver = $this->getMock('Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface');

        $contextsSelectType = new ContextsSelectType(
            $em,
            $configManager,
            $translator,
            $securityTokenStorage,
            $eventDispatcher,
            $entityTitleResolver
        );

        return [
            new PreloadedExtension(
                [
                    TranslatableEntityType::NAME      => $translatableType,
                    $select2ChoiceType->getName()     => $select2ChoiceType,
                    $emailTemplateList->getName()     => $emailTemplateList,
                    $emailAddressType->getName()      => $emailAddressType,
                    $richTextType->getName()          => $richTextType,
                    $resizableRichTextType->getName() => $resizableRichTextType,
                    $attachmentsType->getName()       => $attachmentsType,
                    ContextsSelectType::NAME          => $contextsSelectType,
                    'genemu_jqueryselect2_hidden'     => new Select2Type('hidden'),
                     $genemuChoiceType->getName()     => $genemuChoiceType,
                    $emailAddressFromType->getName()       => $emailAddressFromType,
                    $emailAddressRecipientsType->getName() => $emailAddressRecipientsType,
                ],
                []
            )
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
        $type = $this->createEmailType();
        $form = $this->factory->create($type);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());

        /** @var Email $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email', $result);
        $this->assertEquals('test_grid', $result->getGridName());
        $this->assertEquals($formData['from'], $result->getFrom());
        $this->assertEquals($to, $result->getTo());
        $this->assertEquals($cc, $result->getCc());
        $this->assertEquals($bcc, $result->getBcc());
        $this->assertEquals($formData['subject'], $result->getSubject());
        $this->assertEquals($body, $result->getBody());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'         => 'Oro\Bundle\EmailBundle\Form\Model\Email',
                    'intention'          => 'email',
                    'csrf_protection'    => true,
                    'cascade_validation' => true
                ]
            );

        $type = $this->createEmailType();
        $type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $type = $this->createEmailType();
        $this->assertEquals('oro_email_email', $type->getName());
    }

    public function messageDataProvider()
    {
        return [
            [
                [
                    'gridName' => 'test_grid',
                    'from' => 'John Smith <john@example.com>',
                    'to' => [
                        'John Smith 1 <john1@example.com>',
                        '"John Smith 2" <john2@example.com>',
                        'john3@example.com',
                    ],
                    'subject' => 'Test subject',
                    'type' => 'text',
                    'attachments' => new ArrayCollection(),
                    'template' => new EmailTemplate(),
                ],
                ['John Smith 1 <john1@example.com>', '"John Smith 2" <john2@example.com>', 'john3@example.com'],
                [],
                [],
            ],
            [
                [
                    'gridName' => 'test_grid',
                    'from' => 'John Smith <john@example.com>',
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
