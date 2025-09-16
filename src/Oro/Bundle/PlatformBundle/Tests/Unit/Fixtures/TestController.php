<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Fixtures;

use Symfony\Component\HttpFoundation\Request;

/**
 * Test controller fixture for ArgumentResolver tests
 */
class TestController
{
    public function action(TestEntity $entity): void
    {
    }

    public function actionWithRequest(Request $request): void
    {
    }

    public function actionWithBuiltinTypes(string $stringParam, int $intParam): void
    {
    }

    public function actionWithUnionTypes(TestEntity|string $entityOrString): void
    {
    }
}
