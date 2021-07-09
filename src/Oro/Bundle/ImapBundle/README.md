# OroImapBundle

OroImapBundle enables the data synchronization between local user mailboxes provided by OroEmailBundle and remote email servers using the capabilities of the IMAP protocol.

## Usage

```php
<?php
    // Preparing connection config
    $imapConfig = new ImapConfig('imap.gmail.com', 993, 'ssl', 'user', 'pwd');

    // Accessing IMAP connector factory
    /** @var ImapConnectorFactory $factory */
    $factory = $this->get('oro_imap.connector.factory');

    // Creating IMAP connector for the ORO user
    $imapConnector = $factory->createImapConnector($imapConfig);

    // Creating IMAP manager
    $imapManager = new ImapEmailManager($imapConnector);

    // Creating the search query builder
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


## OAuth providers for mailboxes

`OroImapBundle` out-of-the-box, provides two OAuth based Email origin types:

### Gmail

Google Gmail implementation - provides OAuth authentication/authorization via custom Google application 
- integration configuration via *System Configuration -> Integrations -> Google Settings*
- Required fields: `Client ID`, `Client Secret` - values can be found in the Google application management panel
- Select *OAuth 2.0 for Gmail emails sync -> Enable* - If credentials are invalid, the integration *will not enable*.

### Microsoft 365

Microsoft 365 implementation - provides OAuth authentication/authorization via custom Microsoft Azure application 
- integration configuration via *System Configuration -> Integrations -> Microsoft Settings*
- Required fields: `Client ID`, `Client Secret`, `Tenant` - values can be found in the MS Azure application management panel.
- Select *Enable Emails Sync* in *Microsoft 365 Integrations*.


### Custom provider implementation

- Implement new OAuth provider class that inherits from `Oro\Bundle\ImapBundle\Provider\OAuthProviderInterface`
- Implement new OAuth manager class that inherits from `Oro\Bundle\ImapBundle\Manager\OAuthManagerInterface`
- Tag the manager implementation with tag `oro_imap.oauth_manager` (the service will be automatically picked up and, 
if provider enabled, additional account type will be available for *User Configuration -> General Setup -> 
Email Configuration -> Email synchronization settings -> Account Type*)
- Implement form type with default Email Origin values for certain provider 
(see `Oro\Bundle\ImapBundle\Form\Type\AbstractOAuthAwareConfigurationType` 
and existing inheriting types)
- Register a route for `Oro\Bundle\ImapBundle\Controller\CheckConnectionController` for new OAuth vendor
- Implement custom controller for handling access token
 (see `Oro\Bundle\ImapBundle\Controller\AbstractAccessTokenController` and 
 inheriting controllers) and register a route for it.
- Register custom form block widgets definitions 

  1. `Resources/config/oro/twig.yml` - add this file to register global set of
  definitions of form fields

  ```yaml
  bundles:
      - ExampleVendorImapBundle:Form:fields.html.twig
  ```

  2. Create fields definitions file with custom definition of previously defined form field

  ```twig
  {# ExampleVendorImapBundle:Form:fields.html.twig #}
  
  {% block example_imap_configuration_type_widget %}
      {% set data = form.parent.parent.vars.value %}
  
      {% set options = form.vars.options|default({})|merge({
          {# component options #} 
      }) %}
  
      <div class="example-imap-gmail-container"
           data-page-component-module="examplevendorimap/js/app/components/imap-component"
           data-page-component-options="{{ options|json_encode }}"
      >
          <div {{ block('widget_container_attributes') }}>
              {# Custom form layout #}  
              {{- form_rest(form) -}}
          </div>
      </div>
  {% endblock %}
  ```
 
 - Implement JavaScript components:
 
   1. Popup for OAuth initialization (extend the `/Resources/public/js/app/components/imap-component.js`)
   2. Create view managed by the component (extend the `/Resources/public/js/app/views/imap-view.js`)
   3. Depending on OAuth implementation from your provider, claim token data via previously defined
   controller
   4. By default, the component/view handle population of proper DOM elements with provided token data
