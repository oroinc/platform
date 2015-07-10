<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\UserBundle\Entity\User;

class UserChannelTokenProvider
{
    const PART_STR_LENGTH = 10;

    /**
     * Get unique user channel token
     *
     * @param User $user
     *
     * @return string
     */
    public function getToken(User $user)
    {
        /** @var User $user */
        $channelToken = $this->generateToken($user);

        return $channelToken;
    }

    /**
     * Generate Unique private channel id by user
     *
     * @param User $user
     *
     * @return string
     */
    protected function generateToken(User $user)
    {
        $passStr = $user->getPassword();
        $token = md5(substr($passStr, self::PART_STR_LENGTH));

        return $token;
    }

}
