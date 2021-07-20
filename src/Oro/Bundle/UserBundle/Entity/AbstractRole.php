<?php

namespace Oro\Bundle\UserBundle\Entity;

use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Core\Role\Role as BaseRole;

/**
 * Abstract class for the any Role.
 */
abstract class AbstractRole extends BaseRole
{
    /**
     * @var string
     */
    protected $role;

    /**
     * @return string
     */
    abstract public function getPrefix();

    /**
     * Set role name only for newly created role
     *
     * @param  string $role Role name
     * @param bool $generateUnique
     * @return $this
     */
    public function setRole($role, $generateUnique = true)
    {
        $this->role = $generateUnique
            ? $this->generateUniqueRole($role)
            : $this->addPrefix($this->normalize($role));

        return $this;
    }

    /**
     * @return string
     */
    abstract public function getLabel();

    public function __toString(): string
    {
        return (string)$this->getRole();
    }

    /**
     * @param string $role
     * @return string
     */
    public function generateUniqueRole($role = '')
    {
        $role = $this->addUniqueSuffix($role);
        $role = $this->normalize($role);
        $role = $this->addPrefix($role);

        return $role;
    }

    /**
     * @param string $role
     * @return string
     */
    protected function normalize($role)
    {
        return strtoupper(preg_replace('/[^\w\-]/i', '_', $role));
    }

    /**
     * @param string $role
     * @return string
     */
    protected function addUniqueSuffix($role)
    {
        return uniqid(rtrim($role, '_') . '_');
    }

    /**
     * @param string $role
     * @return string
     */
    protected function addPrefix($role)
    {
        if ($role !== AuthenticatedVoter::IS_AUTHENTICATED_ANONYMOUSLY && strpos($role, $this->getPrefix()) !== 0) {
            $role = $this->getPrefix() . $role;
        }

        return $role;
    }
}
