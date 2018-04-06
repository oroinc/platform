<?php

namespace Oro\Bundle\UIBundle\Twig;

use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Twig\MenuExtension;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

class TabExtension extends \Twig_Extension
{
    const TEMPLATE = 'OroUIBundle::tab_panel.html.twig';
    const DEFAULT_WIDGET_TYPE = 'block';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return MenuExtension
     */
    protected function getMenuExtension()
    {
        return $this->container->get('oro_menu.twig.extension');
    }

    /**
     * @return RouterInterface
     */
    protected function getRouter()
    {
        return $this->container->get('router');
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    protected function getAuthorizationChecker()
    {
        return $this->container->get('security.authorization_checker');
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->container->get('translator');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'menuTabPanel',
                [$this, 'menuTabPanel'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'tabPanel',
                [$this, 'tabPanel'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            )
        ];
    }

    /**
     * @param \Twig_Environment $environment
     * @param string $menuName
     * @param array $options
     *
     * @return string
     */
    public function menuTabPanel(\Twig_Environment $environment, $menuName, $options = [])
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
     * @throws \Symfony\Component\Validator\Exception\InvalidArgumentException
     * @return array
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

            if (!$url = $child->getUri()) {
                if ($route = $child->getExtra('widgetRoute')) {
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
     * @param \Twig_Environment $environment
     * @param array $tabs
     * @param array $options
     * @return string
     */
    public function tabPanel(\Twig_Environment $environment, $tabs, array $options = [])
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
    public function getName()
    {
        return 'oro_ui.tab_panel';
    }
}
