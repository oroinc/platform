<?php

namespace Oro\Bundle\EntityExtendBundle\Annotation\ORM;

/**
 * @Annotation
 * @Target("CLASS")
 */
class DiscriminatorValue
{
    /** @var string */
    private $value;

    public function __construct(array $data)
    {
        $this->value = $data['value'];
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
