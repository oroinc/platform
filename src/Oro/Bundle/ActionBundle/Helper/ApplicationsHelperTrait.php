<?php

namespace Oro\Bundle\ActionBundle\Helper;

trait ApplicationsHelperTrait
{
    /** @var string|bool|null */
    protected $currentApplication = false;

    /**
     * @param array $applications
     *
     * @return bool
     */
    public function isApplicationsValid(array $applications)
    {
        if (empty($applications)) {
            return true;
        }

        if ($this->currentApplication === false) {
            $this->currentApplication = $this->getCurrentApplication();
        }

        return in_array($this->currentApplication, $applications, true);
    }
}
