<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslationCacheWarmerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TranslationCacheWarmerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $translationWarmerDef = $container->register('translation.warmer')
            ->addTag('kernel.cache_warmer');

        $compiler = new TranslationCacheWarmerPass();
        $compiler->process($container);

        self::assertEquals(
            ['kernel.cache_warmer' => [['priority' => 100]]],
            $translationWarmerDef->getTags()
        );
    }
}
