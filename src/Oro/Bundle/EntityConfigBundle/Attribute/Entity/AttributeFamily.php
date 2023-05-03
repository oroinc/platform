<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Layout\ContextItemInterface;

/**
 * An attribute family is a set of the attributes that are enough to store complete information
 * about the entities of a similar type
 *
 * @ORM\Table(
 *     name="oro_attribute_family",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="oro_attribute_family_code_org_uidx", columns={"code", "organization_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository")
 * @Config(
 *      defaultValues={
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "ownership"={
 *              "owner_type"="ORGANIZATION",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="catalog"
 *          }
 *      }
 * )
 * @method File getImage()
 * @method AttributeFamily setImage(File $image)
 * @method LocalizedFallbackValue getLabel(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultLabel()
 */
class AttributeFamily implements
    DatesAwareInterface,
    ContextItemInterface,
    ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var ArrayCollection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_attribute_family_label",
     *      joinColumns={
     *          @ORM\JoinColumn(name="attribute_family_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "order"=40,
     *              "full"=true,
     *              "fallback_field"="string"
     *          }
     *      }
     * )
     */
    protected $labels;

    /**
     * @var string
     * @ORM\Column(name="code", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     *  )
     */
    private $code;

    /**
     * @var string
     * @ORM\Column(name="entity_class", type="string", length=255)
     */
    private $entityClass;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup",
     *     mappedBy="attributeFamily",
     *     cascade={"ALL"},
     *     orphanRemoval=true,
     *     indexBy="code"
     * )
     * @ORM\OrderBy({"id" = "ASC"})
     */
    private $attributeGroups;

    /**
     * @var bool
     * @ORM\Column(name="is_enabled", type="boolean", length=255)
     */
    private $isEnabled = true;

    /**
     * @var Organization
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->attributeGroups = new ArrayCollection();
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return $this
     */
    public function addLabel(LocalizedFallbackValue $label)
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }

        return $this;
    }

    /**
     * @param LocalizedFallbackValue $label
     *
     * @return $this
     */
    public function removeLabel(LocalizedFallbackValue $label)
    {
        if ($this->labels->contains($label)) {
            $this->labels->removeElement($label);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|\Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue[]
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * @return Organization
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Organization $owner
     *
     * @return $this
     */
    public function setOwner(Organization $owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param bool $isEnabled
     * @return $this
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @param string $entityClass
     * @return AttributeFamily
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @return ArrayCollection|AttributeGroup[]
     */
    public function getAttributeGroups()
    {
        return $this->attributeGroups;
    }

    /**
     * @param Collection $attributeGroups
     * @return AttributeFamily
     */
    public function setAttributeGroups(Collection $attributeGroups)
    {
        $this->attributeGroups = $attributeGroups;

        return $this;
    }

    /**
     * @param string $code
     * @return null|AttributeGroup
     */
    public function getAttributeGroup($code)
    {
        if (!isset($this->attributeGroups[$code])) {
            return null;
        }

        return $this->attributeGroups[$code];
    }

    /**
     * @param AttributeGroup $attributeGroup
     * @return $this
     */
    public function addAttributeGroup(AttributeGroup $attributeGroup)
    {
        $this->attributeGroups[$attributeGroup->getCode()] = $attributeGroup;
        $attributeGroup->setAttributeFamily($this);

        return $this;
    }

    /**
     * @param AttributeGroup $attributeGroup
     * @return $this
     */
    public function removeAttributeGroup(AttributeGroup $attributeGroup)
    {
        if ($this->attributeGroups->contains($attributeGroup)) {
            $this->attributeGroups->removeElement($attributeGroup);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getDefaultLabel()->getString();
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        return 'code:'.$this->getCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getHash()
    {
        $data = [];

        /** @var AttributeGroup $group */
        $groups = $this->getAttributeGroups();

        foreach ($groups as $group) {
            $item = ['group' => $group->getId(), 'attributes' => [], 'visible' => $group->getIsVisible()];

            /** @var AttributeGroupRelation $attributeRelation */
            foreach ($group->getAttributeRelations() as $attributeRelation) {
                $item['attributes'][] = $attributeRelation->getId();
            }
            $data[] = $item;
        }

        return md5(serialize([$this->getId() => $data]));
    }
}
