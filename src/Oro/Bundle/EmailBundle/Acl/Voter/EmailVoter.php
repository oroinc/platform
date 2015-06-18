<?php

namespace Oro\Bundle\EmailBundle\Acl\Voter;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\EmailBundle\Entity\EmailUser;

class EmailVoter implements VoterInterface
{
    const SUPPORTED_CLASS = 'Oro\Bundle\EmailBundle\Entity\Email';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, ['VIEW', 'EDIT']);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return $class === self::SUPPORTED_CLASS;
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$object || !is_object($object)) {
            return self::ACCESS_ABSTAIN;
        }

        if (!$this->supportsClass(ClassUtils::getClass($object))) {
            return self::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                return self::ACCESS_ABSTAIN;
            }
        }

        /** @var EmailUser[] $emailUsers */
        $emailUsers = $object->getEmailUsers();
        foreach ($attributes as $attribute) {
            foreach ($emailUsers as $emailUser) {
                if ($this->container->get('oro_security.security_facade')->isGranted($attribute, $emailUser)) {
                    return self::ACCESS_GRANTED;
                }
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
