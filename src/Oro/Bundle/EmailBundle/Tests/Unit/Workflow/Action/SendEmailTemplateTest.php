<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Model\DTO\EmailAddressDTO;
use Oro\Bundle\EmailBundle\Model\DTO\LocalizedTemplateDTO;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Workflow\Action\SendEmailTemplate;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;

class SendEmailTemplateTest extends AbstractSendEmailTemplateTest
{
    /** @var SendEmailTemplate */
    private $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new SendEmailTemplate(
            $this->contextAccessor,
            $this->emailProcessor,
            new EmailAddressHelper(),
            $this->entityNameResolver,
            $this->registry,
            $this->validator,
            $this->localizedTemplateProvider,
            $this->emailOriginHelper
        );

        $this->action->setLogger($this->logger);
        $this->action->setDispatcher($this->dispatcher);
    }

    /**
     * @param array $options
     * @param string $exceptionName
     * @param string $exceptionMessage
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $exceptionName, $exceptionMessage): void
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider(): array
    {
        return [
            'no from' => [
                'options' => ['to' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass()],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'From parameter is required',
            ],
            'no from email' => [
                'options' => [
                    'to' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'from' => ['name' => 'Test'],
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required',
            ],
            'no to or recipients' => [
                'options' => ['from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass()],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Need to specify "to" or "recipients" parameters',
            ],
            'no to email' => [
                'options' => [
                    'from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'to' => ['name' => 'Test'],
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required',
            ],
            'recipients in not an array' => [
                'options' => [
                    'from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'recipients' => 'some@recipient.com',
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Recipients parameter must be an array',
            ],
            'no to email in one of addresses' => [
                'options' => [
                    'from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'to' => ['test@test.com', ['name' => 'Test']],
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required',
            ],
            'no template' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'entity' => new \stdClass()],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Template parameter is required',
            ],
            'no entity' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'template' => 'test'],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Entity parameter is required',
            ],
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param array $expected
     */
    public function testInitialize($options, $expected): void
    {
        $this->assertSame($this->action, $this->action->initialize($options));
        $this->assertAttributeEquals($expected, 'options', $this->action);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function optionsDataProvider(): array
    {
        return [
            'simple' => [
                [
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ],
            ],
            'simple with name' => [
                [
                    'from' => 'Test <test@test.com>',
                    'to' => 'Test <test@test.com>',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => 'Test <test@test.com>',
                    'to' => ['Test <test@test.com>'],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ],
            ],
            'extended' => [
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com',
                    ],
                    'to' => [
                        'name' => 'Test',
                        'email' => 'test@test.com',
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com',
                    ],
                    'to' => [
                        [
                            'name' => 'Test',
                            'email' => 'test@test.com',
                        ],
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ],
            ],
            'multiple to' => [
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com',
                    ],
                    'to' => [
                        [
                            'name' => 'Test',
                            'email' => 'test@test.com',
                        ],
                        'test@test.com',
                        'Test <test@test.com>',
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com',
                    ],
                    'to' => [
                        [
                            'name' => 'Test',
                            'email' => 'test@test.com',
                        ],
                        'test@test.com',
                        'Test <test@test.com>',
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ],
            ],
            'with recipients' => [
                [
                    'from' => 'test@test.com',
                    'to' => 'test2@test.com',
                    'recipients' => [new EmailHolderStub()],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => 'test@test.com',
                    'to' => ['test2@test.com'],
                    'recipients' => [new EmailHolderStub()],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
            ],
        ];
    }

    /**
     * Test with expected \Doctrine\ORM\EntityNotFoundException for the case, when template does not found
     *
     * @expectedException \Doctrine\ORM\EntityNotFoundException
     */
    public function testExecuteWithoutTemplateEntity(): void
    {
        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->willThrowException(new EntityNotFoundException());

        $this->emailProcessor->expects($this->never())
            ->method('process');

        $this->action->initialize(
            [
                'from' => 'test@test.com',
                'to' => 'test@test.com',
                'template' => 'test',
                'subject' => 'subject',
                'body' => 'body',
                'entity' => new \stdClass(),
            ]
        );
        $this->action->execute([]);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ValidatorException
     * @expectedExceptionMessage test
     */
    public function testExecuteWithInvalidEmail(): void
    {
        $violationListInterface = $this->createMock('Symfony\Component\Validator\ConstraintViolationInterface');
        $violationListInterface->expects($this->once())
            ->method('getMessage')
            ->willReturn('test');

        $violationList = $this->createMock('Symfony\Component\Validator\ConstraintViolationList');
        $violationList->expects($this->once())
            ->method('count')
            ->willReturn(1);
        $violationList->expects($this->once())
            ->method('get')
            ->willReturn($violationListInterface);

        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violationList);

        $this->localizedTemplateProvider->expects($this->never())
            ->method($this->anything());

        $this->emailProcessor->expects($this->never())
            ->method($this->anything());

        $this->action->initialize(
            [
                'from' => 'invalidemailaddress',
                'to' => 'test@test.com',
                'template' => 'test',
                'subject' => 'subject',
                'body' => 'body',
                'entity' => new \stdClass(),
            ]
        );
        $this->action->execute([]);
    }

    public function testExecuteWithProcessException(): void
    {
        $rcpt = new EmailAddressDTO('test@test.com');

        $dto = new LocalizedTemplateDTO($this->emailTemplate);
        $dto->addRecipient($rcpt);

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with([$rcpt], new EmailTemplateCriteria('test', \stdClass::class), ['entity' => new \stdClass()])
            ->willReturn([$dto]);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn('plain/text');

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects($this->once())
            ->method('getEmailOrigin')
            ->with('test@test.com', null)
            ->willReturn($emailOrigin);

        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email'))
            ->willThrowException(new \Swift_SwiftException('The email was not delivered.'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Workflow send email template action.');

        $this->action->initialize(
            [
                'from' => 'test@test.com',
                'to' => 'test@test.com',
                'template' => 'test',
                'subject' => 'subject',
                'body' => 'body',
                'entity' => new \stdClass(),
            ]
        );
        $this->action->execute([]);
    }

    /**
     * @dataProvider executeOptionsDataProvider
     *
     * @param array $options
     * @param string|object $recipient
     * @param array $expected
     */
    public function testExecute(array $options, $recipient, array $expected): void
    {
        $context = [];

        $this->entityNameResolver->expects($this->any())
            ->method('getName')
            ->willReturnCallback(
                function () {
                    return '_Formatted';
                }
            );

        if (!$recipient instanceof EmailHolderInterface) {
            $recipient = new EmailAddressDTO($recipient);
        }

        $dto = new LocalizedTemplateDTO($this->emailTemplate);
        $dto->addRecipient(is_object($recipient) ? $recipient : new EmailAddressDTO($recipient));

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with([$recipient], new EmailTemplateCriteria('test', \stdClass::class), ['entity' => new \stdClass()])
            ->willReturn([$dto]);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn('plain/text');
        $this->emailTemplate->expects($this->once())
            ->method('getSubject')
            ->willReturn($expected['subject']);
        $this->emailTemplate->expects($this->once())
            ->method('getContent')
            ->willReturn($expected['body']);

        $emailEntity = $this->createMock('\Oro\Bundle\EmailBundle\Entity\Email');

        $emailUserEntity = $this->getMockBuilder('\Oro\Bundle\EmailBundle\Entity\EmailUser')
            ->disableOriginalConstructor()
            ->setMethods(['getEmail'])
            ->getMock();
        $emailUserEntity->expects($this->any())
            ->method('getEmail')
            ->willReturn($emailEntity);

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects($this->once())
            ->method('getEmailOrigin')
            ->with($expected['from'], null)
            ->willReturn($emailOrigin);

        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with(
                (new Email())
                    ->setFrom($expected['from'])
                    ->setSubject($expected['subject'])
                    ->setBody($expected['body'])
                    ->setTo($expected['to'])
                    ->setType('text'),
                $emailOrigin
            )
            ->willReturn($emailUserEntity);

        if (array_key_exists('attribute', $options)) {
            $this->contextAccessor->expects($this->once())
                ->method('setValue')
                ->with($context, $options['attribute'], $emailEntity);
        }

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function executeOptionsDataProvider(): array
    {
        $nameMock = $this->createMock('Oro\Bundle\LocaleBundle\Model\FirstNameInterface');
        $nameMock->expects($this->any())
            ->method('getFirstName')
            ->willReturn('NAME');
        $recipient = new EmailHolderStub('recipient@test.com');

        return [
            'simple' => [
                [
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                'test@test.com',
                [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'simple with name' => [
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => '"Test" <test@test.com>',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                '"Test" <test@test.com>',
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'extended' => [
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com',
                    ],
                    'to' => [
                        'name' => 'Test',
                        'email' => 'test@test.com',
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                '"Test" <test@test.com>',
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'extended with name formatting' => [
                [
                    'from' => [
                        'name' => $nameMock,
                        'email' => 'test@test.com',
                    ],
                    'to' => [
                        'name' => $nameMock,
                        'email' => 'test@test.com',
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                '"_Formatted" <test@test.com>',
                [
                    'from' => '"_Formatted" <test@test.com>',
                    'to' => ['"_Formatted" <test@test.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'with recipients' => [
                [
                    'from' => 'test@test.com',
                    'recipients' => [$recipient],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                $recipient,
                [
                    'from' => 'test@test.com',
                    'to' => ['recipient@test.com'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecuteWithMultipleRecipients(): void
    {
        $toEmail1 = 'to1@test.com';
        $toEmail2 = 'to2@test.com';

        $recipientEmail1 = 'recipient1@test.com';
        $recipient1 = new EmailHolderStub($recipientEmail1);

        $recipientEmail2 = 'recipient2@test.com';
        $recipient2 = new EmailHolderStub($recipientEmail2);

        $enTemplate = new EmailTemplate();
        $enTemplate->setSubject('subject_en');
        $enTemplate->setContent('body_en');
        $enTemplate->setType('txt');

        $deTemplate = new EmailTemplate();
        $deTemplate->setSubject('subject_de');
        $deTemplate->setContent('body_de');
        $deTemplate->setType('txt');

        $options = [
            'from' => 'from@test.com',
            'to' => [
                $toEmail1,
                $toEmail2,
                ' ',
            ],
            'template' => 'test',
            'entity' => new \stdClass(),
            'attribute' => 'attr',
            'recipients' => [
                $recipient1,
                $recipient2,
            ],
        ];

        $rcpt1 = new EmailAddressDTO($toEmail1);
        $rcpt2 = new EmailAddressDTO($toEmail2);

        $dto1 = new LocalizedTemplateDTO($enTemplate);
        $dto1->addRecipient($rcpt1);
        $dto1->addRecipient($recipient1);

        $dto2 = new LocalizedTemplateDTO($deTemplate);
        $dto2->addRecipient($rcpt2);
        $dto2->addRecipient($recipient2);

        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
            ->with(
                [$rcpt1, $rcpt2, $recipient1, $recipient2],
                new EmailTemplateCriteria('test', \stdClass::class),
                ['entity' => new \stdClass()]
            )
            ->willReturn([$dto1, $dto2]);

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects($this->exactly(2))
            ->method('getEmailOrigin')
            ->with($options['from'], null)
            ->willReturn($emailOrigin);

        $this->emailProcessor->expects($this->exactly(2))
            ->method('process')
            ->withConsecutive(
                [
                    (new Email())
                        ->setFrom($options['from'])
                        ->setSubject($enTemplate->getSubject())
                        ->setBody($enTemplate->getContent())
                        ->setTo([$toEmail1, $recipientEmail1])
                        ->setType('text'),
                    $emailOrigin,
                ],
                [
                    (new Email())
                        ->setFrom($options['from'])
                        ->setSubject($deTemplate->getSubject())
                        ->setBody($deTemplate->getContent())
                        ->setTo([$toEmail2, $recipientEmail2])
                        ->setType('text'),
                    $emailOrigin,
                ]
            )
            ->willReturn(
                (new EmailUser())
                    ->setEmail(new EmailEntity())
            );

        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with([], $options['attribute'], $this->isInstanceOf(EmailEntity::class));

        $this->action->initialize($options);
        $this->action->execute([]);
    }
}
