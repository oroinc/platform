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


## OAuth providers for mailboxes

`OroImapBundle` out-of-the-box, provides two OAuth based Email origin types:

### Gmail

Google Gmail implementation - provides OAuth authentication/authorization via custom Google application 
- integration configuration via *System Configuration -> Integrations -> Google Settings*
- Required fields: `Client ID`, `Client Secret` - values can be found in the Google application management panel
- Select *OAuth 2.0 for Gmail emails sync -> Enable* - If credentials are invalid, the integration *will not enable*.

### Microsoft Office 365

MS Office implementation - provides OAuth authentication/authorization via custom Microsoft Azure application 
- integration configuration via *System Configuration -> Integrations -> Microsoft Settings*
- Required fields: `Client ID`, `Client Secret`, `Tenant` - values can be found in the MS Azure application management panel.
- Select *OAuth 2.0 for Office 365 emails sync -> Enable* - If credentials are invalid, the integration *will not enable*.


### Custom provider implementation

- Implement new provider class that inherits from `Oro\Bundle\ImapBundle\Manager\Oauth2ManagerInterface`
- Tag the implementation with tag `oro_imap.oauth2_manager` (the service will be automatically picked up and, 
if provider enabled, additional account type will be available for *User Configuration -> General Setup -> 
Email Configuration -> Email synchronization settings -> Account Type*)
 1. Make sure all interface methods are implemented
 2. `getType()` method should return unique name of the provider you are implementing
 3. `getConnectionFormTypeClass()` should return custom configuration form class fully qualified class name

```
# services.yml

services:

    # ...

    example_vendor.example_bundle.imap_email_oauth2_manager:
        class: ExampleVendor\Bundle\ImapBundle\Manager\ImapEmailOauth2Manager
        arguments:
            # ...
        tags:
            - { name: 'oro_imap.oauth2_manager' }
```
- Implement form type with default Email Origin values for certain provider 
(see `Oro\Bundle\ImapBundle\Form\Type\AbstractOauthAwareConfigurationType` 
and existing inheriting types)
```
<?php 
namespace ExampleVendor\Bundle\ImapBundle\Form\Type;

use Oro\Bundle\ImapBundle\Form\Type\AbstractOauthAwareConfigurationType;

class ExampleConfigurationType extends AbstractOauthAwareConfigurationType 
{
    /* ... */

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'example_imap_configuration_type';
    }
}
```
 - Implement custom controller for handling access token and connection check endpoints
 (see `Oro\Bundle\ImapBundle\Controller\AbstractVendorConnectionController` and 
 inheriting controllers) - provide necessary endpoints. Out-of-the-box the abstraction implements
 two methods for providing access token and checking connection.
 
   - `Check connection endpoint` - endpoint for checking connection to the OAuth-secured service and provides 
 additional form data (folders)
 
   - `Get Token endpoint` - Endpoint for providing token data
   
   ```
   <?php

   namespace ExampleVendor\Bundle\ImapBundle\Controller;

   use Oro\Bundle\ImapBundle\Controller\AbstractVendorConnectionController;

   class ExampleController extends AbstractVendorConnectionController
   {
       /* ... */
   }
   ```

  Default Token data format:
  
  ```
  {
  	"access_token":"XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
  	"refresh_token":"XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
  	"expires_in":3599,
  	"email_address":"user@example.com"
  }
  ```

- Register custom form block widgets definitions 

  1. `Resources/config/oro/twig.yml` - add this file to register global set of
  definitions of form fields
  
  ```
  bundles:
      - ExampleVendorImapBundle:Form:fields.html.twig
  ```
  2. Create fields definitions file with custom definition of previously defined form field
  
  ```
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
 
 
 
