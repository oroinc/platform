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
     * {@inheritdoc}
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup(): string
    {
        return $this->group;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}
