<?php

namespace Oro\Bundle\TranslationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslatorDependencyPass;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\DebugTranslatorPass;

class OroTranslationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TranslatorDependencyPass());
        $container->addCompilerPass(new DebugTranslatorPass());
    }
}
