<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $emailEntityBuilder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailer;

    /**
     * @var Processor
     */
    protected $emailProcessor;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailEntityBuilder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->mailer = $this->getMockBuilder('\Swift_Mailer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailProcessor = new Processor($this->em, $this->emailEntityBuilder, $this->mailer);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Sender can not be empty
     */
    public function testProcessEmptyFromException()
    {
        $this->mailer->expects($this->never())
            ->method('createMessage');
        $this->mailer->expects($this->never())
            ->method('send');

        $this->emailProcessor->process($this->createEmailModel(array()));
    }

    /**
     * @dataProvider invalidModelDataProvider
     */
    public function testProcessEmptyToException($data, $exception, $exceptionMessage)
    {
        $this->mailer->expects($this->never())
            ->method('createMessage');
        $this->mailer->expects($this->never())
            ->method('send');

        $this->setExpectedException($exception, $exceptionMessage);
        $this->emailProcessor->process($this->createEmailModel($data));
    }

    public function invalidModelDataProvider()
    {
        return array(
            array(array(), '\InvalidArgumentException', 'Sender can not be empty'),
            array(array('from' => 'test@test.com'), '\InvalidArgumentException', 'Recipient can not be empty'),
        );
    }

    /**
     * @expectedException \Swift_SwiftException
     * @expectedExceptionMessage An email was not delivered.
     */
    public function testProcessSendFailException()
    {
        $message = $this->getMockForAbstractClass('\Swift_Mime_Message');
        $this->mailer->expects($this->once())
            ->method('createMessage')
            ->will($this->returnValue($message));
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($message)
            ->will($this->returnValue(false));

        $model = $this->createEmailModel(
            array(
                'from' => 'test@test.com',
                'to' => array('test2@test.com'),
                'subject' => 'test',
                'body' => 'test body'
            )
        );
        $this->emailProcessor->process($model);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The $addresses argument must be a string or a list of strings (array or Iterator)
     */
    public function testProcessAddressException()
    {
        $message = $this->getMockForAbstractClass('\Swift_Mime_Message');
        $this->mailer->expects($this->once())
            ->method('createMessage')
            ->will($this->returnValue($message));
        $this->mailer->expects($this->never())
            ->method('send');

        $model = $this->createEmailModel(
            array(
                'from' => new \stdClass(),
                'to' => array(new \stdClass()),
            )
        );
        $this->emailProcessor->process($model);
    }

    /**
     * @dataProvider messageDataProvider
     * @param array $data
     * @param array $expectedMessageData
     */
    public function testProcess($data, $expectedMessageData)
    {
        $message = $this->getMockBuilder('\Swift_Mime_Message')
            ->setMethods(array('setDate', 'setFrom', 'setTo', 'setSubject', 'setBody'))
            ->getMockForAbstractClass();
        $message->expects($this->once())
            ->method('setDate');
        $message->expects($this->once())
            ->method('setFrom')
            ->with($expectedMessageData['from']);
        $message->expects($this->once())
            ->method('setTo')
            ->with($expectedMessageData['to']);
        $message->expects($this->once())
            ->method('setSubject')
            ->with($expectedMessageData['subject']);
        $message->expects($this->once())
            ->method('setBody')
            ->with($expectedMessageData['body'], 'text/plain');

        $this->mailer->expects($this->once())
            ->method('createMessage')
            ->will($this->returnValue($message));
        $this->mailer->expects($this->once())
            ->method('send')
            ->with($message)
            ->will($this->returnValue(true));

        $origin = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailOrigin')
            ->disableOriginalConstructor()
            ->getMock();
        $folder = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailFolder')
            ->disableOriginalConstructor()
            ->getMock();
        $origin->expects($this->once())
            ->method('getFolder')
            ->with(EmailFolder::SENT)
            ->will($this->returnValue($folder));

        $emailOriginRepo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $emailOriginRepo->expects($this->once())
            ->method('findOneBy')
            ->with(array('name' => InternalEmailOrigin::BAP))
            ->will($this->returnValue($origin));
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroEmailBundle:InternalEmailOrigin')
            ->will($this->returnValue($emailOriginRepo));

        $this->emailEntityBuilder->expects($this->once())
            ->method('setOrigin')
            ->with($origin);

        $email = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Email')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailEntityBuilder->expects($this->once())
            ->method('email')
            ->with($data['subject'], $data['from'], $data['to'])
            ->will($this->returnValue($email));

        $body = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\EmailBody')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailEntityBuilder->expects($this->once())
            ->method('body')
            ->with($expectedMessageData['body'], false, true)
            ->will($this->returnValue($body));

        $batch = $this->getMock('Oro\Bundle\EmailBundle\Builder\EmailEntityBatchInterface');
        $this->emailEntityBuilder->expects($this->once())
            ->method('getBatch')
            ->will($this->returnValue($batch));
        $batch->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($this->em));
        $this->em->expects($this->once())
            ->method('flush');

        $model = $this->createEmailModel($data);
        $this->emailProcessor->process($model);
    }

    public function messageDataProvider()
    {
        return array(
            array(
                array(
                    'from' => 'from@test.com',
                    'to' => array('to@test.com'),
                    'subject' => 'subject',
                    'body' => 'body'
                ),
                array(
                    'from' => array('from@test.com'),
                    'to' => array('to@test.com'),
                    'subject' => 'subject',
                    'body' => 'body'
                )
            ),
            array(
                array(
                    'from' => 'Test <from@test.com>',
                    'to' => array('To <to@test.com>', 'to2@test.com'),
                    'subject' => 'subject',
                    'body' => 'body'
                ),
                array(
                    'from' => array('from@test.com' => 'Test'),
                    'to' => array('to@test.com' => 'To', 'to2@test.com'),
                    'subject' => 'subject',
                    'body' => 'body'
                )
            )
        );
    }

    protected function createEmailModel($data)
    {
        $email = new Email();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $key => $value) {
            $propertyAccessor->setValue($email, $key, $value);
        }
        return $email;
    }
}
