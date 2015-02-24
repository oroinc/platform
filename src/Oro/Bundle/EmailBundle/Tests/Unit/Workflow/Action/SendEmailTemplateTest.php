<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Workflow\Action\SendEmail;
use Oro\Bundle\EmailBundle\Workflow\Action\SendEmailTemplate;

class SendEmailTemplateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailProcessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $nameFormatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    /**
     * @var EmailRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $renderer;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManager;

    /**
     * @var EmailTemplateRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectRepository;

    /**
     * @var EmailTemplateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailTemplate;

    /**
     * @var SendEmail
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ContextAccessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailProcessor = $this->getMockBuilder('Oro\Bundle\EmailBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->renderer = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRenderer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectRepository = $this->getMockBuilder(
            'Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->objectRepository);

        $this->emailTemplate = $this->getMock('Oro\Bundle\EmailBundle\Model\EmailTemplateInterface');

        $this->action = new SendEmailTemplate(
            $this->contextAccessor,
            $this->renderer,
            $this->emailProcessor,
            $this->objectManager,
            new EmailAddressHelper(),
            $this->nameFormatter
        );

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
        $this->setExpectedException($exceptionName, $exceptionMessage);
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
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'From parameter is required'
            ],
            'no from email' => [
                'options' => [
                    'to' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'from' => ['name' => 'Test']
                ],
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ],
            'no to' => [
                'options' => ['from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass()],
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'To parameter is required'
            ],
            'no to email' => [
                'options' => [
                    'from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'to' => ['name' => 'Test']
                ],
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ],
            'no to email in one of addresses' => [
                'options' => [
                    'from' => 'test@test.com', 'template' => 'test', 'entity' => new \stdClass(),
                    'to' => ['test@test.com', ['name' => 'Test']]
                ],
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ],
            'no template' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'entity' => new \stdClass()],
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
                'exceptionMessage' => 'Template parameter is required'
            ],
            'no entity' => [
                'options' => ['from' => 'test@test.com', 'to' => 'test@test.com', 'template' => 'test'],
                'exceptionName' => '\Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException',
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
                    'entity' => new \stdClass()
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
                    'entity' => new \stdClass()
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
                    'entity' => new \stdClass()
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
                    'entity' => new \stdClass()
                ]
            ]
        ];
    }

    public function testExecuteWithoutTemplaitEntity()
    {
        $this->setExpectedException('\Doctrine\ORM\EntityNotFoundException', 'Entity was not found.');
        $options = [
            'from' => 'test@test.com',
            'to' => 'test@test.com',
            'template' => 'test',
            'subject' => 'subject',
            'body' => 'body',
            'entity' => new \stdClass(),
        ];
        $context = [];
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));
        $this->nameFormatter->expects($this->any())
            ->method('format')
            ->will(
                $this->returnCallback(
                    function () {
                        return '_Formatted';
                    }
                )
            );

        $this->objectRepository->expects($this->once())
            ->method('findByName')
            ->with($options['template'])
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

    /**
     * @dataProvider executeOptionsDataProvider
     * @param array $options
     * @param array $expected
     */
    public function testExecute($options, $expected)
    {
        $context = [];
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->will($this->returnArgument(1));
        $this->nameFormatter->expects($this->any())
            ->method('format')
            ->will(
                $this->returnCallback(
                    function () {
                        return '_Formatted';
                    }
                )
            );

        $this->objectRepository->expects($this->once())
            ->method('findByName')
            ->with($options['template'])
            ->willReturn($this->emailTemplate);

        $this->emailTemplate->expects($this->once())
            ->method('getType')
            ->willReturn('txt');
        $this->renderer->expects($this->once())
            ->method('compileMessage')
            ->willReturn([$expected['subject'], $expected['body']]);

        $self = $this;
        $emailEntity = $this->getMockBuilder('\Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email'))
            ->will(
                $this->returnCallback(
                    function (Email $model) use ($emailEntity, $expected, $self) {
                        $self->assertEquals($expected['body'], $model->getBody());
                        $self->assertEquals($expected['subject'], $model->getSubject());
                        $self->assertEquals($expected['from'], $model->getFrom());
                        $self->assertEquals($expected['to'], $model->getTo());

                        return $emailEntity;
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
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ]
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
                    'subject' => 'Test subject',
                    'body' => 'Test body',
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
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => 'Test <test@test.com>',
                    'to' => ['Test <test@test.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
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
                    'template' => 'test',
                    'entity' => new \stdClass(),
                ],
                [
                    'from' => '_Formatted <test@test.com>',
                    'to' => ['_Formatted <test@test.com>'],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
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
                    'entity' => new \stdClass(),
                    'attribute' => 'attr'
                ],
                [
                    'from' => 'Test <test@test.com>',
                    'to' => [
                        'Test <test@test.com>',
                        'test@test.com',
                        'Test <test@test.com>'
                    ],
                    'subject' => 'Test subject',
                    'body' => 'Test body',
                ]
            ]
        ];
    }
}
