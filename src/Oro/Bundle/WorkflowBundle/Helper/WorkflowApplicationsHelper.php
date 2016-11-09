<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface;
use Oro\Bundle\ActionBundle\Helper\RouteHelperTrait;

class WorkflowApplicationsHelper implements ApplicationsHelperInterface
{
    use RouteHelperTrait;

    /** @var ApplicationsHelperInterface */
    protected $applicationsHelper;

    /**
     * @param ApplicationsHelperInterface $applicationsHelper
     */
    public function __construct(ApplicationsHelperInterface $applicationsHelper)
    {
        $this->applicationsHelper = $applicationsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicationsValid(array $applications)
    {
        return $this->applicationsHelper->isApplicationsValid($applications);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentApplication()
    {
        return $this->applicationsHelper->getCurrentApplication();
    }
}
