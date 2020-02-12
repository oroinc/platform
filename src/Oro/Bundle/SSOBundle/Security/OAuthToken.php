<?php

namespace Oro\Bundle\SSOBundle\Security;

use HWI\Bundle\OAuthBundle\Security\Core\Authentication\Token\OAuthToken as HWIOAuthToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\AuthenticatedTokenTrait;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenTrait;

/**
 * The OAuth authentication token.
 */
class OAuthToken extends HWIOAuthToken implements OrganizationAwareTokenInterface
{
    use AuthenticatedTokenTrait;
    use OrganizationAwareTokenTrait {
        __serialize as protected traitSerialize;
        __unserialize as protected traitUnserialize;
    }

    /**
     * This method is required for compatibility OAuthToken with PHP <7.4
     * @see https://github.com/hwi/HWIOAuthBundle/issues/1567
     *
     * {@inheritdoc}
     */
    public function __serialize(): array
    {
        static $inSerialize = false;

        if ($inSerialize) {
            return [];
        }

        $inSerialize = true;

        $data = [
            $this->traitSerialize(),
            parent::serialize()
        ];

        $inSerialize = false;

        return $data;
    }

    /**
     * This method is required for compatibility OAuthToken with PHP <7.4
     * @see https://github.com/hwi/HWIOAuthBundle/issues/1567
     *
     * {@inheritdoc}
     */
    public function __unserialize(array $serialized): void
    {
        static $isInUnserialize = false;

        if ($isInUnserialize) {
            return;
        }

        $isInUnserialize = true;

        $this->traitUnserialize($serialized[0]);
        parent::unserialize($serialized[1]);

        $isInUnserialize = false;
    }
}
