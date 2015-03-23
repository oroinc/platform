<?php

namespace Oro\Bundle\SSOBundle\OAuth\ResourceOwner;

use HWI\Bundle\OAuthBundle\OAuth\ResourceOwner\GoogleResourceOwner as BaseGoogleResourceOwner;

class GoogleResourceOwner extends BaseGoogleResourceOwner
{
    use ConfigurableCredentialsTrait;
}
