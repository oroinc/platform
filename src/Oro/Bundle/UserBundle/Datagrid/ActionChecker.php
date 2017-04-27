<?php

namespace Oro\Bundle\UserBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ActionChecker
{
    /** @var  SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function checkActions(ResultRecordInterface $record)
    {
        return ($this->securityFacade->getLoggedUserId() === $record->getValue('id'))
            ? ['delete' => false]
            : [];
    }
}
