<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all exception text extractors.
 */
class ExceptionTextExtractorCompilerPass implements CompilerPassInterface
{
    private const EXCEPTION_TEXT_EXTRACTOR_SERVICE_ID = 'oro_api.exception_text_extractor';
    private const EXCEPTION_TEXT_EXTRACTOR_TAG        = 'oro.api.exception_text_extractor';

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
