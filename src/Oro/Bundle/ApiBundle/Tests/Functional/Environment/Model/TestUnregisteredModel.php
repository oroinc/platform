<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Model;

/**
 * This model is used to test subresources to a model that is not registered in Data API.
 */
class TestUnregisteredModel
{
    /** @var mixed */
    private $id;

    /** @var string */
    private $class;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass(string $class): void
    {
        $this->class = $class;
    }
}
