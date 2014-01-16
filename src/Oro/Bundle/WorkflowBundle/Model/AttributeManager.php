<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;

class AttributeManager
{
    const ATTRIBUTE_ENTITY = 'entity';
    const ATTRIBUTE_STEP = 'step';

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
     * Get attributes with option "managed_entity"
     *
     * @return Collection|Attribute[]
     */
    public function getManagedEntityAttributes()
    {
        return $this->getAttributes()->filter(
            function (Attribute $attribute) {
                return $attribute->getType() == 'entity' && $attribute->getOption('managed_entity');
            }
        );
    }

    /**
     * Get list of attributes that require binding
     *
     * @return Collection|Attribute[]
     */
    public function getBindEntityAttributes()
    {
        return $this->getAttributes()->filter(
            function (Attribute $attribute) {
                return $attribute->getType() == 'entity' && $attribute->getOption('bind');
            }
        );
    }

    /**
     * Get list of attributes names that require binding
     *
     * @return array
     */
    public function getBindEntityAttributeNames()
    {
        $result = array();

        /** @var Attribute $attribute  */
        foreach ($this->getBindEntityAttributes() as $attribute) {
            $result[] = $attribute->getName();
        }

        return $result;
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

    /**
     * @return Attribute
     * @throws UnknownAttributeException
     */
    public function getStepAttribute()
    {
        if (!$this->attributes->containsKey(self::ATTRIBUTE_STEP)) {
            throw new UnknownAttributeException('There is no step attribute');
        }

        return $this->attributes->get(self::ATTRIBUTE_STEP);
    }
}
