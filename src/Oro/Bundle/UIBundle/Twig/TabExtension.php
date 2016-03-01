<?php

namespace Oro\Bundle\UIBundle\Twig;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

use Knp\Menu\MenuItem;

use Oro\Bundle\NavigationBundle\Twig\MenuExtension;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class TabExtension extends \Twig_Extension
{
    const TEMPLATE = 'OroUIBundle::tab_panel.html.twig';
    const DEFAULT_WIDGET_TYPE = 'block';

    /**
     * @var MenuExtension
     */
    protected $menuExtension;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        MenuExtension $menuExtension,
        RouterInterface $router,
        SecurityFacade $securityFacade,
        TranslatorInterface $translator
    ) {
        $this->menuExtension  = $menuExtension;
        $this->router         = $router;
        $this->securityFacade = $securityFacade;
        $this->translator     = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'menuTabPanel' => new \Twig_Function_Method(
                $this,
                'menuTabPanel',
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true
                ]
            ),
            'tabPanel' => new \Twig_Function_Method(
                $this,
                'tabPanel',
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true
                ]
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
        $menu = $this->menuExtension->getMenu($menuName, [], $options);

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

                    $url = $this->router->generate($route, $routeParameters);
                } else {
                    throw new InvalidArgumentException(
                        sprintf('Extra parameter "widgetRoute" should be defined for %s', $child->getName())
                    );
                }
            }

            if ($this->securityFacade->isGranted($child->getExtra('widgetAcl'))) {
                $label = $child->getLabel();
                if (!empty($label)) {
                    $label = $this->translator->trans($label);
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
