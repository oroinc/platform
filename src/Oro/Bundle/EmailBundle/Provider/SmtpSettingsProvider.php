<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;

class SmtpSettingsProvider extends AbstractSmtpSettingsProvider
{
    /**
     * @param string|null $scope
     *
     * @return SmtpSettings
     */
    public function getSmtpSettings($scope = null)
    {
        return $this->getConfigurationSmtpSettings($this->globalConfigManager);
    }
}
