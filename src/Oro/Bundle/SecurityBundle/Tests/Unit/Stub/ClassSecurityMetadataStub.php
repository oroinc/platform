<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Stub;

use Oro\Bundle\SecurityBundle\Metadata\ClassSecurityMetadata;
use Oro\Bundle\SecurityBundle\Metadata\FieldSecurityMetadata;

class ClassSecurityMetadataStub implements ClassSecurityMetadata
{
    private string $className;

    private string $group;

    private string $label;

    private string $description;

    private string $category;

    /** @var FieldSecurityMetadata[] */
    private array $fields;

    public function __construct(
        string $className = '',
        string $group = '',
        string $label = '',
        string $description = '',
        string $category = '',
        array $fields = []
    ) {
        $this->className = $className;
        $this->group = $group;
        $this->label = $label;
        $this->description = $description;
        $this->category = $category;
        $this->fields = $fields;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * {@inheritDoc}
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
