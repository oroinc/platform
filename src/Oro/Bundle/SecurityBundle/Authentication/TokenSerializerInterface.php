<?php

namespace Oro\Bundle\SecurityBundle\Authentication;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * The security token serializer can be used to get a string representation
 * of the security token and then restore the token from this string.
 * The restored token may not be an instance of the same class as the original token,
 * but it must represent the same security context.
 * Any security token is already implements Serializable interface,
 * but sometimes it is not possible to use default serialization because it
 * serializes full information about the nested objects, including reflection info.
 * For example, if it is required to pass security context between different processes.
 */
interface TokenSerializerInterface
{
    /**
     * Converts the given security token to a string.
     *
     * @param TokenInterface $token
     *
     * @return string|null
     */
    public function serialize(TokenInterface $token);

    /**
     * Converts the given string to a security token that represents
     * the same security context being used to build this string.
     *
     * @param string $value
     *
     * @return TokenInterface|null
     */
    public function deserialize($value);
}
