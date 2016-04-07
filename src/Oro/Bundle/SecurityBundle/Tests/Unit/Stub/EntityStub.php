<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Stub;

use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;

/**
 * Class Entry
 *
 * @package Oro\Bundle\SecurityBundle\Tests\Unit\Stub
 */
class EntityStub implements \Serializable
{
    private $identity;

    /**
     * Entry constructor.
     *
     * @param RoleSecurityIdentity $identity
     */
    public function __construct(RoleSecurityIdentity $identity)
    {
        $this->identity = $identity;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array($this->identity));
    }

    public static function getExceptionMesage()
    {
        return 'Can\'t serialize !';
    }

    /**
     * @param string $serialized
     *
     * @throws \Exception
     */
    public function unserialize($serialized)
    {
        try {
            list($this->identity) = unserialize($serialized);
        } catch (\Throwable $e) {
            throw new \Exception(self::getExceptionMesage());
        }
    }
}
