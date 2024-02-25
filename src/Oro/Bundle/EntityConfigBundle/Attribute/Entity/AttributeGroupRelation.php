<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;

/**
* Entity that represents Attribute Group Relation
*
*/
#[ORM\Entity(repositoryClass: AttributeGroupRelationRepository::class)]
#[ORM\Table(name: 'oro_attribute_group_rel')]
#[ORM\UniqueConstraint(name: 'oro_attribute_group_uidx', columns: ['entity_config_field_id', 'attribute_group_id'])]
class AttributeGroupRelation
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    #[ORM\Column(name: 'entity_config_field_id', type: Types::INTEGER)]
    private ?int $entityConfigFieldId = null;

    #[ORM\ManyToOne(targetEntity: AttributeGroup::class, inversedBy: 'attributeRelations')]
    #[ORM\JoinColumn(name: 'attribute_group_id', referencedColumnName: 'id')]
    private ?AttributeGroup $attributeGroup = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getEntityConfigFieldId()
    {
        return $this->entityConfigFieldId;
    }

    /**
     * @param $entityConfigFieldId
     * @return $this
     */
    public function setEntityConfigFieldId($entityConfigFieldId)
    {
        $this->entityConfigFieldId = $entityConfigFieldId;

        return $this;
    }

    /**
     * @return AttributeGroup
     */
    public function getAttributeGroup()
    {
        return $this->attributeGroup;
    }

    /**
     * @param AttributeGroup $attributeGroup
     * @return $this
     */
    public function setAttributeGroup($attributeGroup)
    {
        $this->attributeGroup = $attributeGroup;

        return $this;
    }
}
