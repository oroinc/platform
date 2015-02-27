Transition Actions
=======================

Table of Contents
-----------------
 - [Send Email Action](#send-email-action)
 - [Send Email Template Action](#send-email-template-action)


Send Email Action
-----------------

**Class:** Oro\Bundle\EmailBundle\Workflow\Action\SendEmail

**Alias:** send_email

**Description:** Send email to recipient with defined subject and body

**Parameters:**
  - class - class name of created object;
  - attribute - attribute that will contain entity instance;
  - from - email address in From field (required);
  - to - email address in To field (required);
  - subject - email template name (required);
  - body - entity parameter (required);

**Configuration Example**
```
- @send_email:
    attribute: $attr
    from: 'email@address.com'
    to: 'email@address.com'
    subject: 'Subject'
    body: 'Body'

```

Send Email Template Action
--------------------------

**Class:** Oro\Bundle\EmailBundle\Workflow\Action\SendEmailTemplate

**Alias:** send_email_template

**Description:** Send email to choose recipient with defined template

**Parameters:**
  - class - class name of created object;
  - attribute - attribute that will contain entity instance;
  - from - email address in From field (required);
  - to - email address in To field (required);
  - template - email template name (required);
  - entity - entity parameter (required);

**Configuration Example**
```
- @send_email_template:
    attribute: $attr
    from: 'email@address.com'
    to: 'email@address.com'
    template: 'template_name'
    entity: $entity

```
