<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CommentBundle\DependencyInjection\OroCommentExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCommentExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroCommentExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
