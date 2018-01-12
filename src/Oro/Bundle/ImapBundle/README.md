OroImapBundle
=============

This bundle provides a functionality to work with email servers through IMAP protocol.

Dependencies
------------

"zendframework/zend-mail": "2.1.5"

Notes:
- We have to use version 2.1.5 due an issue with *binary* content transfer encoding in version 2.1.6.


Usage
-----

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

Synchronization with IMAP servers
---------------------------------
Each user who want to synchronize own emails with BAP need to configure own IMAP mailbox on the user details page. He/she just need to enter correct host, port, security type and credentials.
During the synchronization we load emails from user's inbox and outbox by the following algorithm:

 - If a user's mailbox is newer synchronized yet then we load emails for the last year only.
 - We load all emails for selected folders according to synchronization settings (User menu -> My user -> Edit -> Email synchronization settings tab).
 - If a folder is deleted on IMAP server, it will be deleted in OroCRM as well. Folders with existing emails that already have been synchronized will not be deleted in OroCRM.
 - After changing synchronization settings folders will be synchronized automatically (not emails).

By default the synchronization is executed by CRON every 30 minutes. Also you can execute it manually using the following command:
```bash
php app/console oro:cron:imap-sync
```

Email synchronization functionality is implemented in the following classes:

 - ImapEmailSynchronizer - extends OroEmailBundle\Sync\AbstractEmailSynchronizer class to work with IMAP mailboxes.
 - ImapEmailSynchronizationProcessor - implements email synchronization algorithm used for synchronize emails through IMAP.
 - EmailSyncCommand - allows to execute email synchronization as CRON job or through command line.

If during synchronization will be detected that conditions for email box not valid already, the owner of this email box will be notified about this:

 - after success login, the owner of the email box will receive flash message;
 - if the clank server is turned-on, user will receive messages about this issue;
 - an email to user's email address will be send.

For the system email boxes that has no owner, there is an `oro_imap_sync_origin_credential_notifications` capability. In case if role has access with this
capability, all the users with this role will receive similar messages as for the user's email box.
