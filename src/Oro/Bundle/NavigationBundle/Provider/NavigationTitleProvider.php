<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Symfony\Component\HttpFoundation\Request;

class NavigationTitleProvider
{
    /**
     * @var TitleService
     */
    private $titleService;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param TitleService  $titleService
     * @param Request       $request
     */
    public function __construct(
        TitleService $titleService,
        Request $request
    ) {
        $this->titleService = $titleService;
        $this->request = $request;
    }


    /**
     * Load title template from config values
     *
     * @param array  $params
     *
     * @return string
     */
    public function getTitle($params = [])
    {
        $this->titleService->loadByRoute($this->request->get('_route'));
        $this->titleService->setParams($params);

        $title = $this->titleService
            ->render(array(), null, null, null, true);

        return [$title];
    }
}
