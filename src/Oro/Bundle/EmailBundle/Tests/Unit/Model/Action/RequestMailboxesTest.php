<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\Action;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\EmailBundle\Model\Action\RequestMailboxes;

use Oro\Component\Action\Model\ContextAccessor;

class RequestMailboxesTest extends \PHPUnit_Framework_TestCase
{
    /** @var RequestMailboxes */
    protected $action;

    /** @var ContextAccessor */
    protected $contextAccessor;

    /** @var Registry */
    protected $registry;

    /** @var MailboxProcessStorage */
    protected $mailboxProcessStorage;

    /** @var MailboxRepository */
    protected $repository;

    public function setUp()
    {
        $this->contextAccessor = $this->getMock('Oro\Component\Action\Model\ContextAccessor');

        $this->mailboxProcessStorage = $this->getMockBuilder('Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $demoProcess = $this->getMock('Oro\Bundle\EmailBundle\Mailbox\MailboxProcessProviderInterface');
        $demoProcess->expects($this->any())
            ->method('getSettingsEntityFQCN')
            ->will($this->returnValue('DemoProcessSettings'));

        $this->mailboxProcessStorage->expects($this->any())
            ->method('getProcess')
            ->with($this->equalTo('demo'))
            ->will($this->returnValue($demoProcess));

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo('OroEmailBundle:Mailbox'))
            ->will($this->returnValue($this->repository));

        $this->action = new RequestMailboxes($this->contextAccessor, $this->registry, $this->mailboxProcessStorage);

        $this->action->setDispatcher($this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface'));
    }


    public function testExecuteAction()
    {
        $email = new Email();

        $options = [
            'process_type' => 'demo',
            'email' => '$.email',
            'attribute' => '$.attribute'
        ];

        $fakeContext = ['fake', 'things', 'are', 'here'];

        $mailboxes = [
            new Mailbox()
        ];

        $this->contextAccessor->expects($this->at(0))
            ->method('getValue')
            ->with(
                $this->equalTo($fakeContext),
                $this->equalTo('demo')
            )->will($this->returnValue('demo'));

        $this->contextAccessor->expects($this->at(1))
            ->method('getValue')
            ->with(
                $this->equalTo($fakeContext),
                $this->equalTo('$.email')
            )->will($this->returnValue($email));

        $this->contextAccessor->expects($this->once())
            ->method('setValue')
            ->with(
                $this->equalTo($fakeContext),
                $this->equalTo('$.attribute'),
                $this->equalTo($mailboxes)
            );

        $this->repository->expects($this->once())
            ->method('findBySettingsClassAndEmail')
            ->with(
                $this->equalTo('DemoProcessSettings'),
                $this->equalTo($email)
            )->will($this->returnValue($mailboxes));

        $this->action->initialize($options);
        $this->action->execute($fakeContext);
    }
}
