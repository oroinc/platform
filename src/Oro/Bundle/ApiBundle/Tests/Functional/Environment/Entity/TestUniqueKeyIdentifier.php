<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(
 *     name="test_api_unique_key_id",
 *      indexes={
 *          @ORM\Index(name="test_api_unique_key5_idx", columns={"key5"})
 *     },
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="test_api_unique_key_idx", columns={"key1", "key2"})
 *     }
 * )
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="organization",
 *              "owner_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class TestUniqueKeyIdentifier implements TestFrameworkEntityInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     *
     * @ORM\Column(name="key1", type="string", nullable=false)
     */
    public $key1;

    /**
     * @var int
     *
     * @ORM\Column(name="key2", type="integer", nullable=false)
     */
    public $key2;

    /**
     * @var string
     *
     * @ORM\Column(name="key3", type="string", nullable=false, unique=true)
     */
    public $key3;

    /**
     * @var int
     *
     * @ORM\Column(name="key4", type="integer", nullable=false, unique=true)
     */
    public $key4;

    /**
     * @var string
     *
     * @ORM\Column(name="key5", type="string", nullable=true)
     */
    public $key5;

    /**
     * @var string
     *
     * @ORM\Column(name="key6", type="string", nullable=true)
     */
    public $key6;

    /**
     * @var string
     *
     * @ORM\Column(name="key7", type="string", nullable=true)
     */
    public $key7;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    public $name;

    /**
     * @var TestUniqueKeyIdentifier|null
     *
     * @ORM\ManyToOne(targetEntity="TestUniqueKeyIdentifier")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="TestUniqueKeyIdentifier")
     * @ORM\JoinTable(name="test_api_unique_key_id_children",
     *      joinColumns={@ORM\JoinColumn(name="parent_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="id")}
     * )
     */
    protected $children;

    /**
     * @var Organization|null
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @return TestUniqueKeyIdentifier|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param TestUniqueKeyIdentifier $item
     */
    public function setParent($item)
    {
        $this->parent = $item;
    }

    /**
     * @return Collection<int, TestUniqueKeyIdentifier>
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(TestUniqueKeyIdentifier $item)
    {
        $this->children->add($item);
    }

    public function removeChild(TestUniqueKeyIdentifier $item)
    {
        $this->children->removeElement($item);
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization)
    {
        $this->organization = $organization;
    }
}
