<?php

namespace Oro\Bundle\ImapBundle\Form\Handler;

use Oro\Bundle\ConfigBundle\Config\ConfigChangeSet;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;

/**
 * Google OAuth related implementation of AbstractImapConfigHandler.
 * Handles refreshing tokens of email origin entities if Google API application
 * client ID/Secret was modified
 */
class GoogleImapConfigHandler extends AbstractImapConfigHandler
{
    /**
     * {@inheritDoc}
     */
    protected function getManagerType(): string
    {
        return AccountTypeModel::ACCOUNT_TYPE_GMAIL;
    }

    /**
     * {@inheritDoc}
     */
    protected function isForceRefreshRequired(ConfigChangeSet $changeSet): bool
    {
        return $changeSet->isChanged('oro_google_integration.client_id')
            || $changeSet->isChanged('oro_google_integration.client_secret');
    }
}
