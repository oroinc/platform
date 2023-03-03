<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DataAuditBundle\Entity\AuditAdditionalFieldsInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;
use Oro\Component\Config\Common\ConfigObject;

/**
 * @ORM\Table(name="oro_test_dataaudit_owner")
 * @ORM\Entity
 * @Config(defaultValues={"dataaudit"={"auditable"=true}})
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class TestAuditDataOwner implements
    TestFrameworkEntityInterface,
    AuditAdditionalFieldsInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

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
     * @ORM\Column(name="not_auditable_property", type="text", nullable=true)
     */
    private $notAuditableProperty;

    /**
     * @ORM\OneToOne(targetEntity="TestAuditDataChild", inversedBy="owner")
     * @ORM\JoinColumn(name="child_id", referencedColumnName="id")
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true, "propagate"=true}})
     */
    private $child;

    /**
     * @var TestAuditDataChild
     *
     * @ORM\OneToOne(targetEntity="TestAuditDataChild", inversedBy="ownerCascade", cascade={"remove"})
     * @ORM\JoinColumn(name="child_cascade_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true, "propagate"=true}})
     */
    private $childCascade;

    /**
     * @var TestAuditDataChild
     *
     * @ORM\OneToOne(targetEntity="TestAuditDataChild", inversedBy="ownerOrphanRemoval", orphanRemoval=true)
     * @ORM\JoinColumn(name="child_orphan_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true, "propagate"=true}})
     */
    private $childOrphanRemoval;

    /**
     * @var TestAuditDataChild
     *
     * @ORM\OneToOne(targetEntity="TestAuditDataChild")
     * @ORM\JoinColumn(name="child_unidirectional_id", referencedColumnName="id")
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true, "propagate"=true}})
     */
    private $childUnidirectional;

    /**
     * @ORM\ManyToMany(targetEntity="TestAuditDataChild", inversedBy="owners")
     * @ORM\JoinTable(name="oro_test_dataaudit_many2many",
     *      joinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="id", unique=true)}
     * )
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true, "propagate"=true}})
     */
    private $childrenManyToMany;

    /**
     * @var TestAuditDataChild[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="TestAuditDataChild")
     * @ORM\JoinTable(name="oro_test_dataaudit_many2many_u",
     *      joinColumns={@ORM\JoinColumn(name="owner_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="child_id", referencedColumnName="id", unique=true)}
     * )
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true, "propagate"=true}})
     */
    private $childrenManyToManyUnidirectional;

    /**
     * @ORM\OneToMany(targetEntity="TestAuditDataChild", mappedBy="ownerManyToOne")
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true, "propagate"=true}})
     */
    private $childrenOneToMany;

    /**
     * @var array
     *
     * @ORM\Column(name="array_property", type="array", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $arrayProperty;

    /**
     * @var int
     *
     * @ORM\Column(name="bigint_property", type="bigint", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $bigintProperty;

    /**
     * @var resource
     *
     * @ORM\Column(name="binary_property", type="binary", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $binaryProperty;

    /**
     * @var resource
     *
     * @ORM\Column(name="blob_property", type="blob", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $blobProperty;

    /**
     * @var bool
     *
     * @ORM\Column(name="boolean_property", type="boolean", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $booleanProperty;

    /**
     * @var ConfigObject
     *
     * @ORM\Column(name="config_object_property", type="config_object", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $configObjectProperty;

    /**
     * @var string
     *
     * @ORM\Column(name="crypted_string_property", type="crypted_string", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $cryptedStringProperty;

    /**
     * @var string
     *
     * @ORM\Column(name="currency_property", type="currency", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $currencyProperty;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_property", type="date", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $dateProperty;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_time_property", type="datetime", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $dateTimeProperty;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_time_tz_property", type="datetimetz", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $dateTimeTzProperty;

    /**
     * @var float
     *
     * @ORM\Column(name="decimal_property", type="decimal", nullable=true, precision=19, scale=4)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $decimalProperty;

    /**
     * @var int
     *
     * @ORM\Column(name="duration_property", type="duration", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $durationProperty;

    /**
     * @var float
     *
     * @ORM\Column(name="float_property", type="float", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $floatProperty;

    /**
     * @var string
     *
     * @ORM\Column(name="guid_property", type="guid", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $guidProperty;

    /**
     * @var int
     *
     * @ORM\Column(name="integer_property", type="integer", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $integerProperty;

    /**
     * @var array
     *
     * @ORM\Column(name="json_array_property", type="json_array", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $jsonArrayProperty;

    /**
     * @var float
     *
     * @ORM\Column(name="money_property", type="money", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $moneyProperty;

    /**
     * @var string
     *
     * @ORM\Column(name="money_value_property", type="money_value", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $moneyValueProperty;

    /**
     * @var mixed
     *
     * @ORM\Column(name="object_property", type="object", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $objectProperty;

    /**
     * @var float
     *
     * @ORM\Column(name="percent_property", type="percent", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $percentProperty;

    /**
     * @var array
     *
     * @ORM\Column(name="simple_array_property", type="simple_array", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $simpleArrayProperty;

    /**
     * @var int
     *
     * @ORM\Column(name="smallint_property", type="smallint", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $smallintProperty;

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
     * @ORM\Column(name="text_property", type="text", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $textProperty;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_property", type="time", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $timeProperty;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(name="date_immutable_property", type="date_immutable", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $dateImmutable;

    /**
     * @var \DateInterval
     *
     * @ORM\Column(name="dateinterval_property", type="dateinterval", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $dateInterval;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(name="datetime_immutable_property", type="datetime_immutable", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $datetimeImmutable;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(name="datetimetz_immutable_property", type="datetimetz_immutable", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $datetimetzImmutable;

    /**
     * @var array
     *
     * @ORM\Column(name="json_property", type="json", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $json;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(name="time_immutable_property", type="time_immutable", nullable=true)
     * @ConfigField(defaultValues={"dataaudit"={"auditable"=true}})
     */
    private $timeImmutable;

    /**
     * @var array
     */
    private $additionalFields;

    public function __construct()
    {
        $this->childrenManyToMany = new ArrayCollection();
        $this->childrenOneToMany = new ArrayCollection();
        $this->childrenManyToManyUnidirectional = new ArrayCollection();
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

    public function addChildrenManyToMany(TestAuditDataChild $testAuditDataChild)
    {
        $this->childrenManyToMany->add($testAuditDataChild);
    }

    public function removeChildrenManyToMany(TestAuditDataChild $testAuditDataChild)
    {
        $this->childrenManyToMany->removeElement($testAuditDataChild);
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
    public function setAdditionalFields($fields)
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

    /**
     * @return bool
     */
    public function isBooleanProperty()
    {
        return $this->booleanProperty;
    }

    /**
     * @param bool $booleanProperty
     */
    public function setBooleanProperty($booleanProperty)
    {
        $this->booleanProperty = $booleanProperty;
    }

    /**
     * @return int
     */
    public function getIntegerProperty()
    {
        return $this->integerProperty;
    }

    /**
     * @param int $integerProperty
     */
    public function setIntegerProperty($integerProperty)
    {
        $this->integerProperty = $integerProperty;
    }

    /**
     * @return int
     */
    public function getSmallintProperty()
    {
        return $this->smallintProperty;
    }

    /**
     * @param int $smallintProperty
     */
    public function setSmallintProperty($smallintProperty)
    {
        $this->smallintProperty = $smallintProperty;
    }

    /**
     * @return int
     */
    public function getBigintProperty()
    {
        return $this->bigintProperty;
    }

    /**
     * @param int $bigintProperty
     */
    public function setBigintProperty($bigintProperty)
    {
        $this->bigintProperty = $bigintProperty;
    }

    /**
     * @return string
     */
    public function getTextProperty()
    {
        return $this->textProperty;
    }

    /**
     * @param string $textProperty
     */
    public function setTextProperty($textProperty)
    {
        $this->textProperty = $textProperty;
    }

    /**
     * @return array
     */
    public function getArrayProperty()
    {
        return $this->arrayProperty;
    }

    /**
     * @param array $arrayProperty
     */
    public function setArrayProperty($arrayProperty)
    {
        $this->arrayProperty = $arrayProperty;
    }

    /**
     * @return resource
     */
    public function getBinaryProperty()
    {
        return $this->binaryProperty;
    }

    /**
     * @param resource|string $binaryProperty
     */
    public function setBinaryProperty($binaryProperty)
    {
        $this->binaryProperty = $binaryProperty;
    }

    /**
     * @return resource
     */
    public function getBlobProperty()
    {
        return $this->blobProperty;
    }

    /**
     * @param resource $blobProperty
     */
    public function setBlobProperty($blobProperty)
    {
        $this->blobProperty = $blobProperty;
    }

    /**
     * @return ConfigObject
     */
    public function getConfigObjectProperty()
    {
        return $this->configObjectProperty;
    }

    /**
     * @param ConfigObject $configObjectProperty
     */
    public function setConfigObjectProperty(ConfigObject $configObjectProperty = null)
    {
        $this->configObjectProperty = $configObjectProperty;
    }

    /**
     * @return string
     */
    public function getCryptedStringProperty()
    {
        return $this->cryptedStringProperty;
    }

    /**
     * @param string $cryptedStringProperty
     */
    public function setCryptedStringProperty($cryptedStringProperty)
    {
        $this->cryptedStringProperty = $cryptedStringProperty;
    }

    /**
     * @return string
     */
    public function getCurrencyProperty()
    {
        return $this->currencyProperty;
    }

    /**
     * @param string $currencyProperty
     */
    public function setCurrencyProperty($currencyProperty)
    {
        $this->currencyProperty = $currencyProperty;
    }

    /**
     * @return \DateTime
     */
    public function getDateProperty()
    {
        return $this->dateProperty;
    }

    /**
     * @param \DateTime $dateProperty
     */
    public function setDateProperty(\DateTime $dateProperty = null)
    {
        $this->dateProperty = $dateProperty;
    }

    /**
     * @return \DateTime
     */
    public function getDateTimeProperty()
    {
        return $this->dateTimeProperty;
    }

    /**
     * @param \DateTime $dateTimeProperty
     */
    public function setDateTimeProperty(\DateTime $dateTimeProperty = null)
    {
        $this->dateTimeProperty = $dateTimeProperty;
    }

    /**
     * @return \DateTime
     */
    public function getDateTimeTzProperty()
    {
        return $this->dateTimeTzProperty;
    }

    /**
     * @param \DateTime $dateTimeTzProperty
     */
    public function setDateTimeTzProperty(\DateTime $dateTimeTzProperty = null)
    {
        $this->dateTimeTzProperty = $dateTimeTzProperty;
    }

    /**
     * @return int
     */
    public function getDurationProperty()
    {
        return $this->durationProperty;
    }

    /**
     * @param int $durationProperty
     */
    public function setDurationProperty($durationProperty)
    {
        $this->durationProperty = $durationProperty;
    }

    /**
     * @return float
     */
    public function getDecimalProperty()
    {
        return $this->decimalProperty;
    }

    /**
     * @param float $decimalProperty
     */
    public function setDecimalProperty($decimalProperty)
    {
        $this->decimalProperty = $decimalProperty;
    }

    /**
     * @return float
     */
    public function getFloatProperty()
    {
        return $this->floatProperty;
    }

    /**
     * @param float $floatProperty
     */
    public function setFloatProperty($floatProperty)
    {
        $this->floatProperty = $floatProperty;
    }

    /**
     * @return string
     */
    public function getGuidProperty()
    {
        return $this->guidProperty;
    }

    /**
     * @param string $guidProperty
     */
    public function setGuidProperty($guidProperty)
    {
        $this->guidProperty = $guidProperty;
    }

    /**
     * @return string
     */
    public function getHtmlEscapedProperty()
    {
        return $this->htmlEscapedProperty;
    }

    /**
     * @param string $htmlEscapedProperty
     */
    public function setHtmlEscapedProperty($htmlEscapedProperty)
    {
        $this->htmlEscapedProperty = $htmlEscapedProperty;
    }

    /**
     * @return array
     */
    public function getJsonArrayProperty()
    {
        return $this->jsonArrayProperty;
    }

    /**
     * @param array $jsonArrayProperty
     */
    public function setJsonArrayProperty($jsonArrayProperty)
    {
        $this->jsonArrayProperty = $jsonArrayProperty;
    }

    /**
     * @return TestAuditDataChild[]|Collection
     */
    public function getChildrenManyToManyUnidirectional(): Collection
    {
        return $this->childrenManyToManyUnidirectional;
    }

    public function setChildrenManyToManyUnidirectional(Collection $collection)
    {
        $this->childrenManyToManyUnidirectional = $collection;
    }

    public function addChildrenManyToManyUnidirectional(TestAuditDataChild $child)
    {
        $this->childrenManyToManyUnidirectional->add($child);
    }

    public function removeChildrenManyToManyUnidirectional(TestAuditDataChild $child)
    {
        $this->childrenManyToManyUnidirectional->removeElement($child);
    }

    /**
     * @return float
     */
    public function getMoneyProperty()
    {
        return $this->moneyProperty;
    }

    /**
     * @param float $moneyProperty
     */
    public function setMoneyProperty($moneyProperty)
    {
        $this->moneyProperty = $moneyProperty;
    }

    /**
     * @return string
     */
    public function getMoneyValueProperty()
    {
        return $this->moneyValueProperty;
    }

    /**
     * @param string $moneyValueProperty
     */
    public function setMoneyValueProperty($moneyValueProperty)
    {
        $this->moneyValueProperty = $moneyValueProperty;
    }

    /**
     * @return mixed
     */
    public function getObjectProperty()
    {
        return $this->objectProperty;
    }

    /**
     * @param mixed $objectProperty
     */
    public function setObjectProperty($objectProperty)
    {
        $this->objectProperty = $objectProperty;
    }

    /**
     * @return TestAuditDataChild
     */
    public function getChildUnidirectional()
    {
        return $this->childUnidirectional;
    }

    /**
     * @param TestAuditDataChild $childUnidirectional
     */
    public function setChildUnidirectional(TestAuditDataChild $childUnidirectional = null)
    {
        $this->childUnidirectional = $childUnidirectional;
    }

    /**
     * @return float
     */
    public function getPercentProperty()
    {
        return $this->percentProperty;
    }

    /**
     * @param float $percentProperty
     */
    public function setPercentProperty($percentProperty)
    {
        $this->percentProperty = $percentProperty;
    }

    /**
     * @return array
     */
    public function getSimpleArrayProperty()
    {
        return $this->simpleArrayProperty;
    }

    /**
     * @param array $simpleArrayProperty
     */
    public function setSimpleArrayProperty($simpleArrayProperty)
    {
        $this->simpleArrayProperty = $simpleArrayProperty;
    }

    /**
     * @return \DateTime
     */
    public function getTimeProperty()
    {
        return $this->timeProperty;
    }

    /**
     * @param \DateTime $timeProperty
     */
    public function setTimeProperty(\DateTime $timeProperty = null)
    {
        $this->timeProperty = $timeProperty;
    }

    /**
     * @return TestAuditDataChild
     */
    public function getChildCascade()
    {
        return $this->childCascade;
    }

    /**
     * @param TestAuditDataChild $childCascade
     */
    public function setChildCascade(TestAuditDataChild $childCascade = null)
    {
        $this->childCascade = $childCascade;
    }

    /**
     * @return TestAuditDataChild
     */
    public function getChildOrphanRemoval()
    {
        return $this->childOrphanRemoval;
    }

    /**
     * @param TestAuditDataChild $childOrphanRemoval
     */
    public function setChildOrphanRemoval(TestAuditDataChild $childOrphanRemoval = null)
    {
        $this->childOrphanRemoval = $childOrphanRemoval;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDateImmutable()
    {
        return $this->dateImmutable;
    }

    /**
     * @param \DateTimeImmutable $dateImmutable
     */
    public function setDateImmutable(\DateTimeImmutable $dateImmutable = null)
    {
        $this->dateImmutable = $dateImmutable;
    }

    /**
     * @return \DateInterval
     */
    public function getDateInterval()
    {
        return $this->dateInterval;
    }

    /**
     * @param \DateInterval $dateInterval
     */
    public function setDateInterval(\DateInterval $dateInterval = null)
    {
        $this->dateInterval = $dateInterval;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDatetimeImmutable()
    {
        return $this->datetimeImmutable;
    }

    /**
     * @param \DateTimeImmutable $datetimeImmutable
     */
    public function setDatetimeImmutable(\DateTimeImmutable $datetimeImmutable = null)
    {
        $this->datetimeImmutable = $datetimeImmutable;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDatetimetzImmutable()
    {
        return $this->datetimetzImmutable;
    }

    /**
     * @param \DateTimeImmutable $datetimetzImmutable
     */
    public function setDatetimetzImmutable(\DateTimeImmutable $datetimetzImmutable = null)
    {
        $this->datetimetzImmutable = $datetimetzImmutable;
    }

    /**
     * @return array
     */
    public function getJson()
    {
        return $this->json;
    }

    public function setJson(array $json = [])
    {
        $this->json = $json;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getTimeImmutable()
    {
        return $this->timeImmutable;
    }

    /**
     * @param \DateTimeImmutable $timeImmutable
     */
    public function setTimeImmutable(\DateTimeImmutable $timeImmutable = null)
    {
        $this->timeImmutable = $timeImmutable;
    }
}
