# Oro\Bundle\EmailBundle\Entity\EmailAttachment

## ACTIONS

### get

Retrieve a specific email attachment record.

### get_list

Retrieve a collection of email attachment records.

### create

Create a new email attachment record. It cannot be used independently to create attachments.
Attachments can be created together with an email via the email creation API resource.
Also attachments can be added to an email together with an email message body via the email update API resource
if the email does not have the body yet.

## FIELDS

### fileName

The name of the attachment file.

#### create

{@inheritdoc}

**The required field.**

### contentType

The MIME type.

#### create

{@inheritdoc}

**The required field.**

### contentEncoding

The content encoding. Possible values: `base64` or `quoted-printable`.

#### create

{@inheritdoc}

**The required field.**

### content

The content of the attachment encoded as specified in the **contentEncoding** field.

#### create

{@inheritdoc}

**The required field.**

### embeddedContentId

The identifier of an inline attachment.

### email

An email which the attachment belongs to.
