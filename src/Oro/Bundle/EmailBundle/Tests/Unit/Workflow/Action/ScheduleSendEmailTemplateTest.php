<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action;

use Oro\Bundle\EmailBundle\Async\Topic\SendEmailTemplateTopic;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Workflow\Action\ScheduleSendEmailTemplate;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ScheduleSendEmailTemplateTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject $validator;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private ScheduleSendEmailTemplate $action;

    protected function setUp(): void
    {
        $contextAccessor = $this->createMock(ContextAccessor::class);
        $contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->validator = $this->createMock(ValidatorInterface::class);
        $entityNameResolver = $this->createMock(EntityNameResolver::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $dispatcher = $this->createMock(EventDispatcher::class);

        $this->action = new ScheduleSendEmailTemplate(
            $contextAccessor,
            $this->validator,
            new EmailAddressHelper(),
            $entityNameResolver,
            $doctrineHelper,
            $this->messageProducer
        );
        $this->action->setDispatcher($dispatcher);

        $this->setUpLoggerMock($this->action);

        $entityNameResolver->expects(self::any())
            ->method('getName')
            ->willReturnCallback(static fn () => '_Formatted');

        $doctrineHelper->expects(self::any())
            ->method('getEntityClass')
            ->willReturn(\stdClass::class);

        $doctrineHelper->expects(self::any())
            ->method('getSingleEntityIdentifier')
            ->willReturn(42);
    }


    /**
     * @dataProvider initializeExceptionDataProvider
     */
    public function testInitializeException(array $options, string $exceptionName, string $exceptionMessage): void
    {
        $this->expectException($exceptionName);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    public function initializeExceptionDataProvider(): array
    {
        return [
            'no from' => [
                'options' => ['to' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass()],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'From parameter is required',
            ],
            'no from email' => [
                'options' => [
                    'to' => 'test@test.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'from' => ['name' => 'Test'],
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Email parameter is required',
            ],
            'no to or recipients' => [
                'options' => ['from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass()],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Need to specify "to" or "recipients" parameters',
            ],
            'no to email' => [
                'options' => [
                    'from' => 'test@test.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'to' => ['name' => 'Test'],
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Email parameter is required',
            ],
            'recipients in not an array' => [
                'options' => [
                    'from' => 'test@test.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => 'some@recipient.com',
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Recipients parameter must be an array, string given',
            ],
            'no to email in one of addresses' => [
                'options' => [
                    'from' => 'test@test.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'to' => ['test@test.com', ['name' => 'Test']],
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Email parameter is required',
            ],
            'no template' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'entity' => new \stdClass()],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Template parameter is required',
            ],
            'no entity' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'template' => 'test'],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Entity parameter is required',
            ],
        ];
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testInitialize(array $options, array $expected): void
    {
        self::assertSame($this->action, $this->action->initialize($options));
        self::assertEquals($expected, ReflectionUtil::getPropertyValue($this->action, 'options'));
    }

    /**
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

    private function mockValidatorViolations(string $violationMessage): void
    {
        $violationList = $this->createMock(ConstraintViolationListInterface::class);
        $violation = $this->createMock(ConstraintViolationInterface::class);
        $violation->expects(self::once())
            ->method('getMessage')
            ->willReturn($violationMessage);
        $violationList->expects(self::once())
            ->method('get')
            ->willReturn($violation);
        $violationList->expects(self::once())
            ->method('count')
            ->willReturn(1);
        $this->validator->expects(self::once())
            ->method('validate')
            ->willReturn($violationList);
    }

    public function testExecuteWithInvalidEmail(): void
    {
        $this->messageProducer->expects(self::never())
            ->method(self::anything());

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

        $this->mockValidatorViolations('violation');

        $this->expectException(ValidatorException::class);
        $this->expectExceptionMessage("Validating \"From\" email (invalidemailaddress):\nviolation");

        $this->action->execute([]);
    }

    /**
     * @dataProvider executeOptionsDataProvider
     */
    public function testExecute(array $options, array $expected): void
    {
        $context = [];

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(
                SendEmailTemplateTopic::getName(),
                [
                    'from' => $expected['from'],
                    'templateName' => $options['template'],
                    'recipients' => $expected['to'],
                    'entity' => [\stdClass::class, 42],
                ]
            );

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function executeOptionsDataProvider(): array
    {
        $nameMock = $this->createMock(FirstNameInterface::class);
        $nameMock->expects(self::any())
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
                [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                ],
            ],
            'simple with name' => [
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => '"Test" <test@test.com>',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
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
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
                ],
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
                [
                    'from' => '"_Formatted" <test@test.com>',
                    'to' => ['"_Formatted" <test@test.com>'],
                ],
            ],
            'with recipients' => [
                [
                    'from' => 'test@test.com',
                    'recipients' => [$recipient],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => 'test@test.com',
                    'to' => ['recipient@test.com'],
                ],
            ],
        ];
    }
}
