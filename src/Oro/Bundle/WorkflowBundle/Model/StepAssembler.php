<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

class StepAssembler extends AbstractAssembler
{
    /**
     * @var Attribute[]
     */
    protected $attributes;

    /**
     * @param array $configuration
     * @param Attribute[]|Collection $attributes
     * @return ArrayCollection
     */
    public function assemble(array $configuration, $attributes)
    {
        $this->setAttributes($attributes);

        $steps = new ArrayCollection();
        foreach ($configuration as $name => $options) {
            $step = $this->assembleStep($name, $options);
            $steps->set($name, $step);
        }

        $this->attributes = array();

        return $steps;
    }

    /**
     * @param string $name
     * @param array $options
     * @return Step
     */
    protected function assembleStep($name, array $options)
    {
        $this->assertOptions($options, array('label'));

        $step = new Step();
        $step->setName($name)
            ->setLabel($options['label'])
            ->setOrder($this->getOption($options, 'order', 0))
            ->setIsFinal($this->getOption($options, 'is_final', false))
            ->setAllowedTransitions($this->getOption($options, 'allowed_transitions', array()));

        return $step;
    }

    /**
     * @param Attribute[]|Collection $attributes
     * @return array
     */
    protected function setAttributes($attributes)
    {
        $this->attributes = array();
        if ($attributes) {
            foreach ($attributes as $attribute) {
                $this->attributes[$attribute->getName()] = $attribute;
            }
        }
    }

    /**
     * @param array $attributeNames
     * @param string $stepName
     * @throws UnknownAttributeException
     */
    protected function assertAttributesExist(array $attributeNames, $stepName)
    {
        foreach ($attributeNames as $attributeName) {
            $this->assertAttributeExists($attributeName, $stepName);
        }
    }

    /**
     * @param string $attributeName
     * @param string $stepName
     * @throws UnknownAttributeException
     */
    protected function assertAttributeExists($attributeName, $stepName)
    {
        if (!isset($this->attributes[$attributeName])) {
            throw new UnknownAttributeException(
                sprintf('Unknown attribute "%s" at step "%s"', $attributeName, $stepName)
            );
        }
    }
}
