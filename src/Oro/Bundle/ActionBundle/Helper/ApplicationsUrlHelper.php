<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\Routing\RouterInterface;

class ApplicationsUrlHelper
{
    /** @var ApplicationsHelper */
    private $applicationsHelper;

    /** @var RouterInterface */
    private $router;

    /**
     * @param ApplicationsHelper $applicationsHelper
     * @param RouterInterface $router
     */
    public function __construct(ApplicationsHelper $applicationsHelper, RouterInterface $router)
    {
        $this->applicationsHelper = $applicationsHelper;
        $this->router = $router;
    }

    /**
     * @param array $parameters
     * @return string
     */
    public function getExecutionUrl(array $parameters = [])
    {
        return $this->router->generate($this->applicationsHelper->getExecutionRoute(), $parameters);
    }

    /**
     * @param array $parameters
     * @return string
     */
    public function getDialogUrl(array $parameters = [])
    {
        return $this->router->generate($this->applicationsHelper->getDialogRoute(), $parameters);
    }
}
