<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model;

/**
 * A model for testing API resource without identifier.
 */
class TestResourceWithoutIdentifier
{
    /** @var string|null */
    private $name;

    /** @var string|null */
    private $description;

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
     * @return null|string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
}
