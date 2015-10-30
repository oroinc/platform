<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnitInterface;

class BusinessUnitSecurityIdentity implements SecurityIdentityInterface
{
    /** @var string */
    protected $id;

    /** @var string */
    protected $class;

    /**
     * Constructor.
     *
     * @param int|string $id
     * @param string     $class
     */
    public function __construct($id, $class)
    {
        if (empty($id)) {
            throw new \InvalidArgumentException('$id must not be empty.');
        }
        if (empty($class)) {
            throw new \InvalidArgumentException('$class must not be empty.');
        }

        $this->id = (string) $id;
        $this->class = $class;
    }

    /**
     * Creates a business unit security identity from a BusinessUnitInterface.
     *
     * @param BusinessUnitInterface $businessUnit
     * @return self
     */
    public static function fromBusinessUnit(BusinessUnitInterface $businessUnit)
    {
        return new self($businessUnit->getId(), ClassUtils::getRealClass($businessUnit));
    }

    /**
     * Returns the ID.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the business unit's class name.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(SecurityIdentityInterface $sid)
    {
        if (!$sid instanceof self) {
            return false;
        }

        return $this->id === $sid->getId() && $this->class === $sid->getClass();
    }

    /**
     * A textual representation of this security identity.
     *
     * This is not used for equality comparison, but only for debugging.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf('BusinessUnitSecurityIdentity(%s, %s)', $this->id, $this->class);
    }
}
