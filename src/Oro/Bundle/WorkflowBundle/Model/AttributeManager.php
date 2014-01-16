<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;

class AttributeManager
{
    const ATTRIBUTE_ENTITY = 'entity';

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
     * @return Attribute[]|Collection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param Attribute[]|Collection $attributes
     * @return Workflow
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
        if (!$this->attributes->containsKey(self::ATTRIBUTE_ENTITY)) {
            throw new UnknownAttributeException('There is no entity attribute');
        }

        return $this->attributes->get(self::ATTRIBUTE_ENTITY);
    }
}
