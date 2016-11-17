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
     * @param ApplicationsHelperInterface $applicationsHelper
     * @param RouterInterface $router
     */
    public function __construct(ApplicationsHelperInterface $applicationsHelper, RouterInterface $router)
    {
        $this->applicationsHelper = $applicationsHelper;
        $this->router = $router;
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public function getExecutionUrl(array $parameters = [])
    {
        return $this->generateUrl($this->applicationsHelper->getExecutionRoute(), $parameters);
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public function getDialogUrl(array $parameters = [])
    {
        return $this->generateUrl($this->applicationsHelper->getFormDialogRoute(), $parameters);
    }

    /**
     * @param array $parameters
     *
     * @return string
     */
    public function getPageUrl(array $parameters = [])
    {
        return $this->generateUrl($this->applicationsHelper->getFormPageRoute(), $parameters);
    }

    /**
     * @param $routeName
     * @param array $parameters
     *
     * @return string
     */
    private function generateUrl($routeName, array $parameters = [])
    {
        return $this->router->generate($routeName, $parameters);
    }
}
