<?php

namespace Oro\Bundle\NavigationBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\NavigationBundle\Entity\Repository\TitleRepository;
use Oro\Bundle\NavigationBundle\Entity\Title;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RouteChoiceType extends AbstractType
{
    const NAME = 'oro_route_choice';

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param RouterInterface $router
     * @param ManagerRegistry $registry
     * @param TranslatorInterface $translator
     */
    public function __construct(RouterInterface $router, ManagerRegistry $registry, TranslatorInterface $translator)
    {
        $this->router = $router;
        $this->registry = $registry;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
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
                    $titles = $this->loadRouteTitles($routes);
                    foreach ($choices as $routeName => $routeTitle) {
                        if (array_key_exists($routeName, $titles)) {
                            $choices[$routeName] = sprintf('%s (%s)', $routeTitle, $titles[$routeName]);
                        } elseif ($options['with_titles_only']) {
                            unset($choices[$routeName]);
                        }
                    }
                }

                return $choices;
            }
        );
    }

    /**
     * @param bool $withoutParametersOnly
     * @param null|string $optionsFilter
     * @param null|string $nameFilter
     * @param null|string $pathFilter
     * @return array
     */
    private function getFilteredRoutes(
        $withoutParametersOnly = true,
        $optionsFilter = null,
        $nameFilter = null,
        $pathFilter = null
    ) {
        $routes = $this->router->getRouteCollection();
        $filteredRoutes = [];
        foreach ($routes as $routeName => $route) {
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

    /**
     * @param Route $route
     * @return bool
     */
    private function isGetMethodAllowed(Route $route)
    {
        return count($route->getMethods()) === 0 || in_array('GET', $route->getMethods(), true);
    }

    /**
     * @param bool $withoutParametersOnly
     * @param Route $route
     * @return bool
     */
    private function isParametersAllowed($withoutParametersOnly, Route $route)
    {
        return !$withoutParametersOnly || strpos($route->getPath(), '{') === false;
    }

    /**
     * @param array $optionsFilter
     * @param Route $route
     * @return bool
     */
    private function isOptionsAllowed(array $optionsFilter, Route $route)
    {
        return !$optionsFilter || $optionsFilter === array_intersect_assoc($optionsFilter, $route->getOptions());
    }

    /**
     * @param string $nameFilter
     * @param string $routeName
     * @return bool
     */
    private function isNameAllowed($nameFilter, $routeName)
    {
        return !$nameFilter || preg_match($nameFilter, $routeName);
    }

    /**
     * @param string $pathFilter
     * @param Route $route
     * @return bool
     */
    private function isPathAllowed($pathFilter, Route $route)
    {
        return !$pathFilter || preg_match($pathFilter, $route->getPath());
    }

    /**
     * @param string $routeName
     * @return string
     */
    private function getRouteNameAsTitle($routeName)
    {
        return str_replace('_', ' ', ucwords($routeName, '_'));
    }

    /**
     * @param array $routes
     * @return array
     */
    private function loadRouteTitles(array $routes)
    {
        /** @var TitleRepository $repository */
        $repository = $this->registry
            ->getManagerForClass(Title::class)
            ->getRepository(Title::class);
        $titles = $repository->getTitles($routes);
        $result = [];
        foreach ($titles as $title) {
            $result[$title['route']] = $this->translator->trans($title['shortTitle']);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_choice';
    }
}
