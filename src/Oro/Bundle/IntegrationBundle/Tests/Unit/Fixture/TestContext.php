<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TestContext implements ContextInterface
{
    /**
     * @var array
     */
    private $postponedRows = [];

    #[\Override]
    public function addError($message)
    {
    }

    #[\Override]
    public function addErrors(array $messages)
    {
    }

    #[\Override]
    public function getErrors()
    {
        return [];
    }

    #[\Override]
    public function addPostponedRow(array $row)
    {
        $this->postponedRows[] = $row;

        return $this;
    }

    #[\Override]
    public function addPostponedRows(array $rows)
    {
        foreach ($rows as $row) {
            $this->addPostponedRow($row);
        }

        return $this;
    }

    #[\Override]
    public function getPostponedRows()
    {
        return $this->postponedRows;
    }

    #[\Override]
    public function getFailureExceptions()
    {
    }

    #[\Override]
    public function incrementReadCount($incrementBy = 1)
    {
    }

    #[\Override]
    public function getReadCount()
    {
        return 1;
    }

    #[\Override]
    public function incrementReadOffset()
    {
    }

    #[\Override]
    public function getReadOffset()
    {
    }

    #[\Override]
    public function incrementAddCount($incrementBy = 1)
    {
    }

    #[\Override]
    public function getAddCount()
    {
        return 1;
    }

    #[\Override]
    public function incrementUpdateCount($incrementBy = 1)
    {
    }

    #[\Override]
    public function getUpdateCount()
    {
        return 0;
    }

    #[\Override]
    public function incrementReplaceCount($incrementBy = 1)
    {
    }

    #[\Override]
    public function getReplaceCount()
    {
        return 0;
    }

    #[\Override]
    public function incrementDeleteCount($incrementBy = 1)
    {
    }

    #[\Override]
    public function getDeleteCount()
    {
        return 0;
    }

    #[\Override]
    public function incrementErrorEntriesCount($incrementBy = 1)
    {
    }

    #[\Override]
    public function getErrorEntriesCount()
    {
        return 0;
    }

    #[\Override]
    public function setValue($name, $value)
    {
    }

    #[\Override]
    public function getValue($name)
    {
    }

    #[\Override]
    public function getConfiguration()
    {
    }

    #[\Override]
    public function hasOption($name)
    {
    }

    #[\Override]
    public function getOption($name, $default = null)
    {
    }

    #[\Override]
    public function removeOption($name)
    {
    }
}
