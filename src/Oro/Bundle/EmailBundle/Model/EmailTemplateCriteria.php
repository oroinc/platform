<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Model to specify the criteria for loading an email template.
 */
class EmailTemplateCriteria
{
    private string $name;

    private ?string $entityName;

    public function __construct(string $name, string $entityName = null)
    {
        $this->name = $name;
        $this->entityName = $entityName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEntityName(): ?string
    {
        return $this->entityName;
    }

    public function setEntityName(?string $entityName): self
    {
        $this->entityName = $entityName;

        return $this;
    }
}
