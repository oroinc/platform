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
 * @ORM\Table(name="oro_test_dataaudit_owner")
 * @ORM\Entity
 * @Config(defaultValues={"dataaudit"={"auditable"=true}})
 */
class TestAuditDataOwner implements TestFrameworkEntityInterface, AuditAdditionalFieldsInterface
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
     * @var string
     *
     * @ORM\Column(name="int_property", type="integer", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $intProperty;

    /**
     * @var string
     *
     * @ORM\Column(name="serialized_property", type="object", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $serializedProperty;

    /**
     * @var string
     *
     * @ORM\Column(name="json_property", type="json_array", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $jsonProperty;

    /**
     * @var string
     *
     * @ORM\Column(name="date_property", type="datetime", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $dateProperty;

    /**
     * @ORM\OneToOne(targetEntity="TestAuditDataChild", inversedBy="owner")
     * @ORM\JoinColumn(name="child_id", referencedColumnName="id")
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $child;

    /**
     * @ORM\ManyToMany(targetEntity="TestAuditDataChild", inversedBy="owners")
     * @ORM\JoinTable(name="oro_test_dataaudit_many2many",
     *      joinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="id", unique=true)}
     * )
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $childrenManyToMany;

    /**
     * @ORM\OneToMany(targetEntity="TestAuditDataChild", mappedBy="ownerManyToOne")
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $childrenOneToMany;

    /**
     * @var array
     */
    private $additionalFields;

    public function __construct()
    {
        $this->childrenManyToMany = new ArrayCollection;
        $this->childrenOneToMany = new ArrayCollection;
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

    /**
     * @return string
     */
    public function getIntProperty()
    {
        return $this->intProperty;
    }

    /**
     * @param string $intProperty
     */
    public function setIntProperty($intProperty)
    {
        $this->intProperty = $intProperty;
    }

    /**
     * @return string
     */
    public function getSerializedProperty()
    {
        return $this->serializedProperty;
    }

    /**
     * @param string $serializedProperty
     */
    public function setSerializedProperty($serializedProperty)
    {
        $this->serializedProperty = $serializedProperty;
    }

    /**
     * @return string
     */
    public function getJsonProperty()
    {
        return $this->jsonProperty;
    }

    /**
     * @param string $jsonProperty
     */
    public function setJsonProperty($jsonProperty)
    {
        $this->jsonProperty = $jsonProperty;
    }

    /**
     * @return string
     */
    public function getDateProperty()
    {
        return $this->dateProperty;
    }

    /**
     * @param string $dateProperty
     */
    public function setDateProperty($dateProperty)
    {
        $this->dateProperty = $dateProperty;
    }

    /**
     * @return mixed
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @param mixed $child
     */
    public function setChild(TestAuditDataChild $child = null)
    {
        $this->child = $child;
    }

    /**
     * @return TestAuditDataChild[]|Collection
     */
    public function getChildrenManyToMany()
    {
        return $this->childrenManyToMany;
    }

    /**
     * @param mixed $collection
     */
    public function setChildrenManyToMany($collection)
    {
        $this->childrenManyToMany = $collection;
    }

    public function __toString()
    {
        return 'ToStringTestAuditDataOwner';
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
     * @return TestAuditDataChild[]|Collection
     */
    public function getChildrenOneToMany()
    {
        return $this->childrenOneToMany;
    }

    /**
     * @param mixed $collection
     */
    public function setChildrenOneToMany($collection)
    {
        $this->childrenOneToMany = $collection;
    }

    /**
     * @param TestAuditDataChild $testAuditDataChild
     */
    public function addChildrenOneToMany(TestAuditDataChild $testAuditDataChild)
    {
        $this->childrenOneToMany->add($testAuditDataChild);
        $testAuditDataChild->setOwnerManyToOne($this);
    }
    
    public function removeChildrenOneToMany(TestAuditDataChild $testAuditDataChild)
    {
        $this->childrenOneToMany->removeElement($testAuditDataChild);
        $testAuditDataChild->setOwnerManyToOne(null);
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
