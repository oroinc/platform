<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Handler;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Form\Handler\EmailHandler;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Tests\Unit\Entity\TestFixtures\EmailAddress;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser;
use Oro\Bundle\EmailBundle\Tests\Unit\ReflectionUtil;

class EmailHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityContext;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emailAddressManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emailProcessor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $nameFormatter;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityRoutingHelper;

    /** @var Email */
    protected $model;

    /** @var EmailHandler */
    protected $handler;

    protected function setUp()
    {
        $this->form                = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request             = new Request();
        $this->em                  = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator          = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityContext     = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->emailAddressManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->emailProcessor      = $this->getMockBuilder('Oro\Bundle\EmailBundle\Mailer\Processor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger              = $this->getMock('Psr\Log\LoggerInterface');
        $this->nameFormatter       = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityRoutingHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $emailAddressRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $emailAddressRepository->expects($this->any())
            ->method('findOneBy')
            ->will(
                $this->returnCallback(
                    function ($args) {
                        $emailAddress = new EmailAddress();
                        $emailAddress->setEmail($args['email']);
                        $emailAddress->setOwner(new TestUser($args['email'], 'FirstName', 'LastName'));

                        return $emailAddress;
                    }
                )
            );
        $this->emailAddressManager->expects($this->any())
            ->method('getEmailAddressRepository')
            ->with($this->identicalTo($this->em))
            ->will($this->returnValue($emailAddressRepository));

        $this->model   = new Email();
        $this->handler = new EmailHandler(
            $this->form,
            $this->request,
            $this->em,
            $this->translator,
            $this->securityContext,
            $this->emailAddressManager,
            new EmailAddressHelper(),
            $this->emailProcessor,
            $this->logger,
            $this->nameFormatter,
            $this->entityRoutingHelper
        );
    }

    public function testProcessGetRequestWithEmptyQueryString()
    {
        $this->request->setMethod('GET');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->model);

        $this->form->expects($this->never())
            ->method('submit');

        $user = new TestUser('test@example.com', 'John', 'Smith');

        $this->nameFormatter->expects($this->any())
            ->method('format')
            ->with($user)
            ->will($this->returnValue('John Smith'));

        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));
        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->assertFalse($this->handler->process($this->model));

        $this->assertEquals(null, $this->model->getGridName());
        $this->assertEquals('John Smith <test@example.com>', $this->model->getFrom());
    }

    public function testProcessGetRequest()
    {
        $this->request->setMethod('GET');
        $this->request->query->set('gridName', 'testGrid');
        $this->request->query->set('from', 'from@example.com');
        $this->request->query->set('to', 'to@example.com');
        $this->request->query->set('cc', 'cc@example.com');
        $this->request->query->set('bcc', 'bcc@example.com');
        $this->request->query->set('subject', 'testSubject');

        $this->nameFormatter->expects($this->any())
            ->method('format')
            ->with($this->isInstanceOf('Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser'))
            ->will($this->returnValue('FirstName LastName'));

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->model);

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->model));

        $this->assertEquals('testGrid', $this->model->getGridName());
        $this->assertEquals('FirstName LastName <from@example.com>', $this->model->getFrom());
        $this->assertEquals(['FirstName LastName <to@example.com>'], $this->model->getTo());
        $this->assertEquals(['FirstName LastName <cc@example.com>'], $this->model->getCc());
        $this->assertEquals(['FirstName LastName <bcc@example.com>'], $this->model->getBcc());
        $this->assertEquals('testSubject', $this->model->getSubject());
    }

    /**
     * @param string $method
     *
     * @dataProvider validData
     */
    public function testProcessValidData($method)
    {
        $this->request->setMethod($method);
        $this->model
            ->setFrom('from@example.com')
            ->setTo(['to@example.com'])
            ->setCc(['cc@example.com'])
            ->setBcc(['bcc@example.com', 'bcc2@example.com'])
            ->setSubject('testSubject')
            ->setBody('testBody');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->model);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($this->model);

        $this->assertTrue($this->handler->process($this->model));
    }

    /**
     * @param string $method
     *
     * @dataProvider invalidData
     */
    public function testProcessException($method)
    {
        $this->request->setMethod($method);
        $this->model
            ->setFrom('from@example.com')
            ->setTo(['to@example.com'])
            ->setSubject('testSubject')
            ->setBody('testBody');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->model);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $exception = new \Exception('TEST');
        $this->emailProcessor->expects($this->once())
            ->method('process')
            ->with($this->model)
            ->will(
                $this->returnCallback(
                    function () use ($exception) {
                        throw $exception;
                    }
                )
            );

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Email sending failed.', ['exception' => $exception]);
        $this->form->expects($this->once())
            ->method('addError')
            ->with($this->isInstanceOf('Symfony\Component\Form\FormError'));
        $this->assertFalse($this->handler->process($this->model));
    }

    /**
     * @dataProvider preciseFullEmailAddressProvider
     */
    public function testPreciseFullEmailAddress($expected, $emailAddress, $ownerClass, $ownerId)
    {
        $this->nameFormatter->expects($this->any())
            ->method('format')
            ->with($this->isInstanceOf('Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser'))
            ->will(
                $this->returnCallback(
                    function ($obj) {
                        return $obj->getFirstName() . ' ' . $obj->getLastName();
                    }
                )
            );
        if ($ownerId) {
            $this->entityRoutingHelper->expects($this->once())
                ->method('getEntity')
                ->with($ownerClass, $ownerId)
                ->will($this->returnValue(new TestUser($emailAddress, 'OwnerFirstName', 'OwnerLastName')));
        }

        $param = [&$emailAddress, $ownerClass, $ownerId];
        ReflectionUtil::callProtectedMethod($this->handler, 'preciseFullEmailAddress', $param);

        $this->assertEquals($expected, $emailAddress);
    }

    public function preciseFullEmailAddressProvider()
    {
        return [
            [
                'FirstName LastName <test@example.com>',
                'test@example.com',
                null,
                null
            ],
            [
                'FirstName LastName <test@example.com>',
                'test@example.com',
                'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser',
                null
            ],
            [
                'OwnerFirstName OwnerLastName <test@example.com>',
                'test@example.com',
                'Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\TestUser',
                123
            ],
        ];
    }

    public function validData()
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }

    public function invalidData()
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }
}
