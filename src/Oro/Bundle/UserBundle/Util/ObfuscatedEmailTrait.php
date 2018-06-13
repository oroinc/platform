<?php

namespace Oro\Bundle\UserBundle\Util;

/**
 * Get the truncated email
 */
trait ObfuscatedEmailTrait
{
    /**
     *
     * The default implementation only keeps the part following @ in the address.
     *
     * @param string $email
     *
     * @return string|null
     */
    public function getObfuscatedEmail($email)
    {
        if (!is_string($email)) {
            return null;
        }
        if (false !== $pos = strpos($email, '@')) {
            $email = '...' . substr($email, $pos);
        }

        return $email;
    }
}
