<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\AnnotationHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\ApiDocAnnotationHandlerInterface;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\ChainApiDocAnnotationHandler;
use Symfony\Component\Routing\Route;

class ChainApiDocAnnotationHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testEmptyChainHandler()
    {
        $annotation = $this->createMock(ApiDoc::class);
        $route = $this->createMock(Route::class);

        $chainHandler = new ChainApiDocAnnotationHandler([]);
        $chainHandler->handle($annotation, $route);
    }

    public function testChainHandler()
    {
        $annotation = $this->createMock(ApiDoc::class);
        $route = $this->createMock(Route::class);

        $handler1 = $this->createMock(ApiDocAnnotationHandlerInterface::class);
        $handler2 = $this->createMock(ApiDocAnnotationHandlerInterface::class);

        $handler1->expects(self::once())
            ->method('handle')
            ->with(self::identicalTo($annotation), self::identicalTo($route));
        $handler2->expects(self::once())
            ->method('handle')
            ->with(self::identicalTo($annotation), self::identicalTo($route));

        $chainHandler = new ChainApiDocAnnotationHandler([$handler1, $handler2]);
        $chainHandler->handle($annotation, $route);
    }
}
