<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="test_api_nested_objects")
 * @ORM\Entity
 */
class TestEntityForNestedObjects implements TestFrameworkEntityInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var TestNestedName
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", nullable=true)
     */
    protected $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", nullable=true)
     */
    protected $lastName;

    /**
     * @var string
     *
     * @ORM\Column(name="middle_name", type="string", nullable=true)
     */
    protected $middleName;

    /**
     * @var string
     *
     * @ORM\Column(name="name_prefix", type="string", nullable=true)
     */
    protected $namePrefix;

    /**
     * @var string
     *
     * @ORM\Column(name="name_suffix", type="string", nullable=true)
     */
    protected $nameSuffix;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="contacted_at", type="datetime", nullable=true)
     */
    protected $contactedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="related_class", type="string", nullable=true)
     */
    protected $relatedClass;

    /**
     * @var string
     *
     * @ORM\Column(name="related_id", type="integer", nullable=true)
     */
    protected $relatedId;

    /**
     * @var TestEntityForNestedObjects|null
     *
     * @ORM\ManyToOne(targetEntity="TestEntityForNestedObjects", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parent;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="TestEntityForNestedObjects", mappedBy="parent")
     */
    protected $children;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="TestCustomIdentifier", cascade={"all"})
     * @ORM\JoinTable(name="test_api_nested_objects_links",
     *      joinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="link_id", referencedColumnName="id")}
     * )
     */
    protected $links;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->links = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return TestNestedName
     */
    public function getName()
    {
        if (!$this->name) {
            $this->name = new TestNestedName($this->firstName, $this->lastName, $this->contactedAt);
        }

        return $this->name;
    }

    /**
     * @param TestNestedName $name
     *
     * @return self
     */
    public function setName(TestNestedName $name)
    {
        $this->name = $name;
        $this->firstName = $this->name->getFirstName();
        $this->lastName = $this->name->getLastName();
        $this->contactedAt = $this->name->getContactedAt();

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return self
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return self
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * @param string $middleName
     *
     * @return self
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * @return string
     */
    public function getNamePrefix()
    {
        return $this->namePrefix;
    }

    /**
     * @param string $namePrefix
     *
     * @return self
     */
    public function setNamePrefix($namePrefix)
    {
        $this->namePrefix = $namePrefix;

        return $this;
    }

    /**
     * @return string
     */
    public function getNameSuffix()
    {
        return $this->nameSuffix;
    }

    /**
     * @param string $nameSuffix
     *
     * @return self
     */
    public function setNameSuffix($nameSuffix)
    {
        $this->nameSuffix = $nameSuffix;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getContactedAt()
    {
        return $this->contactedAt;
    }

    /**
     * @param \DateTime|null $contactedAt
     *
     * @return self
     */
    public function setContactedAt($contactedAt)
    {
        $this->contactedAt = $contactedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelatedClass()
    {
        return $this->relatedClass;
    }

    /**
     * @param string $relatedClass
     *
     * @return self
     */
    public function setRelatedClass($relatedClass)
    {
        $this->relatedClass = $relatedClass;

        return $this;
    }

    /**
     * @return int
     */
    public function getRelatedId()
    {
        return $this->relatedId;
    }

    /**
     * @param int $relatedId
     *
     * @return self
     */
    public function setRelatedId($relatedId)
    {
        $this->relatedId = $relatedId;

        return $this;
    }

    /**
     * @return TestEntityForNestedObjects|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param TestEntityForNestedObjects $item
     */
    public function setParent($item)
    {
        $this->parent = $item;
    }

    /**
     * @return Collection|TestEntityForNestedObjects[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function addChild(TestEntityForNestedObjects $item)
    {
        if (!$this->children->contains($item)) {
            $this->children->add($item);
        }
    }

    public function removeChild(TestEntityForNestedObjects $item)
    {
        if ($this->children->contains($item)) {
            $this->children->removeElement($item);
        }
    }

    /**
     * @return Collection|TestCustomIdentifier[]
     */
    public function getLinks()
    {
        return $this->links;
    }

    public function addLink(TestCustomIdentifier $link)
    {
        $this->links->add($link);
    }

    public function removeLink(TestCustomIdentifier $link)
    {
        $this->links->removeElement($link);
    }
}
