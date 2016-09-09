<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Workflow\Action\SendEmail;

class SendEmailTest extends \PHPUnit_Framework_TestCase
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
    protected $entityNameResolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    /**
     * @var SendEmail
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMockBuilder('Oro\Component\Action\Model\ContextAccessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailProcessor = $this->getMockBuilder('Oro\Bundle\EmailBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityNameResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\Provider\EntityNameResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action = new SendEmail(
            $this->contextAccessor,
            $this->emailProcessor,
            new EmailAddressHelper(),
            $this->entityNameResolver
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
        return array(
            'no from' => array(
                'options' => array('to' => 'test@test.com', 'subject' => 'test', 'body' => 'test'),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'From parameter is required'
            ),
            'no from email' => array(
                'options' => array(
                    'to' => 'test@test.com', 'subject' => 'test', 'body' => 'test',
                    'from' => array('name' => 'Test')
                ),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ),
            'no to' => array(
                'options' => array('from' => 'test@test.com', 'subject' => 'test', 'body' => 'test'),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'To parameter is required'
            ),
            'no to email' => array(
                'options' => array(
                    'from' => 'test@test.com', 'subject' => 'test', 'body' => 'test',
                    'to' => array('name' => 'Test')
                ),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ),
            'no to email in one of addresses' => array(
                'options' => array(
                    'from' => 'test@test.com', 'subject' => 'test', 'body' => 'test',
                    'to' => array('test@test.com', array('name' => 'Test'))
                ),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Email parameter is required'
            ),
            'no subject' => array(
                'options' => array('from' => 'test@test.com', 'to' => 'test@test.com', 'body' => 'test'),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Subject parameter is required'
            ),
            'no body' => array(
                'options' => array('from' => 'test@test.com', 'to' => 'test@test.com', 'subject' => 'test'),
                'exceptionName' => '\Oro\Component\Action\Exception\InvalidParameterException',
                'exceptionMessage' => 'Body parameter is required'
            ),
        );
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
        return array(
            'simple' => array(
                array(
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => 'test@test.com',
                    'to' => array('test@test.com'),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'simple with name' => array(
                array(
                    'from' => 'Test <test@test.com>',
                    'to' => 'Test <test@test.com>',
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => 'Test <test@test.com>',
                    'to' => array('Test <test@test.com>'),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'extended' => array(
                array(
                    'from' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        array(
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        )
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'multiple to' => array(
                array(
                    'from' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        array(
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        ),
                        'test@test.com',
                        'Test <test@test.com>'
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        array(
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        ),
                        'test@test.com',
                        'Test <test@test.com>'
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                )
            )
        );
    }

    /**
     * @dataProvider executeOptionsDataProvider
     * @param array $options
     * @param array $expected
     */
    public function testExecute($options, $expected)
    {
        $context = array();
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

        $self = $this;
        $emailUserEntity = $this->getMockBuilder('\Oro\Bundle\EmailBundle\Entity\EmailUser')
            ->disableOriginalConstructor()
            ->setMethods(['getEmail'])
            ->getMock();
        $emailEntity = $this->getMock('\Oro\Bundle\EmailBundle\Entity\Email');
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
     * @return array
     */
    public function executeOptionsDataProvider()
    {
        $nameMock = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\FirstNameInterface')
            ->getMock();
        $nameMock->expects($this->any())
            ->method('getFirstName')
            ->will($this->returnValue('NAME'));

        return array(
            'simple' => array(
                array(
                    'from' => 'test@test.com',
                    'to' => 'test@test.com',
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => 'test@test.com',
                    'to' => array('test@test.com'),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'simple with name' => array(
                array(
                    'from' => '"Test" <test@test.com>',
                    'to' => '"Test" <test@test.com>',
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => '"Test" <test@test.com>',
                    'to' => array('"Test" <test@test.com>'),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'extended' => array(
                array(
                    'from' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => '"Test" <test@test.com>',
                    'to' => array('"Test" <test@test.com>'),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'extended with name formatting' => array(
                array(
                    'from' => array(
                        'name' => $nameMock,
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        'name' => $nameMock,
                        'email' => 'test@test.com'
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                ),
                array(
                    'from' => '"_Formatted" <test@test.com>',
                    'to' => array('"_Formatted" <test@test.com>'),
                    'subject' => 'test',
                    'body' => 'test'
                )
            ),
            'multiple to' => array(
                array(
                    'from' => array(
                        'name' => 'Test',
                        'email' => 'test@test.com'
                    ),
                    'to' => array(
                        array(
                            'name' => 'Test',
                            'email' => 'test@test.com'
                        ),
                        'test@test.com',
                        '"Test" <test@test.com>'
                    ),
                    'subject' => 'test',
                    'body' => 'test',
                    'attribute' => 'attr'
                ),
                array(
                    'from' => '"Test" <test@test.com>',
                    'to' => array(
                        '"Test" <test@test.com>',
                        'test@test.com',
                        '"Test" <test@test.com>'
                    ),
                    'subject' => 'test',
                    'body' => 'test'
                )
            )
        );
    }
}
