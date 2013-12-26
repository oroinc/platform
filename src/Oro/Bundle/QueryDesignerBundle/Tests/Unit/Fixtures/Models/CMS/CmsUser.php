<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS;

/**
 * @Entity
 * @Table(name="cms_users")
 */
class CmsUser
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="string", length=50, nullable=true)
     */
    public $status;

    /**
     * @Column(type="string", length=255, unique=true)
     */
    public $username;

    /**
     * @Column(type="string", length=255)
     */
    public $name;

    /**
     * @OneToOne(targetEntity="CmsAddress", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    public $address;

    public function getId()
    {
        return $this->id;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(CmsAddress $address)
    {
        if ($this->address !== $address) {
            $this->address = $address;
            $address->setUser($this);
        }
    }
}
