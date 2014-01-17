<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;

class AttributeManager
{
    /**
     * @var string
     */
    protected $entityAttributeName;

    /**
     * @var Attribute[]|Collection
     */
    protected $attributes;

    /**
     * @param Attribute[]|Collection $attributes
     */
    public function __construct(Collection $attributes = null)
    {
        $this->attributes = $attributes ?: new ArrayCollection();
    }

    /**
     * @param string $entityAttributeName
     * @return AttributeManager
     */
    public function setEntityAttributeName($entityAttributeName)
    {
        $this->entityAttributeName = $entityAttributeName;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntityAttributeName()
    {
        return $this->entityAttributeName;
    }

    /**
     * @return Attribute[]|Collection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param Attribute[]|Collection $attributes
     * @return AttributeManager
     */
    public function setAttributes($attributes)
    {
        if ($attributes instanceof Collection) {
            $this->attributes = $attributes;
        } else {
            $data = array();
            foreach ($attributes as $attribute) {
                $data[$attribute->getName()] = $attribute;
            }
            unset($attributes);
            $this->attributes = new ArrayCollection($data);
        }

        return $this;
    }

    /**
     * @param string $attributeName
     * @return Attribute
     */
    public function getAttribute($attributeName)
    {
        return $this->attributes->get($attributeName);
    }

    /**
     * @return Attribute
     * @throws UnknownAttributeException
     */
    public function getEntityAttribute()
    {
        if (!$this->attributes->containsKey($this->entityAttributeName)) {
            throw new UnknownAttributeException('There is no entity attribute');
        }

        return $this->attributes->get($this->entityAttributeName);
    }
}
