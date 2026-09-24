<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Exception;

/**
 * The role view page does not show the permissions row of an entity.
 *
 * The exception keeps the rows that the page shows. The failed step can then report these rows.
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
