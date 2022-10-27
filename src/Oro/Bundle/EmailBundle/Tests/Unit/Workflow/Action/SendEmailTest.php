<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action;

use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Sender\EmailModelSender;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EmailBundle\Workflow\Action\SendEmail;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SendEmailTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private ContextAccessor|\PHPUnit\Framework\MockObject\MockObject $contextAccessor;

    private ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject $validator;

    private EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject $entityNameResolver;

    private EmailModelSender|\PHPUnit\Framework\MockObject\MockObject $emailModelSender;

    private EmailOriginHelper|\PHPUnit\Framework\MockObject\MockObject $emailOriginHelper;

    private SendEmail $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->emailModelSender = $this->createMock(EmailModelSender::class);
        $this->emailOriginHelper = $this->createMock(EmailOriginHelper::class);
        $dispatcher = $this->createMock(EventDispatcher::class);

        $this->action = new SendEmail(
            $this->contextAccessor,
            $this->validator,
            new EmailAddressHelper(),
            $this->entityNameResolver,
            $this->emailModelSender,
            $this->emailOriginHelper
        );
        $this->action->setDispatcher($dispatcher);

        $this->setUpLoggerMock($this->action);

        $this->entityNameResolver->expects(self::any())
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
                'options' => ['to' => 'test@test.com', 'subject' => 'test', 'body' => 'test'],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'From parameter is required',
            ],
            'no from email' => [
                'options' => [
                    'to' => 'test@test.com',
                    'subject' => 'test',
                    'body' => 'test',
                    'from' => ['name' => 'Test'],
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Email parameter is required',
            ],
            'no to' => [
                'options' => ['from' => 'test@test.com', 'subject' => 'test', 'body' => 'test'],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'To parameter is required',
            ],
            'no to email' => [
                'options' => [
                    'from' => 'test@test.com',
                    'subject' => 'test',
                    'body' => 'test',
                    'to' => ['name' => 'Test'],
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Email parameter is required',
            ],
            'no to email in one of addresses' => [
                'options' => [
                    'from' => 'test@test.com',
                    'subject' => 'test',
                    'body' => 'test',
                    'to' => ['test@test.com', ['name' => 'Test']],
                ],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Email parameter is required',
            ],
            'no subject' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'body' => 'test'],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Subject parameter is required',
            ],
            'no body' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'subject' => 'test'],
                'exceptionName' => InvalidParameterException::class,
                'exceptionMessage' => 'Body parameter is required',
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

    public function optionsDataProvider(): array
    {
        return [
            'simple' => [
                [
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'subject' => 'test',
                    'body' => 'test',
                ],
                [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                    'subject' => 'test',
                    'body' => 'test',
                ],
            ],
            'simple with name' => [
                [
                    'from' => 'Test <test@test.com>',
                    'to' => 'Test <test@test.com>',
                    'subject' => 'test',
                    'body' => 'test',
                ],
                [
                    'from' => 'Test <test@test.com>',
                    'to' => ['Test <test@test.com>'],
                    'subject' => 'test',
                    'body' => 'test',
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
                    'subject' => 'test',
                    'body' => 'test',
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
                    'subject' => 'test',
                    'body' => 'test',
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
                    'subject' => 'test',
                    'body' => 'test',
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
                    'subject' => 'test',
                    'body' => 'test',
                ],
            ],
        ];
    }

    /**
     * @dataProvider executeOptionsDataProvider
     */
    public function testExecute(array $options, array $expected): void
    {
        $context = [];

        $emailEntity = new EmailEntity();
        $emailUserEntity = $this->createMock(EmailUser::class);
        $emailUserEntity->expects(self::any())
            ->method('getEmail')
            ->willReturn($emailEntity);

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects(self::once())
            ->method('getEmailOrigin')
            ->with($expected['from'], null)
            ->willReturn($emailOrigin);

        $this->emailModelSender->expects(self::once())
            ->method('send')
            ->with(self::isInstanceOf(EmailModel::class), $emailOrigin)
            ->willReturnCallback(function (EmailModel $model) use ($emailUserEntity, $expected) {
                self::assertEquals($expected['body'], $model->getBody());
                self::assertEquals($expected['subject'], $model->getSubject());
                self::assertEquals($expected['from'], $model->getFrom());
                self::assertEquals($expected['to'], $model->getTo());

                return $emailUserEntity;
            });
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

        return [
            'simple' => [
                [
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'subject' => 'test',
                    'body' => 'test',
                ],
                [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                    'subject' => 'test',
                    'body' => 'test',
                ],
            ],
            'simple with name' => [
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => '"Test" <test@test.com>',
                    'subject' => 'test',
                    'body' => 'test',
                ],
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
                    'subject' => 'test',
                    'body' => 'test',
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
                    'subject' => 'test',
                    'body' => 'test',
                ],
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
                    'subject' => 'test',
                    'body' => 'test',
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
                    'subject' => 'test',
                    'body' => 'test',
                ],
                [
                    'from' => '"_Formatted" <test@test.com>',
                    'to' => ['"_Formatted" <test@test.com>'],
                    'subject' => 'test',
                    'body' => 'test',
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
                        '"Test" <test@test.com>',
                    ],
                    'subject' => 'test',
                    'body' => 'test',
                    'attribute' => 'attr',
                ],
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => [
                        '"Test" <test@test.com>',
                        'test@test.com',
                        '"Test" <test@test.com>',
                    ],
                    'subject' => 'test',
                    'body' => 'test',
                ],
            ],
        ];
    }

    public function testExecuteWithProcessException(): void
    {
        $options = [
            'from' => 'test@test.com',
            'to' => 'test@test.com',
            'template' => 'test',
            'subject' => 'subject',
            'body' => 'body',
            'entity' => new \stdClass(),
        ];

        $context = [];
        $this->entityNameResolver->expects(self::any())
            ->method('getName')
            ->willReturnCallback(function () {
                return '_Formatted';
            });

        $emailUserEntity = $this->getMockBuilder(EmailUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEmail'])
            ->getMock();
        $emailEntity = $this->createMock(EmailEntity::class);
        $emailUserEntity->expects(self::any())
            ->method('getEmail')
            ->willReturn($emailEntity);

        $exception = new \RuntimeException('Sample exception');
        $this->emailModelSender->expects(self::once())
            ->method('send')
            ->with(self::isInstanceOf(EmailModel::class))
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->willReturnCallback(static function (string $message, array $context) use ($exception) {
                self::assertEquals('Failed to send an email to test@test.com: Sample exception', $message);
                self::assertSame($exception, $context['exception']);
                self::assertInstanceOf(EmailModel::class, $context['emailModel']);
            });

        $this->action->initialize($options);
        $this->action->execute($context);
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
        $this->emailModelSender->expects(self::never())
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
}
