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

    #[\Override]
    public function getClassName(): string
    {
        return $this->className;
    }

    #[\Override]
    public function getGroup(): string
    {
        return $this->group;
    }

    #[\Override]
    public function getLabel()
    {
        return $this->label;
    }

    #[\Override]
    public function getDescription()
    {
        return $this->description;
    }

    #[\Override]
    public function getCategory(): string
    {
        return $this->category;
    }

    #[\Override]
    public function getFields(): array
    {
        return $this->fields;
    }
}
