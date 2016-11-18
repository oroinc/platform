<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;
use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\Action\Action\Configurable as ConfigurableAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\AbstractAssembler as BaseAbstractAssembler;

/**
 * Assembles form options that can be passed to transition and step
 */
class FormOptionsAssembler extends BaseAbstractAssembler
{
    const STEP_OWNER = 'step';
    const TRANSITION_OWNER = 'transition';

    /**
     * @var Attribute[]
     */
    protected $attributes;

    /**
     * @var ActionFactory
     */
    protected $actionFactory;

    /**
     * @param ActionFactory $actionFactory
     */
    public function __construct(ActionFactory $actionFactory)
    {
        $this->actionFactory = $actionFactory;
    }

    /**
     * @param array $options
     * @param Attribute[]|Collection $attributes
     * @param string $owner
     * @param string $ownerName
     * @return array
     * @throws InvalidParameterException
     */
    public function assemble(array $options, $attributes, $owner, $ownerName)
    {
        $this->setAttributes($attributes);

        $attributeFields = $this->getOption($options, 'attribute_fields', array());

        if (!is_array($attributeFields)) {
            throw new InvalidParameterException(
                sprintf('Option "form_options.attribute_fields" at %s "%s" must be an array.', $owner, $ownerName)
            );
        }

        $attributeFieldKeys = array_keys($attributeFields);
        foreach ($attributeFieldKeys as $attributeName) {
            $this->assertAttributeExists($attributeName, $owner, $ownerName);
        }

        $options['attribute_fields'] = $this->passConfiguration($attributeFields);

        if (!empty($options['attribute_default_values'])) {
            $value = $options['attribute_default_values'];
            if (!is_array($value)) {
                throw new InvalidParameterException(
                    sprintf(
                        'Option "form_options.attribute_default_values" of %s "%s" must be an array.',
                        $owner,
                        $ownerName
                    )
                );
            }

            $arrayKeys = array_keys($value);
            foreach ($arrayKeys as $attributeName) {
                $this->assertAttributeExists($attributeName, $owner, $ownerName);
                if (!array_key_exists($attributeName, $attributeFields)) {
                    throw new InvalidParameterException(
                        sprintf(
                            'Form options of %s "%s" doesn\'t have attribute "%s" which is referenced in ' .
                            '"attribute_default_values" option.',
                            $owner,
                            $ownerName,
                            $attributeName
                        )
                    );
                }
            }
            $options['attribute_default_values'] = $this->passConfiguration($value);
        }

        if (!empty($options['form_init'])) {
            $options['form_init'] =
                $this->actionFactory->create(ConfigurableAction::ALIAS, $options['form_init']);
        }

        return $options;
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
     * @param string $attributeName
     * @param string $owner
     * @param string $ownerName
     * @throws UnknownAttributeException
     */
    protected function assertAttributeExists($attributeName, $owner, $ownerName)
    {
        if (!isset($this->attributes[$attributeName])) {
            throw new UnknownAttributeException(
                sprintf('Unknown attribute "%s" at %s "%s".', $attributeName, $owner, $ownerName)
            );
        }
    }
}
