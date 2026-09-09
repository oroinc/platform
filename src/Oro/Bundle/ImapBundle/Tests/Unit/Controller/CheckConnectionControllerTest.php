<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Controller;

use Oro\Bundle\ImapBundle\Controller\CheckConnectionController;
use Oro\Bundle\ImapBundle\Manager\ConnectionControllerManager;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CheckConnectionControllerTest extends TestCase
{
    public function testCheckActionDoesNotConvertAccessDeniedExceptionToSuccessfulResponse(): void
    {
        $request = new Request(request: ['formParentName' => 'userForm']);

        $connectionControllerManager = $this->createMock(ConnectionControllerManager::class);
        $connectionControllerManager->expects(self::once())
            ->method('getCheckConnectionForm')
            ->with($request, 'userForm', 'gmail')
            ->willThrowException(new AccessDeniedException());

        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::once())
            ->method('get')
            ->with(ConnectionControllerManager::class)
            ->willReturn($connectionControllerManager);

        $controller = new CheckConnectionController();
        $controller->setContainer($container);

        $this->expectException(AccessDeniedException::class);

        $controller->checkAction($request, 'gmail');
    }
}
