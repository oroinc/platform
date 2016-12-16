<?php

namespace Oro\Bundle\EmailBundle\Form\Model;

use Symfony\Component\HttpFoundation\Request;

class SmtpSettingsFactory
{
    const REQUEST_HOST = 'host';
    const REQUEST_PORT = 'port';
    const REQUEST_ENCRYPTION = 'encryption';
    const REQUEST_USERNAME = 'username';
    const REQUEST_PASSWORD = 'password';

    /**
     * @param Request $request
     *
     * @return SmtpSettings
     */
    public static function createFromRequest(Request $request)
    {
        return new SmtpSettings(
            $request->get(self::REQUEST_HOST, SmtpSettings::DEFAULT_HOST),
            $request->get(self::REQUEST_PORT, SmtpSettings::DEFAULT_PORT),
            $request->get(self::REQUEST_ENCRYPTION, SmtpSettings::DEFAULT_ENCRYPTION),
            $request->get(self::REQUEST_USERNAME, SmtpSettings::DEFAULT_USERNAME),
            $request->get(self::REQUEST_PASSWORD, SmtpSettings::DEFAULT_PASSWORD)
        );
    }
}
