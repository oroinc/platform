<?php

namespace Oro\Bundle\PlatformBundle\Model;

/**
 * Deployment Variable model
 */
class DeploymentVariable
{
    /** @var string */
    private $label;

    /** @var string|null */
    private $value;

    /**
     * @param string $label
     * @param string|null $value
     *
     * @return $this
     */
    public static function create(string $label, ?string $value = null)
    {
        $variable = new static();
        $variable->label = $label;
        $variable->value = $value;

        return $variable;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
}
