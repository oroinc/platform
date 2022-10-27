<?php

namespace Oro\Bundle\ImapBundle\Form\Handler;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;

/**
 * Microsoft Office 365 OAuth related implementation of AbstractImapConfigHandler.
 * Handles refreshing tokens of email origin entities if Microsoft Azure API application
 * client ID/Secret/Tenant was modified
 */
class MicrosoftImapConfigHandler extends AbstractImapConfigHandler
{
    /**
     * {@inheritDoc}
     */
    protected function getManagerType(): string
    {
        return AccountTypeModel::ACCOUNT_TYPE_MICROSOFT;
    }

    /**
     * {@inheritDoc}
     */
    protected function isForceRefreshRequired(ConfigChangeSet $changeSet): bool
    {
        return $changeSet->isChanged('oro_microsoft_integration.client_id')
            || $changeSet->isChanged('oro_microsoft_integration.client_secret')
            || $changeSet->isChanged('oro_microsoft_integration.tenant');
    }
}
