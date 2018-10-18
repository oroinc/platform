<?php

namespace Oro\Bundle\EmailBundle\Model;

/**
 * Model to specify criteria for loading email template from database.
 */
class EmailTemplateCriteria
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $entityName;

    /**
     * @param string $name
     * @param string|null $entityName
     */
    public function __construct(string $name, string $entityName = null)
    {
        $this->name = $name;
        $this->entityName = $entityName;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getEntityName(): ?string
    {
        return $this->entityName;
    }
}
