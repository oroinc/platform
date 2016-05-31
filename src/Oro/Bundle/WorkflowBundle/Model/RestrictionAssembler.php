<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\AbstractAssembler;

use Oro\Bundle\ActionBundle\Exception\AssemblerException;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;

class RestrictionAssembler extends AbstractAssembler
{
    /**
     * @var EntityConnector
     */
    protected $connector;

    /**
     * @var Attribute[]
     */
    protected $attributes;

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
        // TODO add restriction validation(steps, attributes etc.)
        $restrictions = new ArrayCollection();
        foreach ($items as $item) {
            $restriction = new Restriction();
            $restriction
                ->setStep($this->getOption($item, 'step'))
                ->setField($this->getOption($item, 'field'))
                ->setMode($this->getOption($item, 'mode'))
                ->setValues($this->getOption($item, 'values', []))
                ->setEntity($this->getEntityClass($item))
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
     * @param array $item
     *
     * @return string
     * @throws AssemblerException
     */
    protected function getEntityClass(array $item)
    {
        $entity = $this->getOption($item, 'entity');

        foreach ($this->attributes as $attribute) {
            if ($attribute->getName() === $entity && $attribute->getType() === 'entity') {
                return $attribute->getOption('class');
            }
        }
        throw new AssemblerException(
            'Entity restrictions must be configured with attributes with type "entity"'
            . ' or entity_attribute value'
        );
    }
}
