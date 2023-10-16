<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * ORM Entity TestEntityFields.
 *
 * @ORM\Entity()
 * @ORM\Table(
 *     name="oro_test_framework_test_entity_fields"
 * )
 * @Config()
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class TestEntityFields implements ExtendEntityInterface, TestFrameworkEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(
     *     name="integer_field",
     *     type="integer",
     *     nullable=true
     * )
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={
     *             "auditable"=true
     *         }
     *     }
     * )
     */
    private $integerField;

    /**
     * @ORM\Column(
     *     name="float_field",
     *     type="float",
     *     nullable=true
     * )
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={
     *             "auditable"=true
     *         }
     *     }
     * )
     */
    private $floatField;

    /**
     * @ORM\Column(
     *     name="decimal_field",
     *     type="decimal",
     *     nullable=true
     * )
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={
     *             "auditable"=true
     *         }
     *     }
     * )
     */
    private $decimalField;

    /**
     * @ORM\Column(
     *     name="smallint_field",
     *     type="smallint",
     *     nullable=true
     * )
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={
     *             "auditable"=true
     *         }
     *     }
     * )
     */
    private $smallintField;

    /**
     * @ORM\Column(
     *     name="bigint_field",
     *     type="bigint",
     *     nullable=true
     * )
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={
     *             "auditable"=true
     *         }
     *     }
     * )
     */
    private $bigintField;

    /**
     * @ORM\Column(
     *     name="text_field",
     *     type="text",
     *     nullable=true
     * )
     * @ConfigField(
     *     defaultValues={
     *         "importexport"={
     *             "excluded"=true
     *         }
     *     }
     * )
     */
    private $textField;

    /**
     * @ORM\Column(
     *     name="date_field",
     *     type="date",
     *     nullable=true
     * )
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={
     *             "auditable"=true
     *         }
     *     }
     * )
     */
    private $dateField;

    /**
     * @ORM\Column(
     *     name="datetime_field",
     *     type="datetime",
     *     nullable=true
     * )
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={
     *             "auditable"=true
     *         }
     *     }
     * )
     */
    private $datetimeField;

    /**
     * @ORM\Column(
     *     name="boolean_field",
     *     type="boolean",
     *     nullable=true
     * )
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={
     *             "auditable"=true
     *         }
     *     }
     * )
     */
    private $booleanField;

    /**
     * @ORM\Column(
     *     name="html_field",
     *     type="text",
     *     nullable=true
     * )
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={
     *             "auditable"=true
     *         }
     *     }
     * )
     */
    private $htmlField;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="TestExtendedEntity"
     * )
     * @ORM\JoinColumn(
     *     name="many_to_one_relation_id",
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={
     *             "auditable"=true
     *         }
     *     }
     * )
     */
    private $manyToOneRelation;

    /**
     * @ORM\ManyToMany(
     *     targetEntity="TestExtendedEntity"
     * )
     * @ORM\JoinTable(
     *     name="oro_test_framework_many_to_many_relation_to_test_entity_fields",
     *     joinColumns={
     *         @ORM\JoinColumn(name="test_entity_fields_id", referencedColumnName="id", onDelete="CASCADE")
     *     },
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="oro_product_id", referencedColumnName="id", onDelete="CASCADE")
     *     }
     * )
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={
     *             "auditable"=true
     *         },
     *         "importexport"={
     *             "excluded"=true
     *         }
     *     }
     * )
     */
    private $manyToManyRelation;

    /**
     * @ORM\Column(
     *     name="string_field",
     *     type="string",
     *     length=10,
     *     nullable=false
     * )
     * @ConfigField(
     *     defaultValues={
     *         "dataaudit"={
     *             "auditable"=true
     *         }
     *     }
     * )
     */
    private $stringField;

    public function __construct()
    {
        $this->manyToManyRelation = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntegerField(): ?int
    {
        return $this->integerField;
    }

    public function setIntegerField(?int $integerField): self
    {
        $this->integerField = $integerField;

        return $this;
    }

    public function getFloatField(): ?float
    {
        return $this->floatField;
    }

    public function setFloatField(?float $floatField): self
    {
        $this->floatField = $floatField;

        return $this;
    }

    public function getDecimalField(): ?string
    {
        return $this->decimalField;
    }

    public function setDecimalField(?string $decimalField): self
    {
        $this->decimalField = $decimalField;

        return $this;
    }

    public function getSmallintField(): ?int
    {
        return $this->smallintField;
    }

    public function setSmallintField(?int $smallintField): self
    {
        $this->smallintField = $smallintField;

        return $this;
    }

    public function getBigintField(): ?string
    {
        return $this->bigintField;
    }

    public function setBigintField(?string $bigintField): self
    {
        $this->bigintField = $bigintField;

        return $this;
    }

    public function getTextField(): ?string
    {
        return $this->textField;
    }

    public function setTextField(?string $textField): self
    {
        $this->textField = $textField;

        return $this;
    }

    public function getDateField(): ?\DateTimeInterface
    {
        return $this->dateField;
    }

    public function setDateField(?\DateTimeInterface $dateField): self
    {
        $this->dateField = $dateField;

        return $this;
    }

    public function getDatetimeField(): ?\DateTimeInterface
    {
        return $this->datetimeField;
    }

    public function setDatetimeField(?\DateTimeInterface $datetimeField): self
    {
        $this->datetimeField = $datetimeField;

        return $this;
    }

    public function getBooleanField(): ?bool
    {
        return $this->booleanField;
    }

    public function setBooleanField(?bool $booleanField): self
    {
        $this->booleanField = $booleanField;

        return $this;
    }

    public function getHtmlField(): ?string
    {
        return $this->htmlField;
    }

    public function setHtmlField(?string $htmlField): self
    {
        $this->htmlField = $htmlField;

        return $this;
    }

    public function getManyToOneRelation(): ?TestExtendedEntity
    {
        return $this->manyToOneRelation;
    }

    public function setManyToOneRelation(?TestExtendedEntity $manyToOneRelation): self
    {
        $this->manyToOneRelation = $manyToOneRelation;

        return $this;
    }

    /**
     * @return Collection<int, TestExtendedEntity>
     */
    public function getManyToManyRelation(): Collection
    {
        return $this->manyToManyRelation;
    }

    public function addManyToManyRelation(TestExtendedEntity $manyToManyRelation): self
    {
        if (!$this->manyToManyRelation->contains($manyToManyRelation)) {
            $this->manyToManyRelation[] = $manyToManyRelation;
        }

        return $this;
    }

    public function removeManyToManyRelation(TestExtendedEntity $manyToManyRelation): self
    {
        $this->manyToManyRelation->removeElement($manyToManyRelation);

        return $this;
    }

    public function getStringField(): ?string
    {
        return $this->stringField;
    }

    public function setStringField(string $stringField): self
    {
        $this->stringField = $stringField;

        return $this;
    }
}
