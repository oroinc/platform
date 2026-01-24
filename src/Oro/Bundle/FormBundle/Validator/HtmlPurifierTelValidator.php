<?php

namespace Oro\Bundle\FormBundle\Validator;

/**
 * HTMLPurifier URI scheme validator for telephone numbers.
 *
 * This validator extends HTMLPurifier's URI scheme validation to support the
 * tel: URI scheme, validating telephone numbers according to a pattern that
 * allows digits, parentheses, dots, underscores, hyphens, and optional leading
 * plus sign.
 */
class HtmlPurifierTelValidator extends \HTMLPurifier_URIScheme
{
    public $browsable = false;

    public $may_omit_host = true;

    #[\Override]
    public function doValidate(&$uri, $config, $context)
    {
        $uri->userinfo = null;
        $uri->host     = null;
        $uri->port     = null;

        $pattern = '/^\+?[\(\)\.0-9_\-]+$/';
        $proposedPhoneNumber = $uri->path; // defined phone

        if (preg_match($pattern, $proposedPhoneNumber) !== 1) {
            return false;
        }

        return true;
    }
}
