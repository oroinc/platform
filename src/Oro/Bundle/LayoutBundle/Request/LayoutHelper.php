<?php

namespace Oro\Bundle\LayoutBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LayoutBundle\Annotation\Layout as LayoutAnnotation;

class LayoutHelper
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param RequestStack $requestStack
     * @param ConfigManager $configManager
     */
    public function __construct(RequestStack $requestStack, ConfigManager $configManager)
    {
        $this->requestStack = $requestStack;
        $this->configManager = $configManager;
    }

    /**
     * @param Request|null $request
     * @return LayoutAnnotation
     */
    public function getLayoutAnnotation(Request $request = null)
    {
        if ($request === null) {
            $request = $this->requestStack->getCurrentRequest();
        }

        return $request->attributes->get('_layout');
    }

    /**
     * @param Request|null $request
     * @return bool
     */
    public function isLayoutRequest(Request $request = null)
    {
        return $this->getLayoutAnnotation($request) !== null;
    }

    /**
     * @param Request|null $request
     * @return bool
     */
    public function isTemplateRequest(Request $request = null)
    {
        return !$this->isLayoutRequest($request);
    }

    /**
     * @return bool
     */
    public function isProfilerEnabled()
    {
        return $this->configManager->get('oro_layout.debug_block_info');
    }
}
