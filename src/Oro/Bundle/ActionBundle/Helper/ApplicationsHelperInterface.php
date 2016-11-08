<?php

namespace Oro\Bundle\ActionBundle\Helper;

interface ApplicationsHelperInterface extends RouteHelperInterface
{
    /**
     * @param array $applications
     * @return bool
     */
    public function isApplicationsValid(array $applications);

    /**
     * @return string|null
     */
    public function getCurrentApplication();
}
