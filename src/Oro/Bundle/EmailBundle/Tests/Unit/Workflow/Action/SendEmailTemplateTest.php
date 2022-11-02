<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\From;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Workflow\Action\SendEmailTemplate;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler\Stub\EmailHolderStub;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SendEmailTemplateTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ContextAccessor|\PHPUnit\Framework\MockObject\MockObject $contextAccessor;

    private ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject $validator;

    private AggregatedEmailTemplatesSender|\PHPUnit\Framework\MockObject\MockObject $aggregatedEmailTemplatesSender;

    private SendEmailTemplate $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->validator = $this->createMock(ValidatorInterface::class);
        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->aggregatedEmailTemplatesSender = $this->createMock(AggregatedEmailTemplatesSender::class);
        $dispatcher = $this->createMock(EventDispatcher::class);

        $this->action = new SendEmailTemplate(
            $this->contextAccessor,
            $this->validator,
            new EmailAddressHelper(),
            $entityNameResolver,
            $this->aggregatedEmailTemplatesSender
        );
        $this->action->setDispatcher($dispatcher);

        $this->setUpLoggerMock($this->action);

        $entityNameResolver->expects(self::any())
            ->method('getName')
            ->willReturnCallback(static fn () => '_Formatted');
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
                'options' => ['to' => 'test@example.com', 'template' => 'test', 'entity' => new \stdClass()],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'From parameter is required',
            ],
            'no from email' => [
                'options' => [
                    'to' => 'test@example.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'from' => ['name' => 'Test'],
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Email parameter is required',
            ],
            'no to or recipients' => [
                'options' => ['from' => 'test@example.com', 'template' => 'test', 'entity' => new \stdClass()],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Need to specify "to" or "recipients" parameters',
            ],
            'no to email' => [
                'options' => [
                    'from' => 'test@example.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'to' => ['name' => 'Test'],
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Email parameter is required',
            ],
            'recipients in not an array' => [
                'options' => [
                    'from' => 'test@example.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => 'some@recipient.com',
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Recipients parameter must be an array, string given',
            ],
            'no to email in one of addresses' => [
                'options' => [
                    'from' => 'test@example.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'to' => ['test@example.com', ['name' => 'Test']],
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Email parameter is required',
            ],
            'no template' => [
                'options' => ['from' => 'test@example.com', 'to' => 'test@example.com', 'entity' => new \stdClass()],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Template parameter is required',
            ],
            'no entity' => [
                'options' => ['from' => 'test@example.com', 'to' => 'test@example.com', 'template' => 'test'],
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
                    'from' => 'test@example.com',
                    'to' => 'test@example.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => 'test@example.com',
                    'to' => ['test@example.com'],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ],
            ],
            'simple with name' => [
                [
                    'from' => 'Test <test@example.com>',
                    'to' => 'Test <test@example.com>',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => 'Test <test@example.com>',
                    'to' => ['Test <test@example.com>'],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ],
            ],
            'extended' => [
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@example.com',
                    ],
                    'to' => [
                        'name' => 'Test',
                        'email' => 'test@example.com',
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@example.com',
                    ],
                    'to' => [
                        [
                            'name' => 'Test',
                            'email' => 'test@example.com',
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
                        'email' => 'test@example.com',
                    ],
                    'to' => [
                        [
                            'name' => 'Test',
                            'email' => 'test@example.com',
                        ],
                        'test@example.com',
                        'Test <test@example.com>',
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@example.com',
                    ],
                    'to' => [
                        [
                            'name' => 'Test',
                            'email' => 'test@example.com',
                        ],
                        'test@example.com',
                        'Test <test@example.com>',
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                    'recipients' => [],
                ],
            ],
            'with recipients' => [
                [
                    'from' => 'test@example.com',
                    'to' => 'test2@example.com',
                    'recipients' => [new EmailHolderStub()],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => 'test@example.com',
                    'to' => ['test2@example.com'],
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
        $this->aggregatedEmailTemplatesSender->expects(self::never())
            ->method(self::anything());

        $this->action->initialize(
            [
                'from' => 'invalidemailaddress',
                'to' => 'test@example.com',
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
    public function testExecute(array $options, string|object $recipient, array $expected): void
    {
        $context = [];
        if (!$recipient instanceof EmailHolderInterface) {
            $recipient = new Recipient($recipient);
        }

        $emailEntity = $this->createMock(Email::class);

        $emailUserEntity = $this->createMock(EmailUser::class);
        $emailUserEntity->expects(self::any())
            ->method('getEmail')
            ->willReturn($emailEntity);

        $this->aggregatedEmailTemplatesSender->expects(self::once())
            ->method('send')
            ->with(new \stdClass(), [$recipient], $expected['from'], 'test')
            ->willReturn([$emailUserEntity]);

        if (array_key_exists('attribute', $options)) {
            $this->contextAccessor->expects(self::once())
                ->method('setValue')
                ->with($context, $options['attribute'], $emailEntity);
        }

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
        $recipient = new EmailHolderStub('recipient@example.com');

        return [
            'simple' => [
                [
                    'from' => 'test@example.com',
                    'to' => 'test@example.com',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                'test@example.com',
                [
                    'from' => From::emailAddress('test@example.com'),
                    'to' => ['test@example.com'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'simple with name' => [
                [
                    'from' => '"Test" <test@example.com>',
                    'to' => '"Test" <test@example.com>',
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                '"Test" <test@example.com>',
                [
                    'from' => From::emailAddress('"Test" <test@example.com>'),
                    'to' => ['"Test" <test@example.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'extended' => [
                [
                    'from' => [
                        'name' => 'Test',
                        'email' => 'test@example.com',
                    ],
                    'to' => [
                        'name' => 'Test',
                        'email' => 'test@example.com',
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                '"Test" <test@example.com>',
                [
                    'from' => From::emailAddress('"Test" <test@example.com>'),
                    'to' => ['"Test" <test@example.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'extended with name formatting' => [
                [
                    'from' => [
                        'name' => $nameMock,
                        'email' => 'test@example.com',
                    ],
                    'to' => [
                        'name' => $nameMock,
                        'email' => 'test@example.com',
                    ],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                '"_Formatted" <test@example.com>',
                [
                    'from' => From::emailAddress('"_Formatted" <test@example.com>'),
                    'to' => ['"_Formatted" <test@example.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
            'with recipients' => [
                [
                    'from' => 'test@example.com',
                    'recipients' => [$recipient],
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                $recipient,
                [
                    'from' => From::emailAddress('test@example.com'),
                    'to' => ['recipient@example.com'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ],
                'de',
            ],
        ];
    }
}
