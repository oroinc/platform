<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DataAuditBundle\Entity\AuditAdditionalFieldsInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @ORM\Table(name="oro_test_dataaudit_child")
 * @ORM\Entity
 * @Config(defaultValues={"dataaudit"={"auditable"=true}})
 */
class TestAuditDataChild implements TestFrameworkEntityInterface, AuditAdditionalFieldsInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="string_property", type="text", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $stringProperty;

    /**
     * @var string
     *
     * @ORM\Column(name="not_auditable_property", type="text", nullable=true)
     */
    private $notAuditableProperty;

    /**
     * @var TestAuditDataOwner
     *
     * @ORM\OneToOne(targetEntity="TestAuditDataOwner", mappedBy="child")
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $owner;

    /**
     * @var TestAuditDataOwner[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="TestAuditDataOwner", mappedBy="childrenManyToMany")
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $owners;

    /**
     * @ORM\ManyToOne(targetEntity="TestAuditDataOwner", inversedBy="childrenOneToMany")
     * @ORM\JoinColumn(name="owner_one_to_many_id", referencedColumnName="id")
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $ownerManyToOne;

    /**
     * @var array
     */
    private $additionalFields;

    public function __construct()
    {
        $this->owners = new ArrayCollection();
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
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getStringProperty()
    {
        return $this->stringProperty;
    }

    /**
     * @param string $stringProperty
     */
    public function setStringProperty($stringProperty)
    {
        $this->stringProperty = $stringProperty;
    }

    public function __toString()
    {
        return 'ToStringTestAuditDataChild';
    }

    /**
     * @return string
     */
    public function getNotAuditableProperty()
    {
        return $this->notAuditableProperty;
    }

    /**
     * @param string $notAuditableProperty
     */
    public function setNotAuditableProperty($notAuditableProperty)
    {
        $this->notAuditableProperty = $notAuditableProperty;
    }

    /**
     * @return TestAuditDataOwner[]|Collection
     */
    public function getOwners()
    {
        return $this->owners;
    }

    /**
     * @param Collection $owners
     */
    public function setOwners(Collection $owners = null)
    {
        $this->owners = $owners;
    }

    /**
     * @return TestAuditDataOwner
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param TestAuditDataOwner $owner
     */
    public function setOwner(TestAuditDataOwner $owner = null)
    {
        $this->owner = $owner;
    }

    /**
     * @return mixed
     */
    public function getOwnerManyToOne()
    {
        return $this->ownerManyToOne;
    }

    /**
     * @param mixed $ownerManyToOne
     */
    public function setOwnerManyToOne(TestAuditDataOwner $ownerManyToOne = null)
    {
        $this->ownerManyToOne = $ownerManyToOne;
    }

    /**
     * @param array $fields
     */
    public function setAdditionalFields(array $fields)
    {
        $this->additionalFields = $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalFields()
    {
        return $this->additionalFields;
    }
}
