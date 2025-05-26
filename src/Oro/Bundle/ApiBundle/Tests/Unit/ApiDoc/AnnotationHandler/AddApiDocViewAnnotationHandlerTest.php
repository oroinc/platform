<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\AnnotationHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\AddApiDocViewAnnotationHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;

class AddApiDocViewAnnotationHandlerTest extends TestCase
{
    private AddApiDocViewAnnotationHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->handler = new AddApiDocViewAnnotationHandler('additional_view', 'existing_view');
    }

    /**
     * @dataProvider handleProvider
     */
    public function testHandle(array $existingViews, array $expectedViews): void
    {
        $annotation = new ApiDoc(['views' => $existingViews]);
        $route = new Route('test');

        $this->handler->handle($annotation, $route);

        self::assertEquals($expectedViews, $annotation->getViews());
    }

    public function handleProvider(): array
    {
        return [
            [[], []],
            [['existing_view'], ['existing_view', 'additional_view']],
            [['additional_view', 'existing_view'], ['additional_view', 'existing_view']],
            [['another_view'], ['another_view']]
        ];
    }
}
