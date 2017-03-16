<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\Stub;

use Doctrine\Common\Collections\Collection;

class DummyEntity
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var mixed */
    private $fieldOne;

    /** @var DummyEntity */
    private $relationEntity;

    /** @var Collection */
    private $relationEntityCollection;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return DummyEntity
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return DummyEntity
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFieldOne()
    {
        return $this->fieldOne;
    }

    /**
     * @param mixed $fieldOne
     *
     * @return DummyEntity
     */
    public function setFieldOne($fieldOne)
    {
        $this->fieldOne = $fieldOne;

        return $this;
    }

    /**
     * @return DummyEntity
     */
    public function getRelationEntity()
    {
        return $this->relationEntity;
    }

    /**
     * @param DummyEntity $relationEntity
     *
     * @return DummyEntity
     */
    public function setRelationEntity(DummyEntity $relationEntity)
    {
        $this->relationEntity = $relationEntity;

        return $this;
    }

    /**
     * @return Collection
     */
    public function getRelationEntityCollection()
    {
        return $this->relationEntityCollection;
    }

    /**
     * @param Collection $relationEntityCollection
     *
     * @return DummyEntity
     */
    public function setRelationEntityCollection(Collection $relationEntityCollection)
    {
        $this->relationEntityCollection = $relationEntityCollection;

        return $this;
    }
}
