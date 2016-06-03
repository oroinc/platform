OroNotificationBundle
=====================

Email notifications for system entity triggers (like entity update, delete, create).


Console commands
-------------

oro:maintenance-notification
-------------

Command to send maintenance notification emails.

Parameters are:

- `message` - message to insert into email body. Optional.
- `file` - path to the text file with message. Optional.
- `subject` - email subject. Optional. If not provided, email subject from default maintenance template is used.
- `sender_name` - sender name. Optional. If not provided, sender name from Notification Rules configuration is used.
- `sender_email` - sender email. Optional. If not provided, sender email from Notification Rules configuration is used.

To send notifications on production servers --env=prod option should be added to use production email settings.
