<?php

namespace Oro\Bundle\UIBundle\Twig;

use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Twig\MenuExtension;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment as TwigEnvironment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for rendering tabs:
 *   - menuTabPanel
 *   - tabPanel
 */
class TabExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private const TEMPLATE = '@OroUI/tab_panel.html.twig';
    private const DEFAULT_WIDGET_TYPE = 'block';

    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'menuTabPanel',
                [$this, 'menuTabPanel'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'tabPanel',
                [$this, 'tabPanel'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            )
        ];
    }

    /**
     * @param TwigEnvironment $environment
     * @param string $menuName
     * @param array $options
     *
     * @return string
     */
    public function menuTabPanel(TwigEnvironment $environment, $menuName, $options = [])
    {
        $tabs = $this->getTabs($menuName, $options);

        if (empty($tabs)) {
            return '';
        }

        return $environment->render(self::TEMPLATE, ['tabs' => $tabs]);
    }

    /**
     * @param string $menuName
     * @param array  $options
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getTabs($menuName, $options = [])
    {
        /* @var MenuItem $menu */
        $menu = $this->getMenuExtension()->getMenu($menuName, [], $options);

        $tabs = [];
        foreach ($menu->getChildren() as $child) {
            if (!$child->isDisplayed()) {
                continue;
            }

            $url = $child->getUri();
            if (!$url) {
                $route = $child->getExtra('widgetRoute');
                if ($route) {
                    $routeParameters = array_merge(
                        $child->getExtra('widgetRouteParameters', []),
                        $options
                    );

                    $routeParametersMap = $child->getExtra('widgetRouteParametersMap', []);
                    foreach ($routeParametersMap as $routeParameter => $optionParameter) {
                        if (isset($options[$optionParameter])) {
                            $routeParameters[$routeParameter] = $options[$optionParameter];

                            unset($routeParameters[$optionParameter]);
                        }
                    }

                    $url = $this->getRouter()->generate($route, $routeParameters);
                } else {
                    throw new InvalidArgumentException(
                        sprintf('Extra parameter "widgetRoute" should be defined for %s', $child->getName())
                    );
                }
            }

            if ($this->getAuthorizationChecker()->isGranted($child->getExtra('widgetAcl'))) {
                $label = $child->getLabel();
                if (!empty($label)) {
                    $label = $this->getTranslator()->trans($label);
                }
                $tabs[] = [
                    'alias'      => $child->getName(),
                    'label'      => $label,
                    'widgetType' => $child->getExtra('widgetType', self::DEFAULT_WIDGET_TYPE),
                    'url'        => $url
                ];
            }
        }

        if (empty($tabs)) {
            $menu->setDisplay(false);
        }

        return $tabs;
    }

    /**
     * @param TwigEnvironment $environment
     * @param array $tabs
     * @param array $options
     * @return string
     */
    public function tabPanel(TwigEnvironment $environment, $tabs, array $options = [])
    {
        return $environment->render(
            self::TEMPLATE,
            [
                'tabs' => $tabs,
                'options' => $options,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_menu.twig.extension' => MenuExtension::class,
            RouterInterface::class,
            AuthorizationCheckerInterface::class,
            TranslatorInterface::class,
        ];
    }

    protected function getMenuExtension(): MenuExtension
    {
        return $this->container->get('oro_menu.twig.extension');
    }

    protected function getRouter(): RouterInterface
    {
        return $this->container->get(RouterInterface::class);
    }

    protected function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->container->get(AuthorizationCheckerInterface::class);
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }
}
