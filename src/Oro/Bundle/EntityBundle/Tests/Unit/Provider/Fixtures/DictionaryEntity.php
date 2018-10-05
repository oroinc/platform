<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider\Fixtures;

class DictionaryEntity
{
    /** @var string|null */
    private $name;

    /** @var string|null */
    private $label;

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string|null $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
}
