<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm;

/**
 * The aim of this class is provide an iterator which can be used for delete records.
 * This iterator is always iterates through the first page of a buffer. So, it allows you to
 * iterate through records to be deleted and delete them one by one.
 *
 * @deprecated BufferedIdentityQueryResultIterator fixes query result with ids. Iterating first page no longer needed
 */
class DeletionIterableResult extends IterableResult
{
}
