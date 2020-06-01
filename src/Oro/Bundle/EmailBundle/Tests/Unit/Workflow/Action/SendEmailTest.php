<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action;

use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestEmailOrigin;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EmailBundle\Workflow\Action\SendEmail;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SendEmailTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|MockObject */
    protected $contextAccessor;

    /** @var Processor|MockObject */
    protected $emailProcessor;

    /** @var EntityNameResolver|MockObject */
    protected $entityNameResolver;

    /** @var EventDispatcher|MockObject */
    protected $dispatcher;

    /** @var EmailOriginHelper|MockObject */
    protected $emailOriginHelper;

    /** @var SendEmail */
    protected $action;

    /** @var LoggerInterface|MockObject */
    protected $logger;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->emailProcessor = $this->createMock(Processor::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->emailOriginHelper = $this->createMock(EmailOriginHelper::class);

        $this->action = new class(
            $this->contextAccessor,
            $this->emailProcessor,
            new EmailAddressHelper(),
            $this->entityNameResolver,
            $this->emailOriginHelper
        ) extends SendEmail {
            public function xgetOptions(): array
            {
                return $this->options;
            }
        };

        $this->action->setDispatcher($this->dispatcher);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->action->setLogger($this->logger);
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
                'options' => ['to' => 'test@test.com', 'subject' => 'test', 'body' => 'test'],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'From parameter is required'
            ],
            'no from email' => [
                'options' => [
                    'to' => 'test@test.com', 'subject' => 'test', 'body' => 'test',
                    'from' => ['name' => 'Test']
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ],
            'no to' => [
                'options' => ['from' => 'test@test.com', 'subject' => 'test', 'body' => 'test'],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'To parameter is required'
            ],
            'no to email' => [
                'options' => [
                    'from' => 'test@test.com', 'subject' => 'test', 'body' => 'test',
                    'to' => ['name' => 'Test']
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ],
            'no to email in one of addresses' => [
                'options' => [
                    'from' => 'test@test.com', 'subject' => 'test', 'body' => 'test',
                    'to' => ['test@test.com', ['name' => 'Test']]
                ],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ],
            'no subject' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'body' => 'test'],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Subject parameter is required'
            ],
            'no body' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'subject' => 'test'],
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Body parameter is required'
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
        static::assertSame($this->action, $this->action->initialize($options));
        static::assertEquals($expected, $this->action->xgetOptions());
    }

    public function optionsDataProvider()
    {
        return [
            'simple' => [
                [
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'subject' => 'test',
                    'body' => 'test'
                ],
                [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                    'subject' => 'test',
                    'body' => 'test'
                ]
            ],
            'simple with name' => [
                [
                    'from' => 'Test <test@test.com>',
                    'to' => 'Test <test@test.com>',
                    'subject' => 'test',
                    'body' => 'test'
                ],
                [
                    'from' => 'Test <test@test.com>',
                    'to' => ['Test <test@test.com>'],
                    'subject' => 'test',
                    'body' => 'test'
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
                    'subject' => 'test',
                    'body' => 'test'
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
                    'subject' => 'test',
                    'body' => 'test'
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
                    'subject' => 'test',
                    'body' => 'test'
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
                    'subject' => 'test',
                    'body' => 'test'
                ]
            ]
        ];
    }

    /**
     * @dataProvider executeOptionsDataProvider
     * @param array $options
     * @param array $expected
     */
    public function testExecute($options, $expected)
    {
        $context = [];
        $this->contextAccessor->method('getValue')->willReturnArgument(1);
        $this->entityNameResolver->expects(static::any())
            ->method('getName')
            ->willReturnCallback(
                function () {
                    return '_Formatted';
                }
            );

        $emailEntity = new EmailEntity();
        $emailUserEntity = $this->createMock(EmailUser::class);
        $emailUserEntity->method('getEmail')->willReturn($emailEntity);

        $emailOrigin = new TestEmailOrigin();
        $this->emailOriginHelper->expects(static::once())
            ->method('getEmailOrigin')
            ->with($expected['from'], null)
            ->willReturn($emailOrigin);

        $this->emailProcessor->expects(static::once())
            ->method('process')
            ->with(static::isInstanceOf(Email::class), $emailOrigin)
            ->willReturnCallback(
                function (Email $model) use ($emailUserEntity, $expected) {
                    static::assertEquals($expected['body'], $model->getBody());
                    static::assertEquals($expected['subject'], $model->getSubject());
                    static::assertEquals($expected['from'], $model->getFrom());
                    static::assertEquals($expected['to'], $model->getTo());

                    return $emailUserEntity;
                }
            );
        if (array_key_exists('attribute', $options)) {
            $this->contextAccessor->expects(static::once())
                ->method('setValue')
                ->with($context, $options['attribute'], $emailEntity);
        }
        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function executeOptionsDataProvider()
    {
        $nameMock = $this->getMockBuilder(FirstNameInterface::class)->getMock();
        $nameMock->method('getFirstName')->willReturn('NAME');

        return [
            'simple' => [
                [
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'subject' => 'test',
                    'body' => 'test'
                ],
                [
                    'from' => 'test@test.com',
                    'to' => ['test@test.com'],
                    'subject' => 'test',
                    'body' => 'test'
                ]
            ],
            'simple with name' => [
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => '"Test" <test@test.com>',
                    'subject' => 'test',
                    'body' => 'test'
                ],
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
                    'subject' => 'test',
                    'body' => 'test'
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
                    'subject' => 'test',
                    'body' => 'test'
                ],
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => ['"Test" <test@test.com>'],
                    'subject' => 'test',
                    'body' => 'test'
                ]
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
                    'subject' => 'test',
                    'body' => 'test'
                ],
                [
                    'from' => '"_Formatted" <test@test.com>',
                    'to' => ['"_Formatted" <test@test.com>'],
                    'subject' => 'test',
                    'body' => 'test'
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
                        '"Test" <test@test.com>'
                    ],
                    'subject' => 'test',
                    'body' => 'test',
                    'attribute' => 'attr'
                ],
                [
                    'from' => '"Test" <test@test.com>',
                    'to' => [
                        '"Test" <test@test.com>',
                        'test@test.com',
                        '"Test" <test@test.com>'
                    ],
                    'subject' => 'test',
                    'body' => 'test'
                ]
            ]
        ];
    }

    public function testExecuteWithProcessException()
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
        $this->contextAccessor->method('getValue')->willReturnArgument(1);
        $this->entityNameResolver->expects(static::any())
            ->method('getName')
            ->willReturnCallback(
                function () {
                    return '_Formatted';
                }
            );

        $emailUserEntity = $this->getMockBuilder(EmailUser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEmail'])
            ->getMock();
        $emailEntity = $this->createMock(EmailEntity::class);
        $emailUserEntity->method('getEmail')->willReturn($emailEntity);

        $this->emailProcessor->expects(static::once())
            ->method('process')
            ->with(static::isInstanceOf(Email::class))
            ->willThrowException(new \Swift_SwiftException('The email was not delivered.'));

        $this->logger->expects(static::once())
            ->method('error')
            ->with('Workflow send email action.');

        $this->action->initialize($options);
        $this->action->execute($context);
    }
}
