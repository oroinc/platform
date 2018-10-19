<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\ApiDoc\ApiDocAnnotationHandlerInterface;
use Oro\Bundle\ApiBundle\ApiDoc\ChainApiDocAnnotationHandler;
use Symfony\Component\Routing\Route;

class ChainApiDocAnnotationHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainApiDocAnnotationHandler */
    private $chainHandler;

    protected function setUp()
    {
        $this->chainHandler = new ChainApiDocAnnotationHandler();
    }

    public function testEmptyChainHandler()
    {
        $annotation = $this->createMock(ApiDoc::class);
        $route = $this->createMock(Route::class);

        $this->chainHandler->handle($annotation, $route);
    }

    public function testChainHandler()
    {
        $annotation = $this->createMock(ApiDoc::class);
        $route = $this->createMock(Route::class);

        $handler1 = $this->createMock(ApiDocAnnotationHandlerInterface::class);
        $handler2 = $this->createMock(ApiDocAnnotationHandlerInterface::class);

        $this->chainHandler->addHandler($handler1);
        $this->chainHandler->addHandler($handler2);

        $handler1->expects(self::once())
            ->method('handle')
            ->with(self::identicalTo($annotation), self::identicalTo($route));
        $handler2->expects(self::once())
            ->method('handle')
            ->with(self::identicalTo($annotation), self::identicalTo($route));

        $this->chainHandler->handle($annotation, $route);
    }
}
