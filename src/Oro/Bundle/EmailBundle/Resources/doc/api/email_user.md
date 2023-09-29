# Oro\Bundle\EmailBundle\Entity\EmailUser

## ACTIONS

### get

Retrieve a specific email user record.

### get_list

Retrieve a collection of email user records.

### create

Create a new email user record.

The created record is returned in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "emailusers",
    "attributes": {
      "receivedAt": "2023-02-16T13:36:41Z",
      "seen": false,
      "folders": [
        {
          "type": "sent",
          "name": "Sent",
          "path": "Sent"
        }
      ]
    },
    "relationships": {
      "email": {
        "data": {
          "type": "emails",
          "id": "1"
        }
      },
      "owner": {
        "data": {
          "type": "users",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

### update

Edit a specific email user record.

The updated record is returned in the response.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "emailusers",
    "id": "1",
    "attributes": {
      "seen": true
    }
  }
}
```
{@/request}

### delete

Delete a specific email user record.

### delete_list

Delete a collection of email user records.

## FIELDS

### folders

An array of folders the email is located for a user.

Each element of the array is an object with the following properties:

**type** property is a string contains the type of the folder. Possible values: `inbox`, `sent`, `trash`, `drafts`, `spam`, `other`.

**name** property is a string contains the name of the folder.

**path** property is a string contains the path to the folder, e.g. "Inbox" or "Oro/News".

Example of data: **\[{"type": "other", "name": "News", "path": "Oro/News"}\]**

### private

Indicates whether the email is [public or private](https://doc.oroinc.com/bundles/platform/EmailBundle/public-private-emails/#public-and-private-emails).

#### create, update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### email

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### owner

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**

### organization

#### update

{@inheritdoc}

**The read-only field. A passed value will be ignored.**
