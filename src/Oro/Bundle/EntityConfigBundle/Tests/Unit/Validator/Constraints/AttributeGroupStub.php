<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;

class AttributeGroupStub extends AttributeGroup
{
    /** @var int */
    private $id;

    /** @var string */
    private $label;

    /**
     * @param int         $id
     * @param null|string $label
     */
    public function __construct($id, $label = null)
    {
        parent::__construct();

        $this->id = $id;
        $this->label = $label;
    }

    /**
     * @return null|string
     */
    public function getDefaultLabel()
    {
        return $this->label;
    }
}
