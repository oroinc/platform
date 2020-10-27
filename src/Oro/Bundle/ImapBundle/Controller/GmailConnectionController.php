<?php

namespace Oro\Bundle\ImapBundle\Controller;

use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides check-connection and access token endpoints for OAuth
 * authentication and related services. Integration for Google
 * application to support OAuth for Gmail IMAP/SMTP.
 *
 * @Route("/gmail")
 */
class GmailConnectionController extends AbstractVendorConnectionController
{
    /**
     * @Route("/connection/check", name="oro_imap_gmail_connection_check", methods={"POST"})
     * @CsrfProtection()
     */
    public function checkAction()
    {
        return $this->check($this->getRequestStack()->getCurrentRequest());
    }

    /**
     * @Route("/connection/access-token", name="oro_imap_gmail_access_token", methods={"POST"})
     */
    public function accessTokenAction()
    {
        return $this->handleAccessToken(
            $this->getRequestStack()->getCurrentRequest(),
            AccountTypeModel::ACCOUNT_TYPE_GMAIL
        );
    }
}
