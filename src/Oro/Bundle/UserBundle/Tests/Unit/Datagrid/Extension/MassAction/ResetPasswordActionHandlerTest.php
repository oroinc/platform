<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Datagrid\Extension\MassAction\ResetPasswordActionHandler;
use Oro\Bundle\NotificationBundle\Model\EmailTemplate;

class ResetPasswordActionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ResetPasswordActionHandler */
    protected $handler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ResetPasswordActionHandler */
    protected $translator;

    /** @var  int */
    protected $methodCalls;

    protected function setUp()
    {
        $processor = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Processor\EmailNotificationProcessor')
            ->disableOriginalConstructor()
            ->getMock();
        $processor
            ->expects($this->atLeastOnce())
            ->method('process');

        $userManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\UserManager')
            ->disableOriginalConstructor()
            ->getMock();
        $userManager
            ->expects($this->atLeastOnce())
            ->method('updateUser');

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->methodCalls = 0;
        $this->handler = new ResetPasswordActionHandler($processor, $userManager, $this->translator, $logger);
    }

    public function testHandle()
    {
        $responseMessage = 'TEST123';

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->will($this->returnValue($responseMessage));

        $options = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $options
            ->expects($this->once())
            ->method('offsetGetByPath')
            ->will($this->returnValue($responseMessage));

        $massAction = $this
            ->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\MassActionInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $massAction
            ->expects($this->once())
            ->method('getOptions')
            ->will($this->returnValue($options));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository
            ->expects($this->once())
            ->method('findOneBy')
            ->will($this->returnValue(new EmailTemplate()));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em
            ->expects($this->atLeastOnce())
            ->method('flush');
        $em
            ->expects($this->atLeastOnce())
            ->method('clear');
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb
            ->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $results = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult')
            ->disableOriginalConstructor()
            ->getMock();
        $results
            ->expects($this->once())
            ->method('getSource')
            ->will($this->returnValue($qb));
        $results
            ->expects($this->atLeastOnce())
            ->method('next');
        $results
            ->expects($this->atLeastOnce())
            ->method('current')
            ->will($this->returnCallback(function () {
                $this->methodCalls++;
                return $this->methodCalls < 7 ? new ResultRecord(new User()) : null;
            }));

        $args = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args
            ->expects($this->once())
            ->method('getMassAction')
            ->will($this->returnValue($massAction));
        $args
            ->expects($this->once())
            ->method('getResults')
            ->will($this->returnValue($results));

        $response = $this->handler->handle($args);

        $this->assertInstanceof('Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse', $response);
        $this->assertEquals($responseMessage, $response->getMessage());
    }
}
