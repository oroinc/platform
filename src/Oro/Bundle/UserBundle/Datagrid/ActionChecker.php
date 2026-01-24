<?php

namespace Oro\Bundle\UserBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Checks which actions are allowed for a user record in the datagrid.
 *
 * This utility class determines which actions (such as delete) should be
 * available for a given user record based on the current authenticated user.
 * For example, it prevents users from deleting their own account.
 */
class ActionChecker
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function checkActions(ResultRecordInterface $record)
    {
        return ($this->tokenAccessor->getUserId() === $record->getValue('id'))
            ? ['delete' => false]
            : [];
    }
}
