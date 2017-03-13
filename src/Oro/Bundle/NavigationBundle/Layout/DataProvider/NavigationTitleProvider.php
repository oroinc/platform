<?php

namespace Oro\Bundle\NavigationBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\NavigationBundle\Provider\TitleService;

class NavigationTitleProvider
{
    const FRONTEND_MENU_NAME = 'frontend_menu';

    /** @var TitleService */
    private $titleService;

    /** @var ConfigManager */
    private $userConfigManager;

    /**
     * @param TitleService  $titleService
     * @param ConfigManager $userConfigManager
     */
    public function __construct(
        TitleService $titleService,
        ConfigManager $userConfigManager
    ) {
        $this->titleService = $titleService;
        $this->userConfigManager = $userConfigManager;
    }

    /**
     * Load title template from config values
     *
     * @param string $routeName
     * @param array  $params
     *
     * @return string
     */
    public function getTitle($routeName, array $params = [])
    {
        $this->titleService->loadByRoute($routeName, self::FRONTEND_MENU_NAME);
        $this->titleService->setParams($params);

        $title = $this->titleService->render([], null, null, null, true);

        $delimiter  = ' ' . $this->userConfigManager->get('oro_navigation.title_delimiter') . ' ';

        return trim($title, $delimiter);
    }
}
