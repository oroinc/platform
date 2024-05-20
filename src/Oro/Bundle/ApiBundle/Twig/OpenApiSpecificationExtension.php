<?php

namespace Oro\Bundle\ApiBundle\Twig;

use Oro\Bundle\ApiBundle\ApiDoc\EntityNameProvider;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\Provider\OpenApiChoicesProvider;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters for OpenAPI specification management:
 *   - oro_open_api_status
 *   - oro_open_api_format
 *   - oro_open_api_view
 *   - oro_open_api_entities
 */
class OpenApiSpecificationExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('oro_open_api_status', [$this, 'getOpenApiStatus']),
            new TwigFilter('oro_open_api_format', [$this, 'getOpenApiFormat']),
            new TwigFilter('oro_open_api_view', [$this, 'getOpenApiView']),
            new TwigFilter('oro_open_api_entities', [$this, 'getOpenApiEntities'], ['is_safe' => ['html1']])
        ];
    }

    public function getOpenApiStatus(string $status): string
    {
        $statuses = array_flip($this->getOpenApiChoicesProvider()->getAvailableStatusChoices());

        return $statuses[$status];
    }

    public function getOpenApiFormat(string $format): string
    {
        $formats = array_flip($this->getOpenApiChoicesProvider()->getAvailableFormatChoices());

        return $formats[$format];
    }

    public function getOpenApiView(string $view): string
    {
        $views = array_flip($this->getOpenApiChoicesProvider()->getAvailableViewChoices());

        return $views[$view];
    }

    public function getOpenApiEntities(?array $entityTypes, string $view): string
    {
        if (!$entityTypes) {
            return $this->getTranslator()->trans('All');
        }

        $docViewDetector = $this->getDocViewDetector();
        $previousView = $docViewDetector->getView();
        if ($previousView === $view) {
            $entityNames = $this->convertToEntityNames($entityTypes, $docViewDetector->getRequestType());
        } else {
            $docViewDetector->setView($view);
            try {
                $entityNames = $this->convertToEntityNames($entityTypes, $docViewDetector->getRequestType());
            } finally {
                $docViewDetector->setView($previousView);
            }
        }

        return '<span class="nowrap">' . implode('</span>; <span class="nowrap">', $entityNames) . '</span>';
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'oro_api.open_api_choices_provider' => OpenApiChoicesProvider::class,
            'oro_api.value_normalizer' => ValueNormalizer::class,
            'oro_api.entity_name_provider' => EntityNameProvider::class,
            'oro_api.rest.doc_view_detector' => EntityNameProvider::class,
            TranslatorInterface::class
        ];
    }

    private function getOpenApiChoicesProvider(): OpenApiChoicesProvider
    {
        return $this->container->get('oro_api.open_api_choices_provider');
    }

    private function getValueNormalizer(): ValueNormalizer
    {
        return $this->container->get('oro_api.value_normalizer');
    }

    private function getEntityNameProvider(): EntityNameProvider
    {
        return $this->container->get('oro_api.entity_name_provider');
    }

    private function getDocViewDetector(): RestDocViewDetector
    {
        return $this->container->get('oro_api.rest.doc_view_detector');
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }

    private function convertToEntityNames(array $entityTypes, RequestType $requestType): array
    {
        $entityNames = [];
        $valueNormalizer = $this->getValueNormalizer();
        $entityNameProvider = $this->getEntityNameProvider();
        foreach ($entityTypes as $entityType) {
            $entityClass = ValueNormalizerUtil::tryConvertToEntityClass($valueNormalizer, $entityType, $requestType);
            $entityNames[] = $entityClass
                ? $entityNameProvider->getEntityName($entityClass)
                : $entityType;
        }

        return $entityNames;
    }
}
