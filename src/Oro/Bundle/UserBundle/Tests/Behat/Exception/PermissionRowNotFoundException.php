<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Exception;

/**
 * Thrown when a permissions row for an entity is missing from the role view page.
 *
 * Carries the rows that were actually rendered so the failing step can report them next to the
 * locale diagnostics it collects itself.
 */
class PermissionRowNotFoundException extends \RuntimeException
{
    /**
     * @param string $entityName
     * @param array<int, string> $renderedRows
     */
    public function __construct(
        private readonly string $entityName,
        private readonly array $renderedRows
    ) {
        parent::__construct(sprintf(
            'Permissions row for entity "%s" was not found on the role view page. Rows rendered: %s',
            $entityName,
            $renderedRows ? implode(', ', $renderedRows) : 'none'
        ));
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @return array<int, string>
     */
    public function getRenderedRows(): array
    {
        return $this->renderedRows;
    }
}
