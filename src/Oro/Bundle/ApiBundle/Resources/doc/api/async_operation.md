# Oro\Bundle\ApiBundle\Entity\AsyncOperation

## ACTIONS

### get

Retrieve a specific asynchronous operation record.

{@inheritdoc}

## FIELDS

### entityType

The type of an entity for which the asynchronous operation was created.

### summary

{@inheritdoc}

This field will have data only when an asynchronous operation is finished successfully.

The summary can have the following properties:

- **aggregateTime** - The accumulated time, in milliseconds, taken by the system to accomplish the asynchronous operation.
- **readCount** - The number of items that have been successfully read.
- **writeCount** - The number of items that have been successfully written.
- **errorCount** - The number of errors occurred when processing the asynchronous operation.
- **createCount** - The number of items that have been successfully created.
- **updateCount** - The number of items that have been successfully updated.

## SUBRESOURCES

### errors

#### get_subresource

Retrieve errors occurred when processing a specific asynchronous operation.

# Oro\Bundle\ApiBundle\Batch\Model\BatchError

## FIELDS

### id

The unique identifier of a resource.

### status

The HTTP status code applicable to the problem.

### title

A short, human-readable summary that describes the problem type. 

### detail

A human-readable explanation specific to this occurrence of the problem.

### source

An object that contains references to the source of the error.

This object can have the following properties:

- **pointer** - A [JSON Pointer](https://tools.ietf.org/html/rfc6901) to the value in the request document that caused the error, e.g., "/data/0" for a primary data object, or "/data/0/attributes/title" for a specific attribute.
- **propertyPath** - A path to the value in the request document that caused the error. This property is returned if the **pointer** property cannot be computed.
