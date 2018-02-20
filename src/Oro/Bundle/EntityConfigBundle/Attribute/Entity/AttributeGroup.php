<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\PrePersist;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityConfigBundle\Model\ExtendAttributeGroup;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * @ORM\Table(name="oro_attribute_group")
 * @ORM\Entity(repositoryClass="Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRepository")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      mode="hidden"
 * )
 */
class AttributeGroup extends ExtendAttributeGroup implements DatesAwareInterface
{
    use DatesAwareTrait;

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
     *      name="oro_attribute_group_label",
     *      joinColumns={
     *          @ORM\JoinColumn(name="attribute_group_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $labels;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=255, unique=false)
     */
    private $code;

    /**
     * @var AttributeFamily
     * @ORM\ManyToOne(
     *     targetEntity="Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily", inversedBy="attributeGroups"
     * )
     * @ORM\JoinColumn(name="attribute_family_id", referencedColumnName="id")
     */
    private $attributeFamily;

    /**
     * @var Collection|AttributeGroupRelation[]
     * @ORM\OneToMany(
     *     targetEntity="AttributeGroupRelation",
     *     mappedBy="attributeGroup",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    private $attributeRelations;

    /**
     * @var bool
     * @ORM\Column(name="is_visible", type="boolean", nullable=false, options={"default"=true})
     */
    private $isVisible = true;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->labels = new ArrayCollection();
        $this->attributeRelations = new ArrayCollection();
    }

    /**
     * @param LocalizedFallbackValue $label
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
     * @param AttributeGroupRelation $attributeRelation
     * @return $this
     */
    public function addAttributeRelation(AttributeGroupRelation $attributeRelation)
    {
        if (!$this->attributeRelations->contains($attributeRelation)) {
            $this->attributeRelations->add($attributeRelation);
            $attributeRelation->setAttributeGroup($this);
        }

        return $this;
    }

    /**
     * @param AttributeGroupRelation $attributeRelation
     * @return $this
     */
    public function removeAttributeRelation(AttributeGroupRelation $attributeRelation)
    {
        if ($this->attributeRelations->contains($attributeRelation)) {
            $this->attributeRelations->removeElement($attributeRelation);
        }

        return $this;
    }

    /**
     * @return ArrayCollection|AttributeGroupRelation[]
     */
    public function getAttributeRelations()
    {
        return $this->attributeRelations;
    }

    /**
     * @return ArrayCollection|\Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue[]
     */
    public function getLabels()
    {
        return $this->labels;
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
     * @param AttributeFamily $attributeFamily
     * @return $this
     */
    public function setAttributeFamily($attributeFamily)
    {
        $this->attributeFamily = $attributeFamily;

        return $this;
    }

    /**
     * @return AttributeFamily
     */
    public function getAttributeFamily()
    {
        return $this->attributeFamily;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param bool $isVisible
     * @return $this
     */
    public function setIsVisible($isVisible)
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsVisible()
    {
        return $this->isVisible;
    }
}
