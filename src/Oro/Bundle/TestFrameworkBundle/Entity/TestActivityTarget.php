<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamilyAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\TestFrameworkBundle\Model\ExtendTestActivityTarget;

/**
 * @ORM\Table(name="test_activity_target")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "attribute"={
 *              "has_attributes"=true
 *          }
 *      }
 * )
 */
class TestActivityTarget extends ExtendTestActivityTarget implements
    TestFrameworkEntityInterface,
    AttributeFamilyAwareInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var AttributeFamily
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily")
     * @ORM\JoinColumn(name="attribute_family_id", referencedColumnName="id", onDelete="RESTRICT")
     */
    protected $attributeFamily;

    /**
     * @var string
     *
     * @ORM\Column(name="string", type="string", nullable=true)
     */
    protected $string;

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
    public function setAttributeFamily(AttributeFamily $attributeFamily)
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

    /**
     * @param string $name
     * @param array $arguments
     * @return $this|null
     */
    public function __call($name, $arguments)
    {
        preg_match('/^(set|get).*/', $name, $matches);
        if (count($matches) !== 2) {
            return;
        }

        $fieldName = strtolower(str_replace($matches[1], '', $matches[0]));
        if (strtolower($matches[1]) === 'get') {
            return isset($this->serialized_data[$fieldName])
                ? $this->serialized_data[$fieldName]
                : null;
        }

        $this->serialized_data[$fieldName] = $arguments[0];
        
        return $this;
    }
}
