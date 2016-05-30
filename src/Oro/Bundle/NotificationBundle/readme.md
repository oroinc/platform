OroNotificationBundle
=====================

Email notifications for system entity triggers (like entity update, delete, create).


Console commands
-------------

oro:mass_notification:send
-------------

Command to send mass notification emails to all active users.

Params are:

- `message` - message to insert into email body.
- `subject` - email subject. Optional. If not provided, email subject from configuration is used.
- `sender_name` - sender name. Optional. If not provided, sender name from configuration is used.
- `sender_email` - sender email. Optional. If not provided, sender email from configuration is used.
