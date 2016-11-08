<?php

namespace Oro\Bundle\ActionBundle\Helper;

trait ApplicationsHelperTrait
{
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