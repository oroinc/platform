<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;

class ApiDocConfigurationCompilerPass implements CompilerPassInterface
{
    const API_DOC_EXTRACTOR_SERVICE                 = 'nelmio_api_doc.extractor.api_doc_extractor';
    const EXPECTED_API_DOC_EXTRACTOR_CLASS          = 'Nelmio\ApiDocBundle\Extractor\ApiDocExtractor';
    const EXPECTED_CACHING_API_DOC_EXTRACTOR_CLASS  = 'Nelmio\ApiDocBundle\Extractor\CachingApiDocExtractor';
    const NEW_API_DOC_EXTRACTOR_CLASS               = 'Oro\Bundle\ApiBundle\ApiDoc\ApiDocExtractor';
    const NEW_CACHING_API_DOC_EXTRACTOR_CLASS       = 'Oro\Bundle\ApiBundle\ApiDoc\CachingApiDocExtractor';
    const API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE  = 'oro_api.rest.routing_options_resolver';
    const REST_DOC_VIEW_DETECTOR_SERVICE            = 'oro_api.rest.doc_view_detector';
    const REST_DOC_VIEW_DETECTOR_AWARE_INTERFACE    =
        'Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetectorAwareInterface';
    const ROUTING_OPTIONS_RESOLVER_AWARE_INTERFACE  =
        'Oro\Component\Routing\Resolver\RouteOptionsResolverAwareInterface';
    const API_DOC_ROUTING_OPTIONS_RESOLVER_TAG_NAME = 'oro_api.routing_options_resolver';

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
        if (is_subclass_of($apiDocExtractorDef->getClass(), self::REST_DOC_VIEW_DETECTOR_AWARE_INTERFACE)) {
            $apiDocExtractorDef->addMethodCall(
                'setRestDocViewDetector',
                [new Reference(self::REST_DOC_VIEW_DETECTOR_SERVICE)]
            );
        }
        if (is_subclass_of($apiDocExtractorDef->getClass(), self::ROUTING_OPTIONS_RESOLVER_AWARE_INTERFACE)) {
            $apiDocExtractorDef->addMethodCall(
                'setRouteOptionsResolver',
                [new Reference(self::API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE)]
            );
        }

        $this->registerRoutingOptionsResolvers($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function registerRoutingOptionsResolvers(ContainerBuilder $container)
    {
        $chainServiceDef = DependencyInjectionUtil::findDefinition(
            $container,
            self::API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE
        );
        if (null !== $chainServiceDef) {
            // find services
            $services = [];
            $taggedServices = $container->findTaggedServiceIds(self::API_DOC_ROUTING_OPTIONS_RESOLVER_TAG_NAME);
            foreach ($taggedServices as $id => $attributes) {
                foreach ($attributes as $attribute) {
                    $priority = isset($attribute['priority']) ? $attribute['priority'] : 0;
                    $view = null;
                    if (!empty($attribute['view'])) {
                        $view = $attribute['view'];
                    }
                    $services[$priority][] = [new Reference($id), $view];
                }
            }
            if (empty($services)) {
                return;
            }

            // sort by priority and flatten
            krsort($services);
            $services = call_user_func_array('array_merge', $services);

            // register
            foreach ($services as $service) {
                $chainServiceDef->addMethodCall('addResolver', $service);
            }
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
