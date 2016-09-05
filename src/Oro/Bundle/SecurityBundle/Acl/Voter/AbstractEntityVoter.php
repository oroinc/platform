<?php

namespace Oro\Bundle\SecurityBundle\Acl\Voter;

use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

abstract class AbstractEntityVoter implements VoterInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var array
     */
    protected $supportedAttributes = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, $this->supportedAttributes);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        if (!$this->className) {
            throw new \InvalidArgumentException('className was not provided');
        }

        return $class === $this->className;
    }

    /**
     * Check whether at least one of the the attributes is supported
     *
     * @param array $attributes
     * @return bool
     */
    protected function supportsAttributes(array $attributes)
    {
        $supportsAttributes = false;
        foreach ($attributes as $attribute) {
            if ($this->supportsAttribute($attribute)) {
                $supportsAttributes = true;
                break;
            }
        }

        return $supportsAttributes;
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$object || !is_object($object)) {
            return self::ACCESS_ABSTAIN;
        }

        // both entity and identity objects are supported
        $class = $this->getEntityClass($object);

        try {
            $identifier = $this->getEntityIdentifier($object);
        } catch (NotManageableEntityException $e) {
            return self::ACCESS_ABSTAIN;
        }

        if (null === $identifier) {
            return self::ACCESS_ABSTAIN;
        }

        return $this->getPermission($class, $identifier, $attributes);
    }

    /**
     * @param string $class
     * @param int $identifier
     * @param array $attributes
     * @return int
     */
    protected function getPermission($class, $identifier, array $attributes)
    {
        // cheap performance check (no DB interaction)
        if (!$this->supportsAttributes($attributes)) {
            return self::ACCESS_ABSTAIN;
        }

        // expensive performance check (includes DB interaction)
        if (!$this->supportsClass($class)) {
            return self::ACCESS_ABSTAIN;
        }

        $result = self::ACCESS_ABSTAIN;
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $permission = $this->getPermissionForAttribute($class, $identifier, $attribute);

            // if not abstain or changing from granted to denied
            if ($result === self::ACCESS_ABSTAIN && $permission !== self::ACCESS_ABSTAIN
                || $result === self::ACCESS_GRANTED && $permission === self::ACCESS_DENIED
            ) {
                $result = $permission;
            }

            // if one of attributes is denied then access should be denied for all attributes
            if ($result === self::ACCESS_DENIED) {
                break;
            }
        }

        return $result;
    }

    /**
     * @param string $class
     * @param int $identifier
     * @param string $attribute
     * @return int
     */
    abstract protected function getPermissionForAttribute($class, $identifier, $attribute);

    /**
     * @param object $object
     * @return string
     */
    protected function getEntityClass($object)
    {
        if ($object instanceof ObjectIdentityInterface) {
            $class = $object->getType();

            $delim = strpos($class, '@');
            if ($delim) {
                $class = ltrim(substr($class, $delim + 1), ' ');
            }
        } elseif ($object instanceof FieldVote) {
            return $this->getEntityClass($object->getDomainObject());
        } else {
            $class = $this->doctrineHelper->getEntityClass($object);
        }

        return ClassUtils::getRealClass($class);
    }

    /**
     * @param object $object
     * @return int|null
     */
    protected function getEntityIdentifier($object)
    {
        if ($object instanceof ObjectIdentityInterface) {
            $identifier = $object->getIdentifier();
            if (!filter_var($identifier, FILTER_VALIDATE_INT)) {
                $identifier = null;
            } else {
                $identifier = (int)$identifier;
            }
        } elseif ($object instanceof FieldVote) {
            return $this->getEntityIdentifier($object->getDomainObject());
        } else {
            $identifier = $this->doctrineHelper->getSingleEntityIdentifier($object, false);
        }

        return $identifier;
    }
}
