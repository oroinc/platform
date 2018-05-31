<?php

namespace Oro\Bundle\ApiBundle\Collection;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Symfony\Component\Form\FormInterface;

class IncludedEntityData
{
    /** @var string */
    private $path;

    /** @var int */
    private $index;

    /** @var bool */
    private $existing;

    /** @var array|null */
    private $normalizedData;

    /** @var EntityMetadata|null */
    private $metadata;

    /** @var FormInterface|null */
    private $form;

    /**
     * @param string $path     A path to the entity in the request data
     * @param int    $index    An index of the entity in the included data
     * @param bool   $existing TRUE if an existing entity should be updated;
     *                         FALSE if a new entity should be created
     */
    public function __construct($path, $index, $existing = false)
    {
        $this->path = $path;
        $this->index = $index;
        $this->existing = $existing;
    }

    /**
     * Gets a path to the entity in the request data.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Gets an index of the entity in the included data.
     *
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Gets a value indicates whether an existing entity should be updated or new one should be created.
     *
     * @return bool
     */
    public function isExisting()
    {
        return $this->existing;
    }

    /**
     * Gets a normalized representation of the entity.
     *
     * @return array|null
     */
    public function getNormalizedData()
    {
        return $this->normalizedData;
    }

    /**
     * Sets a normalized representation of the entity.
     *
     * @param array|null $normalizedData
     */
    public function setNormalizedData($normalizedData)
    {
        $this->normalizedData = $normalizedData;
    }

    /**
     * Gets metadata of the entity.
     *
     * @return EntityMetadata|null
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Sets metadata of the entity.
     *
     * @param EntityMetadata|null $metadata
     */
    public function setMetadata(EntityMetadata $metadata = null)
    {
        $this->metadata = $metadata;
    }

    /**
     * Gets the form that is used to transform entity data.
     *
     * @return FormInterface|null
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Sets the form that is used to transform entity data.
     *
     * @param FormInterface|null $form
     */
    public function setForm(FormInterface $form = null)
    {
        $this->form = $form;
    }
}
