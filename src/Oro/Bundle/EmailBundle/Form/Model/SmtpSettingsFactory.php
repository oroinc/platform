<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\DependencyInjection\Configuration as Config;

class SmtpSettingsFactory
{
    /**
     * @param Request $request
     * @param string $section
     *
     * @return SmtpSettings
     */
    public static function createFromRequest(Request $request, $section = 'email_configuration')
    {
        $host = self::getRequestParamKey('smtp_settings_host', $section);
        $port = self::getRequestParamKey('smtp_settings_port', $section);
        $encryption = self::getRequestParamKey('smtp_settings_encryption', $section);
        $username = self::getRequestParamKey('smtp_settings_username', $section);
        $password = self::getRequestParamKey('smtp_settings_password', $section);

        return new SmtpSettings(
            $request->get($host, SmtpSettings::DEFAULT_HOST),
            $request->get($port, SmtpSettings::DEFAULT_PORT),
            $request->get($encryption, SmtpSettings::DEFAULT_ENCRYPTION),
            $request->get($username, SmtpSettings::DEFAULT_USERNAME),
            $request->get($password, SmtpSettings::DEFAULT_PASSWORD)
        );
    }

    /**
     * @param string $key
     * @param string $section
     *
     * @return string
     */
    public static function getRequestParamKey($key, $section)
    {
        return sprintf(
            '%s[%s][value]',
            $section,
            Config::getConfigKeyByName($key, ConfigManager::SECTION_VIEW_SEPARATOR)
        );
    }
}
