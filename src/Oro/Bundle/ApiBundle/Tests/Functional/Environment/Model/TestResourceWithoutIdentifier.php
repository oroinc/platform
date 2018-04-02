<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model;

/**
 * A model for testing API resource without identifier.
 */
class TestResourceWithoutIdentifier
{
    /** @var string|null */
    private $name;

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
}
