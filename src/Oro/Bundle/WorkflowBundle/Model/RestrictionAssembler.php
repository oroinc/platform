<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;
use Oro\Bundle\WorkflowBundle\Exception\UnknownStepException;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\AbstractAssembler as ComponentAbstractAssembler;

use Oro\Bundle\ActionBundle\Exception\AssemblerException;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class RestrictionAssembler extends ComponentAbstractAssembler
{
    /**
     * @var Attribute[]
     */
    protected $attributes;

    /**
     * @var Step[]
     */
    protected $steps;

    /**
     * @param array                  $configuration
     * @param Attribute[]|Collection $attributes
     * @param Step[]|Collection      $steps
     *
     * @return Restriction[]|ArrayCollection
     * @throws InvalidParameterException
     */
    public function assemble(array $configuration, $steps, $attributes)
    {
        $items = $this->getOption($configuration, WorkflowConfiguration::NODE_ENTITY_RESTRICTIONS, []);
        $this->setAttributes($attributes);
        $this->setSteps($steps);
        $restrictions = new ArrayCollection();
        foreach ($items as $item) {
            $restriction = new Restriction();
            $step        = $this->getOption($item, 'step');
            $this->assertValidStep($step);
            $attribute = $this->getOption($item, 'attribute');
            $this->assertValidAttribute($attribute);
            $restriction
                ->setStep($step)
                ->setField($this->getOption($item, 'field'))
                ->setAttribute($attribute)
                ->setMode($this->getOption($item, 'mode'))
                ->setValues($this->getOption($item, 'values', []))
                ->setEntity($this->getEntityClass($attribute))
                ->setName($this->getOption($item, 'name'));

            $restrictions->add($restriction);
        }

        return $restrictions;
    }


    /**
     * @param Attribute[]|Collection $attributes
     *
     * @return array
     */
    protected function setAttributes($attributes)
    {
        $this->attributes = [];
        if ($attributes) {
            foreach ($attributes as $attribute) {
                $this->attributes[$attribute->getName()] = $attribute;
            }
        }
    }

    /**
     * @param Step[]|Collection $steps
     *
     * @return array
     */
    protected function setSteps($steps)
    {
        $this->steps = [];
        if ($steps) {
            foreach ($steps as $step) {
                $this->steps[$step->getName()] = $step;
            }
        }
    }

    /**
     * @param string $attribute
     *
     * @return string
     */
    protected function getEntityClass($attribute)
    {
        return $this->attributes[$attribute]->getOption('class');
    }

    /**
     * @param string $step
     *
     * @throws UnknownStepException
     */
    protected function assertValidStep($step)
    {
        if (null === $step || !empty($this->steps[$step])) {
            return;
        }
        throw new UnknownStepException($step);
    }

    /**
     * @param string $attribute
     *
     * @throws AssemblerException
     * @throws UnknownAttributeException
     */
    protected function assertValidAttribute($attribute)
    {
        if (empty($this->attributes[$attribute])) {
            throw new UnknownAttributeException($attribute);
        }
        if ($this->attributes[$attribute]->getType() !== 'entity') {
            throw new AssemblerException(
                'Entity restrictions must be configured with attributes with type "entity"'
                . ' or entity_attribute value'
            );
        }
    }
}
