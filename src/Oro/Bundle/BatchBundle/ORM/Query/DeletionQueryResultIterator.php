<?php

namespace Oro\Bundle\BatchBundle\ORM\Query;

/**
 * Iterates results of Query for deletion queries, i.e. without first result shifting
 * @deprecated BufferedIdentityQueryResultIterator fixes dataset with ids, iterating always first page no longer needed
 */
class DeletionQueryResultIterator extends BufferedIdentityQueryResultIterator
{
}
