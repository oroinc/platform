<?php

namespace Oro\Bundle\FormBundle\Captcha;

use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod\CurlPost;

/**
 * Factory to create recaptcha client instance.
 */
class ReCaptchaClientFactory
{
    public function create(string $privateKey): ReCaptcha
    {
        return new ReCaptcha($privateKey, new CurlPost());
    }
}
