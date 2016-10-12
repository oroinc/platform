Documentation File Format
=========================

The documentation file is a regular [markdown](https://en.wikipedia.org/wiki/Markdown) document that contains description about one or multiple API resources.

The only requirement for such document is it should be written in particular format.

Each resource documentation should starts from '#' (h1) header that contains Fully-Qualified Class Name (FQCN) of the resource, e.g.:

```markdown
# Acme\Bundle\AcmeBundle\Entity\AcmeEntity
```

As already mentioned above, a single documentation file may contain documentations for several resource. In general, this can be used to document a main resource and related resources. For example, you can document resources for User and UserGroup entities in one file.

At the next level `##` (h2) one of the documentation sections should be specified, e.g.:

```markdown
# Acme\Bundle\AcmeBundle\Entity\AcmeEntity

## ACTIONS
...
## FIELDS
...
## FILTERS
```

The letters case of the section name is no matter. They are used only by documentation parser to identify the documentation part.  
The following table describes the purposes of each documentation section:

| Section name | Description | Example |
| --- | --- | --- |
| ACTIONS | Followed by action name and contains a description for particular action. | Get, Get_list, Create, Update, etc. |
| FIELDS | Followed by field name and contains a description for particular field. At the same time it is possible to specify the description for field and for particular action as well. | id, name, createdAt, updatedAt, etc. |
| FILTERS | Followed by field name and contains a description for particular field, but in case it will be used for filtering purposes. | id, name, createdAt, updatedAt, etc. |

The third level `###` (h3) is field name or filter field name:

```markdown
# Acme\Bundle\AcmeBundle\Entity\AcmeEntity

## ACTIONS

### Get

Description for GET action. May contain any formatting e.g.: ordered or unordered lists, request or response examples, links, text in bold or italic, etc.

### Create

Description for CREATE action

## FIELDS

### id

Description for ID field

### name

Description for NAME field

## FILTERS

### id

Description for ID filter

### name

Description for NAME filter

### createdAt

Description for CREATED AT filter
```

And for the case then it is needed to specify the description for field and for particular action, the fourth level `####` (h4) header should be added, so the formatting will looks like this:

```markdown
# Acme\Bundle\AcmeBundle\Entity\AcmeEntity

## FIELDS

### name

Regular description for NAME field

#### Get

Description for NAME field and for GET action
```


Please note, the `FILTERS` section do not supports the 4th level (per action type) of description.
