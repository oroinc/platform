<?php

namespace Oro\Bundle\ActionBundle\Resolver;

use Oro\Bundle\ActionBundle\Model\OptionsAssembler;
use Oro\Component\ConfigExpression\ContextAccessor;

class OptionsResolver
{
    /** @var OptionsAssembler */
    protected $optionsAssembler;

    /** @var ContextAccessor */
    protected $contextAccessor;

    /**
     * @param OptionsAssembler $optionsAssembler
     * @param ContextAccessor $contextAccessor
     */
    public function __construct(OptionsAssembler $optionsAssembler, ContextAccessor $contextAccessor)
    {
        $this->optionsAssembler = $optionsAssembler;
        $this->contextAccessor = $contextAccessor;
    }

    /**
     * @param object|array $data
     * @param array $options
     *
     * @return array
     */
    public function resolveOptions($data, array $options)
    {
        return $this->resolveValues($data, $this->optionsAssembler->assemble($options));
    }

    /**
     * @param object|array $data
     * @param array $options
     *
     * @return array
     */
    protected function resolveValues($data, array $options)
    {
        foreach ($options as &$value) {
            if (is_array($value)) {
                $value = $this->resolveValues($data, $value);
            } else {
                $value = $this->contextAccessor->getValue($data, $value);
            }
        }

        return $options;
    }
}
