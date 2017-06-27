Actions
-------

**Class:** Oro\Bundle\NoteBundle\Action\CreateNoteAction

**Alias:** create_note

**Description:** Creates an activity note with for a target entity

**Parameters:**
 - message - property path to message body  
 - target_entity - property path where to instance of entity for adding note
 - attribute - (optional) target path where created Note entity will be saved

**Configuration Example**
```
- @create_note:
    message: $.comment
    target_entity: $.entity
    attribute: $.result.note
```
