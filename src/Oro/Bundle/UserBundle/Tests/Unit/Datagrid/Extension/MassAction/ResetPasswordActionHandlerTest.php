<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Datagrid\Extension\MassAction\ResetPasswordActionHandler;
use Oro\Bundle\UserBundle\Handler\ResetPasswordHandler;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Symfony\Component\Translation\TranslatorInterface;

class ResetPasswordActionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ResetPasswordActionHandler */
    protected $handler;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ResetPasswordActionHandler */
    protected $translator;

    /** @var int */
    protected $methodCalls;

    protected function setUp()
    {
        $tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn(new User());

        $this->translator = $this->createMock(TranslatorInterface::class);

        $resetPasswordHandler = $this->createMock(ResetPasswordHandler::class);

        $this->methodCalls = 0;
        $this->handler = new ResetPasswordActionHandler(
            $resetPasswordHandler,
            $this->translator,
            $tokenAccessor
        );
    }

    public function testHandle()
    {
        $responseMessage = 'TEST123';

        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->willReturn($responseMessage);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em
            ->expects($this->atLeastOnce())
            ->method('flush');

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb
            ->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $results = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult')
            ->disableOriginalConstructor()
            ->getMock();
        $results
            ->expects($this->once())
            ->method('getSource')
            ->willReturn($qb);
        $results
            ->expects($this->atLeastOnce())
            ->method('rewind');
        $results
            ->expects($this->atLeastOnce())
            ->method('next');
        $results
            ->expects($this->atLeastOnce())
            ->method('current')
            ->willReturnCallback(function () {
                $this->methodCalls++;
                return $this->methodCalls < 7 ? new ResultRecord(new User()) : null;
            });

        $args = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args
            ->expects($this->once())
            ->method('getResults')
            ->willReturn($results);

        $response = $this->handler->handle($args);

        $this->assertInstanceof('Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse', $response);
        $this->assertEquals($responseMessage, $response->getMessage());
    }
}
