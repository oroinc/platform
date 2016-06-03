<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ApiDocConfigurationCompilerPass implements CompilerPassInterface
{
    const API_DOC_EXTRACTOR_SERVICE                = 'nelmio_api_doc.extractor.api_doc_extractor';
    const EXPECTED_API_DOC_EXTRACTOR_CLASS         = 'Nelmio\ApiDocBundle\Extractor\ApiDocExtractor';
    const EXPECTED_CACHING_API_DOC_EXTRACTOR_CLASS = 'Nelmio\ApiDocBundle\Extractor\CachingApiDocExtractor';
    const NEW_API_DOC_EXTRACTOR_CLASS              = 'Oro\Component\Routing\ApiDoc\ApiDocExtractor';
    const NEW_CACHING_API_DOC_EXTRACTOR_CLASS      = 'Oro\Component\Routing\ApiDoc\CachingApiDocExtractor';
    const API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE = 'oro_api.routing_options_resolver.api_doc';
    const ROUTING_OPTIONS_RESOLVER_AWARE_INTERFACE =
        'Oro\Component\Routing\Resolver\RouteOptionsResolverAwareInterface';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::API_DOC_EXTRACTOR_SERVICE)) {
            return;
        }
        if (!$container->hasDefinition(self::API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE)) {
            return;
        }

        $apiDocExtractorDef = $container->getDefinition(self::API_DOC_EXTRACTOR_SERVICE);
        $newApiDocExtractorClass = $this->getNewApiDocExtractorClass($apiDocExtractorDef->getClass());
        if (!$newApiDocExtractorClass) {
            return;
        }

        $apiDocExtractorDef->setClass($newApiDocExtractorClass);
        if (is_subclass_of($apiDocExtractorDef->getClass(), self::ROUTING_OPTIONS_RESOLVER_AWARE_INTERFACE)) {
            $apiDocExtractorDef->addMethodCall(
                'setRouteOptionsResolver',
                [new Reference(self::API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE)]
            );
        }
    }

    /**
     * @param string $currentClass
     *
     * @return string|null
     */
    protected function getNewApiDocExtractorClass($currentClass)
    {
        switch ($currentClass) {
            case self::EXPECTED_CACHING_API_DOC_EXTRACTOR_CLASS:
                return self::NEW_CACHING_API_DOC_EXTRACTOR_CLASS;
            case self::EXPECTED_API_DOC_EXTRACTOR_CLASS:
                return self::NEW_API_DOC_EXTRACTOR_CLASS;
            default:
                return null;
        }
    }
}
