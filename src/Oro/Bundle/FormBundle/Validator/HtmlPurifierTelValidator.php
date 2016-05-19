<?php

namespace Oro\Bundle\FormBundle\Validator;

class HtmlPurifierTelValidator extends \HTMLPurifier_URIScheme
{
    public $browsable = false;
    public $may_omit_host = true;

    /**
     * @inheritdoc
     */
    public function doValidate(&$uri, $config, $context)
    {
        $uri->userinfo = null;
        $uri->host     = null;
        $uri->port     = null;

        $pattern = '/^\+?[\(\)\.0-9_\-]+$/';
        $proposedPhoneNumber = $uri->path; // defined phone
        if (preg_match($pattern, $proposedPhoneNumber) !== 1) {
            return false;
        } else {
            return true;
        }
    }
}
