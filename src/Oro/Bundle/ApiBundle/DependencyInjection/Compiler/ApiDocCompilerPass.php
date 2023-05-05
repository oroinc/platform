<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Nelmio\ApiDocBundle\Extractor as NelmioExtractor;
use Nelmio\ApiDocBundle\Formatter as NelmioFormatter;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\AddApiDocViewAnnotationHandler;
use Oro\Bundle\ApiBundle\ApiDoc\Extractor;
use Oro\Bundle\ApiBundle\ApiDoc\Formatter;
use Oro\Bundle\ApiBundle\DependencyInjection\OroApiExtension;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures ApiDoc related services.
 */
class ApiDocCompilerPass implements CompilerPassInterface
{
    use ApiTaggedServiceTrait;

    private const API_DOC_EXTRACTOR_SERVICE = 'nelmio_api_doc.extractor.api_doc_extractor';
    private const API_DOC_REQUEST_TYPE_PROVIDER_SERVICE = 'oro_api.rest.request_type_provider';
    private const API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE = 'oro_api.rest.chain_routing_options_resolver';
    private const API_DOC_ROUTING_OPTIONS_RESOLVER_TAG_NAME = 'oro.api.routing_options_resolver';
    private const API_DOC_ANNOTATION_HANDLER_SERVICE = 'oro_api.rest.api_doc_annotation_handler';
    private const API_DOC_ANNOTATION_HANDLER_TAG_NAME = 'oro.api.api_doc_annotation_handler';
    private const REST_DOC_VIEW_DETECTOR_SERVICE = 'oro_api.rest.doc_view_detector';
    private const API_DOC_HTML_FORMATTER_SERVICE = 'nelmio_api_doc.formatter.html_formatter';
    private const RENAMED_API_DOC_HTML_FORMATTER_SERVICE = 'oro_api.api_doc.formatter.html_formatter.nelmio';
    private const COMPOSITE_API_DOC_HTML_FORMATTER_SERVICE = 'oro_api.api_doc.formatter.html_formatter.composite';
    private const API_DOC_DATA_TYPE_CONVERTER = 'oro_api.api_doc.data_type_converter';
    private const API_DOC_SECURITY_CONTEXT_SERVICE = 'oro_api.api_doc.security_context';
    private const FILE_LOCATOR_SERVICE = 'file_locator';
    private const DOCUMENTATION_PROVIDER_SERVICE = 'oro_api.api_doc.documentation_provider';
    private const API_SOURCE_LISTENER_SERVICE = 'oro_api.listener.api_source';
    private const API_CACHE_MANAGER_SERVICE = 'oro_api.cache_manager';
    private const TWIG = 'twig';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$this->isApplicable($container)) {
            return;
        }

        $this->configureUnderlyingViews($container);
        $this->configureApiDocExtractor($container);
        $this->configureApiDocFormatters($container);
        $this->configureApiDocDataTypeConverter($container);
        $this->registerRoutingOptionsResolvers($container);
        $this->configureRequestTypeProvider($container);
        $this->configureApiSourceListener($container);
        $this->configureCacheManager($container);
    }

    private function getApiDocViews(ContainerBuilder $container): array
    {
        $config = DependencyInjectionUtil::getConfig($container);

        return $config['api_doc_views'];
    }

    private function isApplicable(ContainerBuilder $container): bool
    {
        // extractor
        if (!$container->hasDefinition(self::API_DOC_EXTRACTOR_SERVICE)) {
            return false;
        }
        $apiDocExtractorDef = $container->getDefinition(self::API_DOC_EXTRACTOR_SERVICE);
        if (!$this->getNewApiDocExtractorClass($apiDocExtractorDef->getClass())) {
            return false;
        }

        // HTML formatter
        if (!$container->hasDefinition(self::API_DOC_HTML_FORMATTER_SERVICE)) {
            return false;
        }

        $htmlFormatterDef = $container->getDefinition(self::API_DOC_HTML_FORMATTER_SERVICE);
        $formatterClass = $container->getParameterBag()->resolveValue($htmlFormatterDef->getClass());
        if (NelmioFormatter\HtmlFormatter::class !== ltrim($formatterClass, '/\\')) {
            return false;
        }

        if (!$container->hasDefinition(self::API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE)) {
            return false;
        }

        return true;
    }

    private function configureUnderlyingViews(ContainerBuilder $container): void
    {
        $underlyingViews = $this->getUnderlyingViews($container);
        foreach ($underlyingViews as $view => $underlyingView) {
            $this->registerUnderlyingViewHandler($container, $view, $underlyingView);
        }
        $container
            ->getDefinition(self::API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE)
            ->replaceArgument(2, $underlyingViews);
    }

    private function getUnderlyingViews(ContainerBuilder $container): array
    {
        $underlyingViews = [];
        $views = $this->getApiDocViews($container);
        foreach ($views as $name => $view) {
            if (\array_key_exists('underlying_view', $view) && $view['underlying_view']) {
                $underlyingViews[$name] = $view['underlying_view'];
            }
        }

        return $underlyingViews;
    }

    private function registerUnderlyingViewHandler(
        ContainerBuilder $container,
        string $view,
        string $underlyingView
    ): void {
        $container
            ->register(
                self::API_DOC_ANNOTATION_HANDLER_SERVICE . '.' . $view,
                AddApiDocViewAnnotationHandler::class
            )
            ->setArguments([$view, $underlyingView])
            ->setPublic(false)
            ->addTag(self::API_DOC_ANNOTATION_HANDLER_TAG_NAME);
    }

    private function configureApiDocExtractor(ContainerBuilder $container): void
    {
        $apiDocExtractorDef = $container->getDefinition(self::API_DOC_EXTRACTOR_SERVICE);
        $apiDocExtractorDef->setClass(
            $this->getNewApiDocExtractorClass($apiDocExtractorDef->getClass())
        );
        $apiDocExtractorDef->addMethodCall(
            'setRestDocViewDetector',
            [new Reference(self::REST_DOC_VIEW_DETECTOR_SERVICE)]
        );
        $apiDocExtractorDef->addMethodCall(
            'setRouteOptionsResolver',
            [new Reference(self::API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE)]
        );
        $apiDocExtractorDef->addMethodCall(
            'setApiDocAnnotationHandler',
            [new Reference(self::API_DOC_ANNOTATION_HANDLER_SERVICE)]
        );
    }

    private function configureRequestTypeProvider(ContainerBuilder $container): void
    {
        $requestTypeProviderDef = $container->getDefinition(self::API_DOC_REQUEST_TYPE_PROVIDER_SERVICE);
        $views = $this->getApiDocViews($container);
        foreach ($views as $name => $view) {
            if (!empty($view['request_type'])) {
                $requestTypeProviderDef->addMethodCall('mapViewToRequestType', [$name, $view['request_type']]);
            }
        }
    }

    private function configureApiSourceListener(ContainerBuilder $container): void
    {
        $config = DependencyInjectionUtil::getConfig($container);
        $container->getDefinition(self::API_SOURCE_LISTENER_SERVICE)
            ->setArgument('$excludedFeatures', $config['api_doc_cache']['excluded_features']);
    }

    private function configureCacheManager(ContainerBuilder $container): void
    {
        $config = DependencyInjectionUtil::getConfig($container);
        $resettableServiceIds = $config['api_doc_cache']['resettable_services'];
        if ($resettableServiceIds) {
            $cacheManagerDef = $container->getDefinition(self::API_CACHE_MANAGER_SERVICE);
            foreach ($resettableServiceIds as $resettableServiceId) {
                $cacheManagerDef->addMethodCall('addResettableService', [new Reference($resettableServiceId)]);
            }
        }
    }

    private function configureApiDocFormatters(ContainerBuilder $container): void
    {
        // rename default HTML formatter service
        $defaultHtmlFormatterDef = $container->getDefinition(self::API_DOC_HTML_FORMATTER_SERVICE);
        $container->removeDefinition(self::API_DOC_HTML_FORMATTER_SERVICE);
        $container->setDefinition(self::RENAMED_API_DOC_HTML_FORMATTER_SERVICE, $defaultHtmlFormatterDef);
        $isPublicService = $defaultHtmlFormatterDef->isPublic();
        $defaultHtmlFormatterDef->setPublic(false);
        $defaultHtmlFormatterDef->setClass(Formatter\HtmlFormatter::class);
        $defaultHtmlFormatterDef->addMethodCall(
            'setSecurityContext',
            [new Reference(self::API_DOC_SECURITY_CONTEXT_SERVICE)]
        );
        $defaultHtmlFormatterDef->addMethodCall(
            'setFileLocator',
            [new Reference(self::FILE_LOCATOR_SERVICE)]
        );
        $defaultHtmlFormatterDef->addMethodCall(
            'setDocumentationProvider',
            [new Reference(self::DOCUMENTATION_PROVIDER_SERVICE)]
        );
        $defaultHtmlFormatterDef->removeMethodCall('setTemplatingEngine');
        $defaultHtmlFormatterDef->addMethodCall(
            'setTwig',
            [new Reference(self::TWIG)]
        );

        // configure composite HTML formatter and set it as default one
        $compositeHtmlFormatterDef = $container->getDefinition(self::COMPOSITE_API_DOC_HTML_FORMATTER_SERVICE);
        $container->removeDefinition(self::COMPOSITE_API_DOC_HTML_FORMATTER_SERVICE);
        $container->setDefinition(self::API_DOC_HTML_FORMATTER_SERVICE, $compositeHtmlFormatterDef);
        $compositeHtmlFormatterDef->setPublic($isPublicService);

        // configure formatters according to views config
        $htmlFormatters = [];
        $views = $this->getApiDocViews($container);
        foreach ($views as $name => $view) {
            $htmlFormatter = $view['html_formatter'];
            unset($views[$name]['html_formatter']);
            $compositeHtmlFormatterDef->addMethodCall('addFormatter', [$name, new Reference($htmlFormatter)]);
            if (!\in_array($htmlFormatter, $htmlFormatters, true)) {
                $htmlFormatters[] = $htmlFormatter;
            }
        }
        foreach ($htmlFormatters as $htmlFormatter) {
            $container->getDefinition($htmlFormatter)->addMethodCall('setViews', [$views]);
        }
    }

    private function configureApiDocDataTypeConverter(ContainerBuilder $container): void
    {
        $config = DependencyInjectionUtil::getConfig($container);
        $defaultMapping = $config['api_doc_data_types'];

        $viewMappings = [];
        $views = $this->getApiDocViews($container);
        foreach ($views as $name => $view) {
            if (!empty($view['data_types'])) {
                $viewMappings[$name] = $view['data_types'];
            }
        }

        $container->getDefinition(self::API_DOC_DATA_TYPE_CONVERTER)
            ->setArgument('$defaultMapping', $defaultMapping)
            ->setArgument('$viewMappings', $viewMappings);
    }

    private function registerRoutingOptionsResolvers(ContainerBuilder $container): void
    {
        $services = [];
        $views = $container->getParameter(OroApiExtension::API_DOC_VIEWS_PARAMETER_NAME);
        $taggedServices = $container->findTaggedServiceIds(self::API_DOC_ROUTING_OPTIONS_RESOLVER_TAG_NAME);
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $view = $this->getRequiredAttribute(
                    $attributes,
                    'view',
                    $id,
                    self::API_DOC_ROUTING_OPTIONS_RESOLVER_TAG_NAME
                );
                if (!\in_array($view, $views, true)) {
                    throw new LogicException(sprintf(
                        'The "%s" is invalid value for attribute "view" of tag "%s". Service: "%s".'
                        . ' Possible values: %s.',
                        $view,
                        self::API_DOC_ROUTING_OPTIONS_RESOLVER_TAG_NAME,
                        $id,
                        implode(', ', $views)
                    ));
                }
                $services[$this->getPriorityAttribute($attributes)][] = [new Reference($id), $view];
            }
        }
        if (empty($services)) {
            return;
        }

        $services = $this->sortByPriorityAndFlatten($services);
        $container
            ->getDefinition(self::API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE)
            ->replaceArgument(0, $services);
    }

    private function getNewApiDocExtractorClass(string $currentClass): ?string
    {
        switch ($currentClass) {
            case NelmioExtractor\CachingApiDocExtractor::class:
                return Extractor\CachingApiDocExtractor::class;
            case NelmioExtractor\ApiDocExtractor::class:
                return Extractor\ApiDocExtractor::class;
            default:
                return null;
        }
    }
}
