<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityConfigBundle\Model\ExtendAttributeFamily;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Layout\ContextItemInterface;

/**
 * @ORM\Table(name="oro_attribute_family")
 * @ORM\Entity(repositoryClass="Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository")
 * @Config(
 *      defaultValues={
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="",
 *              "category"="catalog"
 *          }
 *      }
 * )
 */
class AttributeFamily extends ExtendAttributeFamily implements
    DatesAwareInterface,
    OrganizationAwareInterface,
    ContextItemInterface
{
    use DatesAwareTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
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
     * @ORM\Column(name="code", type="string", length=255, unique=true)
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
     * @var User
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

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
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param User $owner
     *
     * @return $this
     */
    public function setOwner(User $owner)
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
     * @return OrganizationInterface|null
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param OrganizationInterface|null $organization
     * @return $this
     */
    public function setOrganization(OrganizationInterface $organization = null)
    {
        $this->organization = $organization;

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
                $item['attributes'][] = $attributeRelation->getEntityConfigFieldId();
            }
            $data[] = $item;
        }

        return md5(serialize([$this->getId() => $data]));
    }
}
