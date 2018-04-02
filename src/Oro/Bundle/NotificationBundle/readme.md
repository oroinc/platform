# OroNotificationBundle

OroNotificationBundle extends the OroEmailBundle capabilities and enables the email notification feature in Oro applications. It provides the UI and CLI tool to send and manage email notifications.

## Console commands

`oro:maintenance-notification`

Command to send maintenance notification emails.

Parameters are:

- `message` - message to insert into email body. Optional.
- `file` - path to the text file with message. Optional.
- `subject` - email subject. Optional. If not provided, email subject from default maintenance template is used.
- `sender_name` - sender name. Optional. If not provided, sender name from Notification Rules configuration is used.
- `sender_email` - sender email. Optional. If not provided, sender email from Notification Rules configuration is used.

To send notifications on production servers --env=prod option should be added to use production email settings.

## Notification Rule creation

System > Emails > Notification Rules > Create Notification Rule

In order to create *Notification Rule* you should specify:
 - Entity Name
 - Event Name (Entity create, Entity remove, Entity update, Workflow transition)
 - Template
 
You can get List of predefined templates or create one from  System > Emails > Templates
 
 Also you should to choose at least one recipient from `Recipient list` group:
  - Users
  - Groups
  - Email
  - Additional Associations
  - Contact Emails 

`Additional Associations` and `Contact Emails` depends on selected `Entity Name`. 
`Contact Emails` allows to check recepients stored in entity fields marked as `Contact Information` > `Email`.
More info about configuring such fields [here](https://oroinc.com/doc/orocrm/current/admin-guide/entities/entity-field-properties#other).

**Please note:**
After rule was created, after firing specified in it events will be created jobs for consumer to submit emails chosen in `Recipient list` group.
Please check that consumer is running.
