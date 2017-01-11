<?php

namespace Oro\Bundle\EntityConfigBundle\Attribute\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;

/**
 * @ORM\Table(
 *     name="oro_attribute_group_rel",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_attribute_group_uidx",
 *              columns={"entity_config_field_id", "attribute_group_id"}
 *          )
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository")
 */
class AttributeGroupRelation
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="entity_config_field_id", type="integer")
     */
    private $entityConfigFieldId;

    /**
     * @var AttributeGroup
     * @ORM\ManyToOne(targetEntity="AttributeGroup", inversedBy="attributeRelations")
     * @ORM\JoinColumn(name="attribute_group_id", referencedColumnName="id")
     */
    private $attributeGroup;

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
