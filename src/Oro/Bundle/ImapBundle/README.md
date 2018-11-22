# OroImapBundle

OroImapBundle enables the data synchronization between local user mailboxes provided by OroEmailBundle and remote email servers using the capabilities of the IMAP protocol.

## Usage

``` php
<?php
    // Preparing connection config
    $imapConfig = new ImapConfig('imap.gmail.com', 993, 'ssl', 'user', 'pwd');

    // Accessing IMAP connector factory
    /** @var $factory \Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory */
    $factory = $this->get('oro_imap.connector.factory');

    // Creating IMAP connector for the ORO user
    /** @var $imap \Oro\Bundle\ImapBundle\Connector\ImapConnector */
    $imapConnector = $factory->createImapConnector($imapConfig);

    // Creating IMAP manager
    $imapManager = new ImapEmailManager($imapConnector);

    // Creating the search query builder
    /** @var $queryBuilder \Oro\Bundle\ImapBundle\Connector\Search\SearchQueryBuilder */
    $queryBuilder = $imapManager->getSearchQueryBuilder();

    // Building a search query
    $query = $queryBuilder
        ->from('test@test.com')
        ->subject('notification')
        ->get();

    // Request an IMAP server for find emails
    $imapManager->selectFolder('INBOX');
    $emails = $imapManager->findItems($query);
    
    // Creating IMAP folder manager
    $imapFolderManager = new ImapEmailFolderManager($imapConnector);
    
    // Getting IMAP folders 
    $folders = $imapFolderManager->getFolders(null, true);
```

## Synchronization with IMAP servers

To synchronize personal emails using Oro application, users need to configure their own IMAP mailbox on the user details page. The required information is the host, port, security type and credentials for the IMAP integration.

During the synchronization, Oro application loads emails from the user's inbox and outbox folders using the following algorithm:

 - If a user's mailbox is synchronized for the first time, Oro application loads emails sent and received last year only.
 - Only emails in the folders that are enabled for synchronization in the User Configuration settings are synchronized. To check the settings,select **My user** in the user menu, click **Edit** on the user details page, and navigate to the Email Synchronization Settings section.
 - When an empty folder is deleted on the email server, during the synchronization via IMAP it gets deleted in OroCRM. Folders with the existing emails that have already been synchronized remain intact and are kept by OroCRM.
 - When the synchronization settings change, folders are synchronized automatically, but not the emails.

By default the synchronization is executed by a CRON job every 30 minutes. Outside that schedule, launch synchronization manually using the following command:

```bash
php bin/console oro:cron:imap-sync
```

Email synchronization functionality is implemented in the following classes:

 - ImapEmailSynchronizer - extends OroEmailBundle\Sync\AbstractEmailSynchronizer class to work with IMAP mailboxes.
 - ImapEmailSynchronizationProcessor - implements email synchronization algorithm used for synchronize emails through IMAP.
 - EmailSyncCommand - allows to execute email synchronization as a CRON job or through the command line.

When during the synchronization, the mailbox IMAP connection settings become invalid for any reason, the mailbox owner is notified using the following channels:

 - After a successful login to the Oro application, the mailbox owner receives a notification via a flash message.
 - If the clank server is turned on, the user receives messages about the issue.
 - Oro application sends an email to owner's email address.

For the system mailboxes that have no owner, there is an `oro_imap_sync_origin_credential_notifications` capability. Users of any role with this
capability enabled, are notified using the channels mentioned above.
