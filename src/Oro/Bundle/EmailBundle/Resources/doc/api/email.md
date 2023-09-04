# Oro\Bundle\EmailBundle\Entity\Email

## ACTIONS

### get

Retrieve a specific email record.

### get_list

Retrieve a collection of email records.

### create

Create a new email record.

The created record is returned in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "emails",
    "attributes": {
      "subject": "Some message",
      "from": {
        "name": "John Doo",
        "email": "john.doo@example.com"
      },
      "toRecipients": [
        {
          "name": "Amanda Doo",
          "email": "amanda.doo@example.com"
        }
      ],
      "messageId": "<649a02cdcf03c_4f8d02088fd@test.mail>",
      "sentAt": "2023-02-16T13:36:37Z",
      "internalDate": "2023-02-16T13:36:35Z",
      "body": {
        "content": "Lorem ipsum dolor sit amet, consectetuer adipiscing elit",
        "type": "text"
      }
    },
    "relationships": {
      "emailUsers": {
        "data": [
          {
            "type": "emailusers",
            "id": "email_user_1"
          }
        ]
      },
      "emailAttachments": {
        "data": [
          {
            "type": "emailattachments",
            "id": "email_attachment_1"
          }
        ]
      }
    }
  },
  "included": [
    {
      "type": "emailusers",
      "id": "email_user_1",
      "attributes": {
        "receivedAt": "2023-02-16T13:36:41Z",
        "folders": [
          {
            "type": "sent",
            "name": "Sent",
            "path": "Sent"
          }
        ]
      }
    },
    {
      "type": "emailattachments",
      "id": "email_attachment_1",
      "attributes": {
        "fileName": "test.jpg",
        "contentType": "image/jpeg",
        "contentEncoding": "base64",
        "content": "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAABHNCSVQICAgIfAhkiAAAAAtJREFUCJlj+A8EAAn7A/3jVfKcAAAAAElFTkSuQmCC"
      }
    }
  ]
}
```
{@/request}

### update

Edit a specific email record.

The updated record is returned in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "emails",
    "id": "1",
    "attributes": {
      "body": {
        "content": "Lorem ipsum dolor sit amet, consectetuer adipiscing elit",
        "type": "text"
      }
    }
  }
}
```
{@/request}

## FIELDS

### from

An object represents an email address from which the email is sent.

The object has two properties, **name** and **email**.

The **name** property is a string contains the display name of the person or entity.

The **email** property is a string contains the email address of the person or entity.

Example of data: **{"name": "John Doo", "email": "john.doo@example.com"}**

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### toRecipients

An array of the **to** recipients for the email.

Each element of the array is an object with the following properties:

**name** property is a string contains the display name of the person or entity.

**email** property is a string contains the email address of the person or entity.

Example of data: **\[{"name": "John Doo", "email": "john.doo@example.com"}\]**

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### ccRecipients

An array of the **cc** recipients for the email.

Each element of the array is an object with the following properties:

**name** property is a string contains the display name of the person or entity.

**email** property is a string contains the email address of the person or entity.

Example of data: **\[{"name": "John Doo", "email": "john.doo@example.com"}\]**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### bccRecipients

An array of the **bcc** recipients for the email.

Each element of the array is an object with the following properties:

**name** property is a string contains the display name of the person or entity.

**email** property is a string contains the email address of the person or entity.

Example of data: **\[{"name": "John Doo", "email": "john.doo@example.com"}\]**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### subject

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### importance

{@inheritdoc}. Possible values: `low`, `normal` and `high`.

#### create

{@inheritdoc}

**Note:**
The default value is `normal`.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### messageId

The message ID in the format specified by **RFC2822**.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### messageIds

The list of message IDs if the email has several values in **Message-ID** header of the email message.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### sentAt

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### internalDate

{@inheritdoc}. It is the value of the **InternalDate** header of the email message.

#### create

{@inheritdoc}

**The required field.**

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### head

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### acceptLanguage

The value of the **Accept-Language** header of the email message.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### xMessageId

The value of the **X-GM-MSG-ID** header of the email message.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### xThreadId

The value of the **X-GM-THR-ID** header of the email message.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### references

The list of message IDs of all the replies in the thread.

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### body

An object represents the email body.

The object has two properties, **type** and **content**.

The **type** property is a string contains the type of the email body content. Possible values: `text` and `html`.

The **content** property is a string contains the email body content.

Example of data: **{"type": "text", "content": "Hello"}**

#### update

{@inheritdoc}

**The body can be set only if it was not set yet. The updating of existing body is not allowed.**

### shortTextBody

A short text representation of the email body content.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### bodySynced

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### hasEmailAttachments

Indicates whether the email has attachments.

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### emailAttachments

The attachments for the email.

### emailUsers

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

## SUBRESOURCES

### emailAttachments

#### get_subresource

Retrieve the email attachment records related to a specific email record.

#### get_relationship

Retrieve the IDs of the email attachment records related to a specific email record.

### emailUsers

#### get_subresource

Retrieve the email user records related to a specific email record.

#### get_relationship

Retrieve the IDs of the email user records related to a specific email record.
