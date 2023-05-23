<?php

namespace Oro\Bundle\TranslationBundle\Api\Model;

/**
 * Represents a translation domain.
 */
class TranslationDomain
{
    private string $name;
    private ?string $description;

    public function __construct(string $name, ?string $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
