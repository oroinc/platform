# System Mailboxes

## Function

System mailboxes represent email addresses which are globally accessible by
authorized users. These are configurable through system configuration. Contents
can be synchronized through IMAP and any replies can be sent using separately
configured SMTP.

Emails in mailboxes are visible in user inbox in CRM for user who are authorized
to view emails from this inbox. Emails can be filtered to show only one mailbox,
only users own mailbox or all emails available to him.

### Configuration

System Mailboxes can be created and configured under *System > Configuration > Email
Configuration > System Mailboxes*. When set up with IMAP Synchronization, mailbox
emails will synchronize using the same cron job as all other emails
(oro:cron:imap-sync). SMTP settings can be also specified for sending automatic
and manual replies for that mailbox.

### Email processing

Any email received by mailbox can be processed to entities in system. Different
types of email processing can be selected in mailbox configuration and custom
configuration is available for each type. Following process types are present
out of the box:

 - Convert to Case
 - Convert to Lead

Developers are able to add new process types. Both mentioned processes are
implemented in respective bundles.

### Autoresponse rules

Each mailbox can be configured with autoresponse rules. These rules will send
configured email templates as a response to incoming emails, given that
conditions for rules are matched.

## Implementation

### Entities
 - **Mailbox** - Represents individual mailboxes. This entity implements
**EmailOwnerInterface** so email addresses can be owned by it.
   - label (mailbox label)
   - email (mailbox email address)
   - origin (This is always UserEmailOrigin from ImapBundle)
   - processSettings (settings entity for email process)
   - authorizedUsers (list of users able to view mailbox emails)
   - authorizedRoles (list of roles able to view mailbox emails)
 - **MailboxProcessSettings** - Represents mailbox process settings. This is
an entity which should be extended to represent any added process and store
its settings (This entity is using single table inheritance).
   - id
   - type

### Mailbox Process

Mailbox processing is added using **processes** from OroWorkflowBundle.

Process can look like following example and will be triggered on
creation of EmailBody entity. This will guarantee that complete email
data (including body) is available for processing.

**Do NOT define triggers for this process. It will be triggered by event
listener on any new email assigned to mailbox.**

``` yaml
definitions:
    convert_mailbox_email_to_lead:
        label: 'Convert Mailbox E-mail to Lead'
        enabled: true
        entity: Oro\Bundle\EmailBundle\Entity\EmailBody
        order: 150
        actions_configuration:
            - @find_entity:
                class: Oro\Bundle\EmailBundle\Entity\Email
                attribute: $.email
                where:
                    emailBody: $id
            - @find_mailboxes:
                attribute: $.mailboxes
                process_type: 'lead'
                email: $.email
            - @tree:
                conditions:
                    @not_empty: [$.mailboxes]
                actions:
                    # Prepare data which do not depend on mailbox and mailbox process settings here
            - @traverse:
                array: $.mailboxes
                value: $.mailbox
                actions:
                    # Prepare mailbox dependent data and perform desired actions here.
```

### Additional actions

Some actions have been added to help creating mailbox processes. Mainly action
to help find mailboxes.

#### Find Mailboxes

This action searches for mailbox using provided email entity. It will set
attribute value to empty array if no mailbox is found. Multiple mailboxes are
found for example when email is addressed to multiple recipients and at least
two of them are mailboxes with processing of same type. Use *@traverse* action
to iterate over these mailboxes.

```yaml
@find_mailboxes:
    attribute: $.mailboxes      # Attribute that will contain result of action
    process_type: 'lead'        # Type of mailbox process (defined in mailbox process provider service definition)
    email: $.email              # Instance of Email entity which is being processed
```
#### Parse First and Last Name from email address

These actions are meant to be used if you need to parse names from email address
(for example when this address is not yet registered in system as Contact etc.).
This action will use name which is specified with address, and if no name is
provided, it will use address as first name and domain as last name.

```yaml
@parse_first_name_from_email_address:
    attribute: $.firstName              # Attribute that will contain result of action
    email_address: $.email.fromName     # Email address to be parsed

@parse_last_name_from_email_address:
    attribute: $.lastName               # Attribute that will contain result of action
    email_address: $.email.fromName     # Email address to be parsed
```

#### Add Email Activity Target

Adds newly generated entity as an activity target for email.

```yaml
@add_email_activity_target:
    email:              $.email
    target_entity:      $.leadEntity
    attribute:          $.added         #true if record was added (optional)
```

#### Strip HTML Tags

Removes content of invisible html tags and strips tags from provided html.
Leaves only visible text.

```yaml
@strip_html_tags:
    attribute: $.textOnly
    html: $.html
```

### Process Registration

Implement **MailboxProcessProviderInterface** and register it as a service with
tag **oro_email.mailbox_process**. Also provide type with this tag.

LeadMailboxProcessProvider as an example:

``` php
class LeadMailboxProcessProvider implements MailboxProcessProviderInterface
{
    /**
     * Returns fully qualified class name of settings entity for this process.
     *
     * @return string
     */
    public function getSettingsEntityFQCN()
    {
        return 'Oro\Bundle\SalesBundle\Entity\LeadMailboxProcessSettings';
    }

    /**
     * Returns form type used for settings entity used by this process.
     *
     * @return string
     */
    public function getSettingsFormType()
    {
        return 'oro_sales_lead_mailbox_process_settings';
    }

    /**
     * Returns id for translation which is used as label for this process type.
     *
     * @return string
     */
    public function getLabel()
    {
        return 'oro.sales.mailbox.process.lead.label';
    }
}

```

Example provider registration:

```yaml
oro_sales.provider.mailbox_process.lead:
    class: %oro_sales.provider.mailbox_process.lead.class%
    tags:
        - { name: oro_email.mailbox_process, type: lead }
```
