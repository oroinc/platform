<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroEntityConfigBundle_Attribute_Entity_AttributeGroup;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRepository;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * Represents group of attributes.
 *
 * @method LocalizedFallbackValue getLabel(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultLabel()
 * @mixin OroEntityConfigBundle_Attribute_Entity_AttributeGroup
 */
#[ORM\Entity(repositoryClass: AttributeGroupRepository::class)]
#[ORM\Table(name: 'oro_attribute_group')]
#[ORM\HasLifecycleCallbacks]
#[Config(mode: 'hidden')]
class AttributeGroup implements DatesAwareInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * @var Collection<int, LocalizedFallbackValue>
     */
    #[ORM\ManyToMany(targetEntity: LocalizedFallbackValue::class, cascade: ['ALL'], orphanRemoval: true)]
    #[ORM\JoinTable(name: 'oro_attribute_group_label')]
    #[ORM\JoinColumn(name: 'attribute_group_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'localized_value_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected ?Collection $labels = null;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 255, unique: false)]
    private ?string $code = null;

    #[ORM\ManyToOne(targetEntity: AttributeFamily::class, inversedBy: 'attributeGroups')]
    #[ORM\JoinColumn(name: 'attribute_family_id', referencedColumnName: 'id')]
    private ?AttributeFamily $attributeFamily = null;

    /**
     * @var Collection<int, AttributeGroupRelation>
     */
    #[ORM\OneToMany(
        mappedBy: 'attributeGroup',
        targetEntity: AttributeGroupRelation::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private ?Collection $attributeRelations = null;

    #[ORM\Column(name: 'is_visible', type: Types::BOOLEAN, nullable: false, options: ['default' => true])]
    private ?bool $isVisible = true;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
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
     * @param ArrayCollection $collection
     *
     * @return $this
     */
    public function setAttributeRelations(ArrayCollection $collection)
    {
        $this->attributeRelations = $collection;

        return $this;
    }

    /**
     * @return ArrayCollection|LocalizedFallbackValue[]
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
