<?php

namespace Oro\Bundle\UserBundle\Entity;

/**
 * This interface defines the methods required for the password reset functionality
 */
interface PasswordRecoveryInterface
{
    /**
     * @param int $ttl
     * @return bool
     */
    public function isPasswordRequestNonExpired($ttl);

    /**
     * @return string|null
     */
    public function getConfirmationToken();

    /**
     * @param string|null $token
     */
    public function setConfirmationToken($token);

    /**
     * Generate unique confirmation token
     *
     * @return string Token value
     */
    public function generateToken();

    /**
     * @return \DateTime|null
     */
    public function getPasswordRequestedAt();

    /**
     * @param \DateTime|null $time New password request time. Null by default.
     */
    public function setPasswordRequestedAt(\DateTime $time = null);

    /**
     * @return \DateTime|null
     */
    public function getPasswordChangedAt();

    /**
     * @param \DateTime|null $time Password changed time. Null by default.
     */
    public function setPasswordChangedAt(\DateTime $time = null);
}
