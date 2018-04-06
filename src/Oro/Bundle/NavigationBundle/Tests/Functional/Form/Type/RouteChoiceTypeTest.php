<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Form\Type;

use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @dbIsolationPerTest
 */
class RouteChoiceTypeTest extends WebTestCase
{
    /** @var FormFactoryInterface */
    private $formFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->formFactory = $this->getContainer()->get('form.factory');
    }

    /**
     * @dataProvider createViewDataProvider
     *
     * @param array $options
     * @param array $expectedChoices
     */
    public function testCreateView(array $options, array $expectedChoices)
    {
        $form = $this->formFactory->create(RouteChoiceType::class, null, $options);

        $formView = $form->createView();
        $this->assertEquals($expectedChoices, $formView->vars['choices']);
    }

    /**
     * @return array
     */
    public function createViewDataProvider()
    {
        return [
            'with name filter' => [
                'options' => [
                    'menu_name' => 'application_menu',
                    'name_filter' => '/^oro_navigation\w+$/'
                ],
                'expectedChoices' => [
                    $this->getChoiceView(
                        'oro_navigation_global_menu_index',
                        'Oro Navigation Global Menu Index (Menus - Menus - System)'
                    ),
                    $this->getChoiceView(
                        'oro_navigation_user_menu_index',
                        'Oro Navigation User Menu Index (Menus - %username%)'
                    ),
                ]
            ],
            'without titles' => [
                'options' => [
                    'menu_name' => 'application_menu',
                    'name_filter' => '/^oro_navigation\w+$/',
                    'add_titles' => false
                ],
                'expectedChoices' => [
                    $this->getChoiceView('oro_navigation_global_menu_index', 'Oro Navigation Global Menu Index'),
                    $this->getChoiceView('oro_navigation_user_menu_index', 'Oro Navigation User Menu Index'),
                ]
            ],
            'with path filter' => [
                'options' => [
                    'menu_name' => 'application_menu',
                    'name_filter' => '/^oro_navigation\w+$/',
                    'path_filter' => '/^(\/admin)?\/menu\/user\/$/',
                    'add_titles' => false
                ],
                'expectedChoices' => [
                    $this->getChoiceView('oro_navigation_user_menu_index', 'Oro Navigation User Menu Index'),
                ]
            ],
            'with options filter' => [
                'options' => [
                    'menu_name' => 'application_menu',
                    'name_filter' => '/^oro_navigation\w+$/',
                    'options_filter' => [
                        'compiler_class' => 'Symfony\Component\Routing\RouteCompiler'
                    ],
                    'add_titles' => false
                ],
                'expectedChoices' => [
                    $this->getChoiceView('oro_navigation_global_menu_index', 'Oro Navigation Global Menu Index'),
                    $this->getChoiceView('oro_navigation_user_menu_index', 'Oro Navigation User Menu Index')
                ]
            ],
            'with parameters' => [
                'options' => [
                    'menu_name' => 'application_menu',
                    'name_filter' => '/^oro_navigation_global\w+$/',
                    'without_parameters_only' => false,
                    'add_titles' => false
                ],
                'expectedChoices' => [
                    $this->getChoiceView('oro_navigation_global_menu_index', 'Oro Navigation Global Menu Index'),
                    $this->getChoiceView('oro_navigation_global_menu_view', 'Oro Navigation Global Menu View'),
                    $this->getChoiceView('oro_navigation_global_menu_create', 'Oro Navigation Global Menu Create'),
                    $this->getChoiceView('oro_navigation_global_menu_update', 'Oro Navigation Global Menu Update'),
                    $this->getChoiceView('oro_navigation_global_menu_move', 'Oro Navigation Global Menu Move'),
                ]
            ],
            'mixed with titles and without' => [
                'options' => [
                    'menu_name' => 'application_menu',
                    'name_filter' => '/^oro_navigation_global\w+$/',
                    'without_parameters_only' => false,
                    'with_titles_only' => false
                ],
                'expectedChoices' => [
                    $this->getChoiceView(
                        'oro_navigation_global_menu_index',
                        'Oro Navigation Global Menu Index (Menus - Menus - System)'
                    ),
                    $this->getChoiceView(
                        'oro_navigation_global_menu_view',
                        'Oro Navigation Global Menu View (%title% - Menus - Menus - System)'
                    ),
                    $this->getChoiceView(
                        'oro_navigation_global_menu_create',
                        'Oro Navigation Global Menu Create (Create Menu Item - Menus - Menus - System)'
                    ),
                    $this->getChoiceView(
                        'oro_navigation_global_menu_update',
                        'Oro Navigation Global Menu Update (%title% - Edit - Menus - Menus - System)'
                    ),
                    $this->getChoiceView('oro_navigation_global_menu_move', 'Oro Navigation Global Menu Move'),
                ]
            ],
        ];
    }

    /**
     * @param string $routeName
     * @param string $label
     *
     * @return ChoiceView
     */
    private function getChoiceView($routeName, $label)
    {
        return new ChoiceView($routeName, $routeName, $label);
    }
}
