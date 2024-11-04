<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Describer;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Generator\OpenApiGenerator;
use Oro\Bundle\ApiBundle\DependencyInjection\OroApiExtension;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers services that is used to generate OpenAPI specifications for all API views.
 */
class OpenApiCompilerPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        $this->registerDataTypeDescribeHelper($container);

        $requestHeaders = [];
        $viewLabels = [];
        $views = $this->getApiDocViews($container);
        foreach ($views as $view => $config) {
            $requestType = $config['request_type'];
            if (!$requestType || !\in_array(RequestType::JSON_API, $requestType, true)) {
                // OpenAPI specification generation is implemented for JSON:API only
                continue;
            }
            $this->registerGenerator($container, $view);
            $this->registerModelDescriber($container, $view, $requestType);
            $this->registerCommonDescriber($container, $view, $requestType);
            $this->registerDocumentationDescriber(
                $container,
                $view,
                $requestType,
                $config['documentation_path'] ?? null
            );
            $this->registerApiDocDescriber($container, $view, $requestType);
            if (isset($config['label'])) {
                $viewLabels[$view] = $config['label'];
            }
            if (isset($config['headers'])) {
                $requestHeaders[$view] = $config['headers'];
            }
        }
        $container->getDefinition('oro_api.api_doc.open_api.request_header_provider')
            ->setArgument(0, $requestHeaders);
        $container->getDefinition('oro_api.api_doc.open_api.name_provider')
            ->setArgument(0, $viewLabels);
    }

    private function registerDataTypeDescribeHelper(ContainerBuilder $container): void
    {
        $openApiConfig = $this->getOpenApiConfig($container);
        $container
            ->register(
                'oro_api.api_doc.open_api.data_type_describe_helper',
                Describer\DataTypeDescribeHelper::class
            )
            ->addArgument($openApiConfig['data_types'])
            ->addArgument($openApiConfig['data_type_aliases'])
            ->addArgument($openApiConfig['data_type_plural_map'])
            ->addArgument($openApiConfig['data_type_pattern_map'])
            ->addArgument($openApiConfig['data_type_range_value_patterns']);
    }

    private function registerGenerator(ContainerBuilder $container, string $view): void
    {
        $container->register('oro_api.api_doc.open_api.generator.' . $view, OpenApiGenerator::class)
            ->addArgument(new TaggedIteratorArgument('oro.api.open_api.describer.' . $view))
            ->addArgument(new TaggedIteratorArgument('oro.api.open_api.model_describer.' . $view))
            ->addArgument(new Reference('oro_api.api_doc.open_api.data_type_describe_helper'))
            ->addTag('oro.api.open_api.generator', ['view' => $view]);
    }

    private function registerModelDescriber(ContainerBuilder $container, string $view, array $requestType): void
    {
        if (\in_array(RequestType::JSON_API, $requestType, true)) {
            $container
                ->register(
                    'oro_api.api_doc.open_api.model_describer.' . $view,
                    Describer\JsonApi\ModelDescriber::class
                )
                ->addArgument(new Reference('oro_api.api_doc.open_api.data_type_describe_helper'))
                ->addArgument(new Reference('oro_api.api_doc.open_api.resource_info_provider'))
                ->addTag('oro.api.open_api.model_describer.' . $view, ['priority' => 100]);
        }
    }

    private function registerCommonDescriber(ContainerBuilder $container, string $view, array $requestType): void
    {
        if (\in_array(RequestType::JSON_API, $requestType, true)) {
            $container
                ->register(
                    'oro_api.api_doc.open_api.describer.common.' . $view,
                    Describer\JsonApi\CommonDescriber::class
                )
                ->addTag('oro.api.open_api.describer.' . $view, ['priority' => 100]);
        }
    }

    private function registerDocumentationDescriber(
        ContainerBuilder $container,
        string $view,
        array $requestType,
        ?string $documentationUri
    ): void {
        $openApiConfig = $this->getOpenApiConfig($container);
        $container
            ->register(
                'oro_api.api_doc.open_api.describer.documentation.' . $view,
                Describer\DocumentationDescriber::class
            )
            ->addArgument($openApiConfig['version'] ?? null)
            ->addArgument($documentationUri)
            ->addArgument($view)
            ->addArgument($requestType)
            ->addArgument(new Reference('oro_api.api_doc.open_api.name_provider'))
            ->addArgument(new Reference('oro_api.api_doc.documentation_provider'))
            ->addTag('oro.api.open_api.describer.' . $view, ['priority' => 60]);
    }

    private function registerApiDocDescriber(ContainerBuilder $container, string $view, array $requestType): void
    {
        $mediaType = 'application/json';
        $modelNormalizerService = 'oro_api.api_doc.open_api.model_normalizer';
        if (\in_array(RequestType::JSON_API, $requestType, true)) {
            $mediaType = 'application/vnd.api+json';
            $modelNormalizerService = 'oro_api.api_doc.open_api.model_normalizer.json_api';
        }

        $container->register('oro_api.api_doc.open_api.describer.api_doc.' . $view, Describer\ApiDocDescriber::class)
            ->addArgument(new Reference('nelmio_api_doc.extractor.api_doc_extractor'))
            ->addArgument(new Parameter(OroApiExtension::REST_API_PREFIX_PARAMETER_NAME))
            ->addArgument($mediaType)
            ->addArgument(new Reference('oro_api.api_doc.open_api.request_header_provider'))
            ->addArgument(new Reference('oro_api.api_doc.open_api.response_header_provider'))
            ->addArgument(new Reference($modelNormalizerService))
            ->addArgument(new Reference('oro_api.api_doc.open_api.data_type_describe_helper'))
            ->addArgument(new Reference('oro_api.api_doc.open_api.resource_info_provider'))
            ->addArgument(new Reference('oro_api.rest.doc_view_detector'))
            ->addTag('oro.api.open_api.describer.' . $view, ['priority' => 50]);
    }

    private function getApiDocViews(ContainerBuilder $container): array
    {
        $config = DependencyInjectionUtil::getConfig($container);

        return $config['api_doc_views'];
    }

    private function getOpenApiConfig(ContainerBuilder $container): array
    {
        $config = DependencyInjectionUtil::getConfig($container);

        return $config['open_api'];
    }
}
