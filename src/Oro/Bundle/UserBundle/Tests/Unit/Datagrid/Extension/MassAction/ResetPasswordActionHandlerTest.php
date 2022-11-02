<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datasource\Orm\IterableResult;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Datagrid\Extension\MassAction\ResetPasswordActionHandler;
use Oro\Bundle\UserBundle\Handler\ResetPasswordHandler;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as User;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResetPasswordActionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ResetPasswordActionHandler */
    private $handler;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ResetPasswordActionHandler */
    private $translator;

    /** @var int */
    private $methodCalls;

    protected function setUp(): void
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

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn($responseMessage);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->atLeastOnce())
            ->method('flush');

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $results = $this->createMock(IterableResult::class);
        $results->expects($this->once())
            ->method('getSource')
            ->willReturn($qb);
        $results->expects($this->atLeastOnce())
            ->method('rewind');
        $results->expects($this->atLeastOnce())
            ->method('next');
        $results->expects($this->atLeastOnce())
            ->method('current')
            ->willReturnCallback(function () {
                $this->methodCalls++;
                return $this->methodCalls < 7 ? new ResultRecord(new User()) : null;
            });

        $args = $this->createMock(MassActionHandlerArgs::class);
        $args->expects($this->once())
            ->method('getResults')
            ->willReturn($results);

        $response = $this->handler->handle($args);

        $this->assertInstanceOf(MassActionResponse::class, $response);
        $this->assertEquals($responseMessage, $response->getMessage());
    }
}
