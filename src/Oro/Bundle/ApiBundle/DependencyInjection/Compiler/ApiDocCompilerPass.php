<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Nelmio\ApiDocBundle\Extractor as NelmioExtractor;
use Nelmio\ApiDocBundle\Formatter as NelmioFormatter;
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
    private const API_DOC_EXTRACTOR_SERVICE                 = 'nelmio_api_doc.extractor.api_doc_extractor';
    private const API_DOC_REQUEST_TYPE_PROVIDER_SERVICE     = 'oro_api.rest.request_type_provider';
    private const API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE  = 'oro_api.rest.chain_routing_options_resolver';
    private const API_DOC_ROUTING_OPTIONS_RESOLVER_TAG_NAME = 'oro.api.routing_options_resolver';
    private const API_DOC_ANNOTATION_HANDLER_SERVICE        = 'oro_api.rest.api_doc_annotation_handler';
    private const API_DOC_ANNOTATION_HANDLER_TAG_NAME       = 'oro.api.api_doc_annotation_handler';
    private const REST_DOC_VIEW_DETECTOR_SERVICE            = 'oro_api.rest.doc_view_detector';
    private const REQUEST_TYPE_PROVIDER_TAG                 = 'oro.api.request_type_provider';
    private const API_DOC_HTML_FORMATTER_SERVICE            = 'nelmio_api_doc.formatter.html_formatter';
    private const RENAMED_API_DOC_HTML_FORMATTER_SERVICE    = 'oro_api.api_doc.formatter.html_formatter.nelmio';
    private const COMPOSITE_API_DOC_HTML_FORMATTER_SERVICE  = 'oro_api.api_doc.formatter.html_formatter.composite';
    private const API_DOC_SECURITY_CONTEXT_SERVICE          = 'oro_api.api_doc.security_context';
    private const FILE_LOCATOR_SERVICE                      = 'file_locator';
    private const DOCUMENTATION_PROVIDER_SERVICE            = 'oro_api.api_doc.documentation_provider';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$this->isApplicable($container)) {
            return;
        }

        $this->configureApiDocAnnotationHandler($container);
        $this->configureApiDocExtractor($container);
        $this->configureHtmlFormatter($container);
        $this->registerRoutingOptionsResolvers($container);
        $this->registerRequestTypeProviders($container);
        $this->configureRequestTypeProvider($container);
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getApiDocViews(ContainerBuilder $container): array
    {
        $config = DependencyInjectionUtil::getConfig($container);

        return $config['api_doc_views'];
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return bool
     */
    private function isApplicable(ContainerBuilder $container)
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
        if (NelmioFormatter\HtmlFormatter::class !== $htmlFormatterDef->getClass()) {
            return false;
        }

        if (!$container->hasDefinition(self::API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE)) {
            return false;
        }

        return true;
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureApiDocAnnotationHandler(ContainerBuilder $container)
    {
        DependencyInjectionUtil::registerTaggedServices(
            $container,
            self::API_DOC_ANNOTATION_HANDLER_SERVICE,
            self::API_DOC_ANNOTATION_HANDLER_TAG_NAME,
            'addHandler'
        );
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureApiDocExtractor(ContainerBuilder $container)
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

    /**
     * @param ContainerBuilder $container
     */
    private function configureRequestTypeProvider(ContainerBuilder $container)
    {
        $requestTypeProviderDef = $container->getDefinition(self::API_DOC_REQUEST_TYPE_PROVIDER_SERVICE);
        $views = $this->getApiDocViews($container);
        foreach ($views as $name => $view) {
            if (!empty($view['request_type'])) {
                $requestTypeProviderDef->addMethodCall('mapViewToRequestType', [$name, $view['request_type']]);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureHtmlFormatter(ContainerBuilder $container)
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
            if (!in_array($htmlFormatter, $htmlFormatters, true)) {
                $htmlFormatters[] = $htmlFormatter;
            }
        }
        foreach ($htmlFormatters as $htmlFormatter) {
            $container->getDefinition($htmlFormatter)->addMethodCall('setViews', [$views]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function registerRoutingOptionsResolvers(ContainerBuilder $container)
    {
        $chainServiceDef = DependencyInjectionUtil::findDefinition(
            $container,
            self::API_DOC_ROUTING_OPTIONS_RESOLVER_SERVICE
        );
        if (null !== $chainServiceDef) {
            $services = [];
            $views = $container->getParameter(OroApiExtension::API_DOC_VIEWS_PARAMETER_NAME);
            $taggedServices = $container->findTaggedServiceIds(self::API_DOC_ROUTING_OPTIONS_RESOLVER_TAG_NAME);
            foreach ($taggedServices as $id => $attributes) {
                foreach ($attributes as $attribute) {
                    $view = DependencyInjectionUtil::getRequiredAttribute(
                        $attribute,
                        'view',
                        $id,
                        self::API_DOC_ROUTING_OPTIONS_RESOLVER_TAG_NAME
                    );
                    if (!in_array($view, $views, true)) {
                        throw new LogicException(sprintf(
                            'The "%s" is invalid value for attribute "view" of tag "%s". Service: "%s".'
                            . ' Possible values: %s.',
                            $view,
                            self::API_DOC_ROUTING_OPTIONS_RESOLVER_TAG_NAME,
                            $id,
                            implode(', ', $views)
                        ));
                    }
                    $services[DependencyInjectionUtil::getPriority($attribute)][] = [new Reference($id), $view];
                }
            }
            if (empty($services)) {
                return;
            }

            $services = DependencyInjectionUtil::sortByPriorityAndFlatten($services);
            foreach ($services as $service) {
                $chainServiceDef->addMethodCall('addResolver', $service);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function registerRequestTypeProviders(ContainerBuilder $container)
    {
        DependencyInjectionUtil::registerTaggedServices(
            $container,
            self::REST_DOC_VIEW_DETECTOR_SERVICE,
            self::REQUEST_TYPE_PROVIDER_TAG,
            'addRequestTypeProvider'
        );
    }

    /**
     * @param string $currentClass
     *
     * @return string|null
     */
    private function getNewApiDocExtractorClass($currentClass)
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
