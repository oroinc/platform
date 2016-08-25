<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

class ConstraintTextExtractorConfigurationCompilerPass implements CompilerPassInterface
{
    const EXCEPTION_TEXT_EXTRACTOR_SERVICE_ID = 'oro_api.constraint_text_extractor';
    const EXCEPTION_TEXT_EXTRACTOR_TAG        = 'oro.api.constraint_text_extractor';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        DependencyInjectionUtil::registerTaggedServices(
            $container,
            self::EXCEPTION_TEXT_EXTRACTOR_SERVICE_ID,
            self::EXCEPTION_TEXT_EXTRACTOR_TAG,
            'addExtractor'
        );
    }
}
