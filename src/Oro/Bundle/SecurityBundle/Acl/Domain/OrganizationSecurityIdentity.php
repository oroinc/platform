<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

final class OrganizationSecurityIdentity implements SecurityIdentityInterface
{
    /** @var string */
    private $id;

    /** @var string */
    private $class;

    /**
     * Constructor.
     *
     * @param $id
     * @param $class
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
     * Creates an organization security identity from an OrganizationInterface.
     *
     * @param OrganizationInterface $organization
     * @return self
     */
    public static function fromOrganization(OrganizationInterface $organization)
    {
        return new self($organization->getId(), ClassUtils::getRealClass($organization));
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
     * Returns the organization's class name.
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
        return sprintf('OrganizationSecurityIdentity(%s, %s)', $this->id, $this->class);
    }
}
