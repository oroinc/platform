<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Util\Stub;

class Entity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var mixed
     */
    private $mockField;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMockField()
    {
        return $this->mockField;
    }

    /**
     * @param mixed $mockField
     * @return $this
     */
    public function setMockField($mockField)
    {
        $this->mockField = $mockField;

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultMockField()
    {
        return 'defaultMock';
    }
}
