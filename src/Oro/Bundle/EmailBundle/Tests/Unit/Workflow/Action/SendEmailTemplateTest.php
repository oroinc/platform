<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action;

use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Workflow\Action\SendEmailTemplate;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;

class SendEmailTemplateTest extends AbstractSendEmailTemplateTest
{
    protected function setUp()
    {
        $this->createDependencyMocks();

        $this->action = new SendEmailTemplate(
            $this->contextAccessor,
            $this->emailProcessor,
            new EmailAddressHelper(),
            $this->entityNameResolver,
            $this->renderer,
            $this->objectManager,
            $this->validator
        );

        $this->action->setLogger($this->logger);
        $this->action->setPreferredLanguageProvider($this->languageProvider);
        $this->action->setDispatcher($this->dispatcher);
    }

    /**
     * @param array $options
     * @param string $exceptionName
     * @param string $exceptionMessage
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, $exceptionName, $exceptionMessage)
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeExceptionDataProvider()
    {
        return [
            'no from' => [
                'options' => ['to' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass()],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'From parameter is required'
            ],
            'no from email' => [
                'options' => [
                    'to' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'from' => ['name' => 'Test']
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ],
            'no to or recipients' => [
                'options' => ['from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass()],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Need to specify "to" or "recipients" parameters'
            ],
            'no to email' => [
                'options' => [
                    'from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'to' => ['name' => 'Test']
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ],
            'recipients in not an array' => [
                'options' => [
                    'from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'recipients' => 'some@recipient.com'
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Recipients parameter must be an array',
            ],
            'no to email in one of addresses' => [
                'options' => [
                    'from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'to' => ['test@test.com', ['name' => 'Test']]
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ],
            'no template' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'entity' => new \stdClass()],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Template parameter is required'
            ],
            'no entity' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'template' => 'test'],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Entity parameter is required'
            ],
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     * @param array $options
     * @param array $expected
     */
    public function testInitialize($options, $expected)
    {
        $this->assertSame($this->action, $this->action->initialize($options));
        $this->assertAttributeEquals($expected, 'options', $this->action);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function optionsDataProvider()
    {
        return [
            'simple' => [
                [
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'template' => 'test',
                    'entity' => new \stdClass()
                ],
                [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ]
            ],
            'simple with name' => [
                [
                    'from' => 'Test <test@test.com>',
                    'to' => 'Test <test@test.com>',
                    'template' => 'test',
                    'entity' => new \stdClass()
                ],
                [
                    'from' => 'Test <test@test.com>',
                    'to' => ['Test <test@test.com>'],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ]
            ],
            'extended' => [
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ],
                    'to' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass()
                ],
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ],
                    'to' => [
                        [
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        ]
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ]
            ],
            'multiple to' => [
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ],
                    'to' => [
                        [
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        ],
                        'test@test.com',
                        'Test <test@test.com>'
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass()
                ],
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ],
                    'to' => [
                        [
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        ],
                        'test@test.com',
                        'Test <test@test.com>'
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ]
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
    public function testExecuteWithoutTemplateEntity()
    {
        $language = 'de';
        $options = [
            'from' => 'test@test.com',
            'to' => 'test@test.com',
            'template' => 'test',
            'subject' => 'subject',
            'body' => 'body',
            'entity' => new \stdClass(),
        ];
        $this->expectsEntityClass($options['entity']);
        $context = [];
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));
        $this->entityNameResolver->expects($this->any())
            ->method('getName')
            ->will(
                $this->returnCallback(
                    function () {
                        return '_Formatted';
                    }
                )
            );

        $this->languageProvider->expects($this->once())
            ->method('getPreferredLanguage')
            ->with($options['to'])
            ->willReturn($language);
        $this->objectRepository->expects($this->once())
            ->method('findOneLocalized')
            ->with(new EmailTemplateCriteria($options['template'], get_class($options['entity'])), $language)
            ->willReturn(null);

        $this->emailTemplate->expects($this->never())
            ->method('getType')
            ->willReturn('txt');
        $this->renderer->expects($this->never())
            ->method('compileMessage')
            ->willReturn([$options['subject'], $options['body']]);

        $emailEntity = $this->getMockBuilder('\Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailProcessor->expects($this->never())
            ->method('process');
        if (array_key_exists('attribute', $options)) {
            $this->contextAccessor->expects($this->once())
                ->method('setValue')
                ->with($context, $options['attribute'], $emailEntity);
        }
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function testExecuteWithInvalidEmail()
    {
        $this->expectException('\Symfony\Component\Validator\Exception\ValidatorException');
        $this->expectExceptionMessage('test');
        $options = [
            'from' => 'invalidemailaddress',
            'to' => 'test@test.com',
            'template' => 'test',
            'subject' => 'subject',
            'body' => 'body',
            'entity' => new \stdClass(),
        ];
        $this->expectsEntityClass($options['entity']);
        $context = [];
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));
        $this->entityNameResolver->expects($this->any())
            ->method('getName')
            ->will(
                $this->returnCallback(
                    function () {
                        return '_Formatted';
                    }
                )
            );

        $violationList = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolationList')
            ->disableOriginalConstructor()
            ->getMock();
        $violationList->expects($this->once())
            ->method('count')
            ->willReturn(1);
        $violationListInterface =
            $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolationInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $violationListInterface->expects($this->once())
            ->method('getMessage')
            ->willReturn('test');
        $violationList->expects($this->once())
            ->method('get')
            ->willReturn($violationListInterface);
        $this->validator->expects($this->once())
            ->method('validate')
            ->willReturn($violationList);

        $this->objectRepository->expects($this->never())
            ->method('findOneLocalized');

        $this->emailTemplate->expects($this->never())
            ->method('getType');
        $this->renderer->expects($this->never())
            ->method('compileMessage');

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function testExecuteWithProcessException()
    {
        $language = 'de';
        $options = [
            'from' => 'test@test.com',
            'to' => 'test@test.com',
            'template' => 'test',
            'subject' => 'subject',
            'body' => 'body',
            'entity' => new \stdClass(),
        ];
        $this->expectsEntityClass($options['entity']);
        $context = [];
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));
        $this->entityNameResolver->expects($this->any())
            ->method('getName')
            ->will(
                $this->returnCallback(
                    function () {
                        return '_Formatted';
                    }
                )
            );

        $this->languageProvider->expects($this->once())
            ->method('getPreferredLanguage')
            ->with($options['to'])
            ->willReturn($language);
        $this->objectRepository->expects($this->once())
            ->method('findOneLocalized')
            ->with(new EmailTemplateCriteria($options['template'], get_class($options['entity'])), $language)
            ->willReturn($this->emailTemplate);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn('txt');

        $this->renderer->expects($this->once())
            ->method('compileMessage')
            ->willReturn(['test', 'test']);

        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email'))
            ->willThrowException(new \Swift_SwiftException('The email was not delivered.'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Workflow send email template action.');

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @dataProvider executeOptionsDataProvider
     * @param array $options
     * @param string|EmailHolderInterface $expectedForPreferredLanguage
     * @param array $expected
     * @param string $language
     */
    public function testExecute(
        array $options,
        $expectedForPreferredLanguage,
        array $expected,
        string $language
    ) {
        $this->expectsEntityClass($options['entity']);
        $context = [];
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));
        $this->entityNameResolver->expects($this->any())
            ->method('getName')
            ->will(
                $this->returnCallback(
                    function () {
                        return '_Formatted';
                    }
                )
            );

        $this->languageProvider->expects($this->once())
            ->method('getPreferredLanguage')
            ->with($expectedForPreferredLanguage)
            ->willReturn($language);
        $this->objectRepository->expects($this->once())
            ->method('findOneLocalized')
            ->with(new EmailTemplateCriteria($options['template'], get_class($options['entity'])), $language)
            ->willReturn($this->emailTemplate);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn('txt');
        $this->renderer->expects($this->once())
            ->method('compileMessage')
            ->willReturn([$expected['subject'], $expected['body']]);

        $self = $this;
        $emailUserEntity = $this->getMockBuilder('\Oro\Bundle\EmailBundle\Entity\EmailUser')
            ->disableOriginalConstructor()
            ->setMethods(['getEmail'])
            ->getMock();
        $emailEntity = $this->createMock('\Oro\Bundle\EmailBundle\Entity\Email');
        $emailUserEntity->expects($this->any())
            ->method('getEmail')
            ->willReturn($emailEntity);
        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email'))
            ->will(
                $this->returnCallback(
                    function (Email $model) use ($emailUserEntity, $expected, $self) {
                        $self->assertEquals($expected['body'], $model->getBody());
                        $self->assertEquals($expected['subject'], $model->getSubject());
                        $self->assertEquals($expected['from'], $model->getFrom());
                        $self->assertEquals($expected['to'], $model->getTo());
                        $self->assertEquals('txt', $model->getType());

                        return $emailUserEntity;
                    }
                )
            );
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
    public function executeOptionsDataProvider()
    {
        $nameMock = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\FirstNameInterface')
            ->getMock();
        $nameMock->expects($this->any())
            ->method('getFirstName')
            ->will($this->returnValue('NAME'));
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
                        'email' => 'test@test.com'
                    ],
                    'to' => [
                        'name' => 'Test',
                        'email' => 'test@test.com'
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
                        'email' => 'test@test.com'
                    ],
                    'to' => [
                        'name' => $nameMock,
                        'email' => 'test@test.com'
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
                'de'
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
        $enLanguage = 'en';
        $deLanguage = 'de';
        $frLanguage = 'fr';
        $enTemplate = new EmailTemplate('', '', 'txt');
        $deTemplate = new EmailTemplate('', '', 'txt');
        $frTemplate = new EmailTemplate('', '', 'txt');
        $options = [
            'from' => 'from@test.com',
            'to' => [
                $toEmail1,
                $toEmail2,
                ' '
            ],
            'template' => 'test',
            'entity' => new \stdClass(),
            'attribute' => 'attr',
            'recipients' => [
                $recipient1,
                $recipient2,
            ]
        ];
        $this->expectsEntityClass($options['entity']);
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));

        $this->languageProvider->expects($this->exactly(4))
            ->method('getPreferredLanguage')
            ->withConsecutive(
                [$toEmail1],
                [$toEmail2],
                [$recipient1],
                [$recipient2]
            )
            ->willReturnOnConsecutiveCalls(
                $deLanguage,
                $enLanguage,
                $deLanguage,
                $frLanguage
            );
        $this->objectRepository->expects($this->exactly(3))
            ->method('findOneLocalized')
            ->withConsecutive(
                [new EmailTemplateCriteria($options['template'], get_class($options['entity'])), $enLanguage],
                [new EmailTemplateCriteria($options['template'], get_class($options['entity'])), $deLanguage],
                [new EmailTemplateCriteria($options['template'], get_class($options['entity'])), $frLanguage]
            )
            ->willReturnOnConsecutiveCalls(
                $enTemplate,
                $deTemplate,
                $frTemplate
            );

        $messages = [
            'en' => ['subject_en', 'body_en'],
            'de' => ['subject_de', 'body_de'],
            'fr' => ['subject_fr', 'body_fr'],
        ];
        $this->renderer->expects($this->exactly(3))
            ->method('compileMessage')
            ->withConsecutive(
                [$enTemplate, ['entity' => $options['entity']]],
                [$deTemplate, ['entity' => $options['entity']]],
                [$frTemplate, ['entity' => $options['entity']]]
            )
            ->willReturnOnConsecutiveCalls(
                $messages['en'],
                $messages['de'],
                $messages['fr']
            );

        $email = new EmailEntity();
        $this->emailProcessor->expects($this->at(1))
            ->method('process')
            ->willReturnCallback(
                function (Email $model) use ($messages, $toEmail2, $email) {
                    $this->assertEquals($messages['en'][0], $model->getSubject());
                    $this->assertEquals($messages['en'][1], $model->getBody());
                    $this->assertEquals([$toEmail2], $model->getTo());
                    $this->assertEquals('txt', $model->getType());
                    $emailUser = new EmailUser();
                    $emailUser->setEmail($email);

                    return $emailUser;
                }
            );
        $this->emailProcessor->expects($this->at(3))
            ->method('process')
            ->willReturnCallback(
                function (Email $model) use ($messages, $toEmail1, $recipientEmail1) {
                    $this->assertEquals($messages['de'][0], $model->getSubject());
                    $this->assertEquals($messages['de'][1], $model->getBody());
                    $this->assertEquals([$toEmail1, $recipientEmail1], $model->getTo());
                    $this->assertEquals('txt', $model->getType());

                    return new EmailUser();
                }
            );
        $this->emailProcessor->expects($this->at(5))
            ->method('process')
            ->willReturnCallback(
                function (Email $model) use ($messages, $recipientEmail2) {
                    $this->assertEquals($messages['fr'][0], $model->getSubject());
                    $this->assertEquals($messages['fr'][1], $model->getBody());
                    $this->assertEquals([$recipientEmail2], $model->getTo());
                    $this->assertEquals('txt', $model->getType());

                    return new EmailUser();
                }
            );

        $context = [];
        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with($context, $options['attribute'], $email);
        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
