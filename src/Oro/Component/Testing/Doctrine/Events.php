<?php

namespace Oro\Component\Testing\Doctrine;

/**
 * Defines event constants for Doctrine-related testing scenarios.
 *
 * This class provides event names that are triggered during functional test
 * execution, particularly for handling transaction rollback scenarios and
 * cleanup of test fixtures.
 */
class Events
{
    /**
     * This event is triggered, when the transaction, which provides functional test isolation, is rolled back.
     * This event can be useful in case, when you need to rollback some changes, made by test fixture, in case this
     * changes affects not only the database (e.g. cache)
     */
    const ON_AFTER_TEST_TRANSACTION_ROLLBACK = 'onAfterTestTransactionRollback';
}
