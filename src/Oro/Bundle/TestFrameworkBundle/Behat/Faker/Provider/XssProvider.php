<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Faker\Provider;

use Faker\Provider\Base as BaseProvider;

class XssProvider extends BaseProvider
{
    /**
     * @param string $identifier
     * @return string
     */
    public function xss($identifier = 'XSS')
    {
        return sprintf('<script>alert(\'%s\');</script>', $identifier);
    }
}
