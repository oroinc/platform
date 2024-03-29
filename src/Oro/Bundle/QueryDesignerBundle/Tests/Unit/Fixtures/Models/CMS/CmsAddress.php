<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Fixtures\Models\CMS;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cms_addresses')]
class CmsAddress
{
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public $id;

    #[ORM\Column(length: 50)]
    public $country;

    #[ORM\Column(length: 50)]
    public $zip;

    #[ORM\Column(length: 50)]
    public $city;

    /**
     * Testfield for Schema Updating Tests.
     */
    public $street;

    #[ORM\OneToOne(inversedBy: 'address', targetEntity: CmsUser::class)]
    #[ORM\JoinColumn(referencedColumnName: 'id')]
    public $user;

    #[ORM\ManyToOne(targetEntity: CmsUser::class, cascade: ['persist'], inversedBy: 'shippingAddresses')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $ownerUser;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getZipCode()
    {
        return $this->zip;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function setUser(CmsUser $user)
    {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }
}
