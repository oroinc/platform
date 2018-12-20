<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\ApiDoc\AddApiDocViewAnnotationHandler;
use Symfony\Component\Routing\Route;

class AddApiDocViewAnnotationHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AddApiDocViewAnnotationHandler */
    private $handler;

    protected function setUp()
    {
        $this->handler = new AddApiDocViewAnnotationHandler('additional_view', 'existing_view');
    }

    /**
     * @dataProvider handleProvider
     */
    public function testHandle($existingViews, $expectedViews)
    {
        $annotation = new ApiDoc(['views' => $existingViews]);
        $route = new Route('test');

        $this->handler->handle($annotation, $route);

        self::assertEquals($expectedViews, $annotation->getViews());
    }

    public function handleProvider()
    {
        return [
            [[], []],
            [['existing_view'], ['existing_view', 'additional_view']],
            [['additional_view', 'existing_view'], ['additional_view', 'existing_view']],
            [['another_view'], ['another_view']]
        ];
    }
}
