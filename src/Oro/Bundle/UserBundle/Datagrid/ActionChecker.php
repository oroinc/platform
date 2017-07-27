<?php

namespace Oro\Bundle\UserBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class ActionChecker
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
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
