<?php

namespace Oro\Bundle\NavigationBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\NavigationBundle\Provider\TitleServiceInterface;
use Oro\Bundle\NavigationBundle\Provider\TitleTranslator;
use Oro\Bundle\NavigationBundle\Title\TitleReader\TitleReaderRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Choice form type to choose route from route collection.
 */
class RouteChoiceType extends AbstractType
{
    private const NAME = 'oro_route_choice';

    private RouterInterface $router;
    private TitleTranslator $titleTranslator;
    private TitleReaderRegistry $readerRegistry;
    private TitleServiceInterface $titleService;
    private ?RouteCollection $routeCollection = null;
    private CacheInterface $cache;

    public function __construct(
        RouterInterface $router,
        TitleReaderRegistry $readerRegistry,
        TitleTranslator $titleTranslator,
        TitleServiceInterface $titleService,
        CacheInterface $cache
    ) {
        $this->router = $router;
        $this->readerRegistry = $readerRegistry;
        $this->titleTranslator = $titleTranslator;
        $this->titleService = $titleService;
        $this->cache = $cache;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('menu_name');

        $resolver->setDefault('path_filter', null);
        $resolver->setDefault('name_filter', '/^oro_\w+$/');
        $resolver->setDefault('options_filter', []);
        $resolver->setDefault('without_parameters_only', true);
        $resolver->setDefault('add_titles', true);
        $resolver->setDefault('with_titles_only', true);

        $resolver->setAllowedTypes('without_parameters_only', ['bool']);
        $resolver->setAllowedTypes('add_titles', ['bool']);
        $resolver->setAllowedTypes('with_titles_only', ['bool']);
        $resolver->setAllowedTypes('name_filter', ['string', 'null']);
        $resolver->setAllowedTypes('path_filter', ['string', 'null']);
        $resolver->setAllowedTypes('options_filter', ['array']);

        $resolver->setDefault(
            'choices',
            function (Options $options) {
                $routes = $this->getFilteredRoutes(
                    $options['without_parameters_only'],
                    $options['options_filter'],
                    $options['name_filter'],
                    $options['path_filter']
                );

                $choices = [];
                foreach ($routes as $route) {
                    $choices[$route] = $this->getRouteNameAsTitle($route);
                }

                if ($options['add_titles']) {
                    $titles = $this->loadRouteTitles($routes, $options['menu_name']);
                    foreach ($choices as $routeName => $routeTitle) {
                        if (array_key_exists($routeName, $titles)) {
                            $choices[$routeName] = sprintf('%s (%s)', $routeTitle, $titles[$routeName]);
                        } elseif ($options['with_titles_only']) {
                            unset($choices[$routeName]);
                        }
                    }
                }

                return array_flip($choices);
            }
        );
    }

    private function getFilteredRoutes(
        bool $withoutParametersOnly = true,
        ?array $optionsFilter = null,
        ?string $nameFilter = null,
        ?string $pathFilter = null
    ): array {
        $cacheKey = md5(json_encode(func_get_args()));
        return $this->cache->get(
            $cacheKey,
            function () use ($withoutParametersOnly, $optionsFilter, $nameFilter, $pathFilter) {
                return $this->filterRouteCollection(
                    $withoutParametersOnly,
                    $optionsFilter,
                    $nameFilter,
                    $pathFilter
                );
            }
        );
    }

    private function filterRouteCollection(
        bool $withoutParametersOnly = true,
        ?array $optionsFilter = null,
        ?string $nameFilter = null,
        ?string $pathFilter = null
    ): array {
        $filteredRoutes = [];
        foreach ($this->getRouteCollection() as $routeName => $route) {
            $required = $this->isGetMethodAllowed($route)
                && $this->isParametersAllowed($withoutParametersOnly, $route)
                && $this->isOptionsAllowed($optionsFilter, $route)
                && $this->isNameAllowed($nameFilter, $routeName)
                && $this->isPathAllowed($pathFilter, $route);

            if ($required) {
                $filteredRoutes[] = $routeName;
            }
        }

        return $filteredRoutes;
    }

    private function isGetMethodAllowed(Route $route): bool
    {
        return count($route->getMethods()) === 0 || in_array('GET', $route->getMethods(), true);
    }

    private function isParametersAllowed(bool $withoutParametersOnly, Route $route): bool
    {
        return !$withoutParametersOnly || !str_contains($route->getPath(), '{');
    }

    private function isOptionsAllowed(?array $optionsFilter, Route $route): bool
    {
        return !$optionsFilter || $optionsFilter === array_intersect_assoc($optionsFilter, $route->getOptions());
    }

    private function isNameAllowed(?string $nameFilter, string $routeName): bool
    {
        return !$nameFilter || preg_match($nameFilter, $routeName);
    }

    private function isPathAllowed(?string $pathFilter, Route $route): bool
    {
        return !$pathFilter || preg_match($pathFilter, $route->getPath());
    }

    private function getRouteNameAsTitle(string $routeName): string
    {
        return str_replace('_', ' ', ucwords($routeName, '_'));
    }

    private function loadRouteTitles(array $routes, string $menuName): array
    {
        $titles = [];
        foreach ($routes as $routeName) {
            $title = $this->readerRegistry->getTitleByRoute($routeName);
            if ($title) {
                $titles[$routeName] = $this->titleTranslator
                    ->trans($this->titleService->createTitle($routeName, $title, $menuName));
            }
        }

        return $titles;
    }

    private function getRouteCollection(): RouteCollection
    {
        if ($this->routeCollection === null) {
            $this->routeCollection = $this->router->getRouteCollection();
        }

        return $this->routeCollection;
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    public function getParent(): string
    {
        return Select2ChoiceType::class;
    }
}
