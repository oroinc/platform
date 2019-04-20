<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Fixtures\FooBundle\Controller;

use Oro\Bundle\NavigationBundle\Annotation\TitleTemplate;

class TestController
{
    /**
     * @TitleTemplate("test1 title")
     */
    public function test1Action()
    {
        return [];
    }

    public function test2Action()
    {
        return [];
    }
}
