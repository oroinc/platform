<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Formatter\OpenApiFormatterRegistry;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Generator\OpenApiGeneratorRegistry;
use Oro\Bundle\ApiBundle\ApiDoc\OpenApi\Provider\OpenApiSpecificationNameProviderInterface;
use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides OpenAPI related choices translated on the current system language.
 */
class OpenApiChoicesProvider
{
    private TranslatorInterface $translator;
    private OpenApiGeneratorRegistry $generatorRegistry;
    private OpenApiFormatterRegistry $formatterRegistry;
    private OpenApiSpecificationNameProviderInterface $viewNameProvider;
    private array $choices = [];

    public function __construct(
        TranslatorInterface $translator,
        OpenApiGeneratorRegistry $generatorRegistry,
        OpenApiFormatterRegistry $formatterRegistry,
        OpenApiSpecificationNameProviderInterface $viewNameProvider
    ) {
        $this->translator = $translator;
        $this->generatorRegistry = $generatorRegistry;
        $this->formatterRegistry = $formatterRegistry;
        $this->viewNameProvider = $viewNameProvider;
    }

    public function getAvailableStatusChoices(): array
    {
        $cacheKey = 'statuses';
        if (!isset($this->choices[$cacheKey])) {
            $this->choices[$cacheKey] = $this->getChoices([
                'published',
                OpenApiSpecification::STATUS_RENEWING,
                OpenApiSpecification::STATUS_CREATING,
                OpenApiSpecification::STATUS_CREATED,
                OpenApiSpecification::STATUS_FAILED
            ], 'oro.api.open_api.statuses.');
        }

        return $this->choices[$cacheKey];
    }

    public function getAvailableFormatChoices(): array
    {
        $cacheKey = 'formats';
        if (!isset($this->choices[$cacheKey])) {
            $this->choices[$cacheKey] = $this->getChoices(
                $this->formatterRegistry->getFormats(),
                'oro.api.open_api.formats.'
            );
        }

        return $this->choices[$cacheKey];
    }

    public function getAvailableViewChoices(): array
    {
        $cacheKey = 'views';
        if (!isset($this->choices[$cacheKey])) {
            $choices = [];
            $views = $this->generatorRegistry->getViews();
            foreach ($views as $view) {
                $choices[$this->getOpenApiSpecificationName($view)] = $view;
            }
            ksort($choices, SORT_STRING);
            $this->choices[$cacheKey] = $choices;
        }

        return $this->choices[$cacheKey];
    }

    private function getChoices(array $items, string $labelPrefix): array
    {
        $choices = [];
        foreach ($items as $item) {
            $choices[$this->translator->trans($labelPrefix . $item)] = $item;
        }

        return $choices;
    }

    public function getOpenApiSpecificationName(string $view): string
    {
        $key = sprintf('oro.api.open_api.views.%s.label', $view);
        $name = $this->translator->trans($key);
        if ($name === $key) {
            $name = $this->viewNameProvider->getOpenApiSpecificationName($view);
        }

        return $name;
    }
}
