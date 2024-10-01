<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamilyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Store test activity target.
 */
#[ORM\Entity]
#[ORM\Table(name: 'test_activity_target')]
#[Config(defaultValues: ['attribute' => ['has_attributes' => true]])]
class TestActivityTarget implements
    TestFrameworkEntityInterface,
    AttributeFamilyAwareInterface,
    ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AttributeFamily::class)]
    #[ORM\JoinColumn(name: 'attribute_family_id', referencedColumnName: 'id', onDelete: 'RESTRICT')]
    protected ?AttributeFamily $attributeFamily = null;

    #[ORM\Column(name: 'string', type: Types::STRING, nullable: true)]
    protected ?string $string = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @return $this
     */
    #[\Override]
    public function setAttributeFamily(AttributeFamily $attributeFamily)
    {
        $this->attributeFamily = $attributeFamily;

        return $this;
    }

    /**
     * @return AttributeFamily
     */
    #[\Override]
    public function getAttributeFamily()
    {
        return $this->attributeFamily;
    }

    /**
     * @return string
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * @param string $string
     * @return TestActivityTarget
     */
    public function setString($string)
    {
        $this->string = $string;

        return $this;
    }
}
