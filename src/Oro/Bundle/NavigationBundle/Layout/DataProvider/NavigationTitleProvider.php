<?php

namespace Oro\Bundle\NavigationBundle\Layout\DataProvider;

use Oro\Bundle\NavigationBundle\Provider\TitleService;

class NavigationTitleProvider
{
    /**
     * @var TitleService
     */
    private $titleService;

    /**
     * @param TitleService  $titleService
     */
    public function __construct(
        TitleService $titleService
    ) {
        $this->titleService = $titleService;
    }


    /**
     * Load title template from config values
     *
     * @param string $routeName
     * @param array  $params
     *
     * @return string
     */
    public function getTitle($routeName, $params = [])
    {
        $this->titleService->loadByRoute($routeName);
        $this->titleService->setParams($params);

        $title = $this->titleService
            ->render([], null, null, null, true);

        return $title;
    }
}
