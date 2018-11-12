# Documenting API Resources

 - [Overview](.md#overview)
 - [Documentation File Format](#documentation-file-format)


## Overview

Documentation is an important part of API and helps developers to use your API. Therefore, it is necessary to provide detailed documentation for your API resources.

OroPlatform collects documentation for API resources from several sources:

* The documentation can be written in a [configuration file](./configuration.md).
* A [Markdown](https://daringfireball.net/projects/markdown/) document. The detailed information you can find bellow in this document.
* System-wide descriptions of [configurable entities and fields](./../../../EntityConfigBundle/README.md).

The source with the highest priority is the configuration file. The documentation provided there overrides all other sources. However, it is a YAML file and therefore, it is not fully suitable for big multi-line texts. The more appropriate place for the documentation is a separate [Markdown](https://daringfireball.net/projects/markdown/) file. To use a custom markdown file, provide a link to it in the configuration file, e.g.:

```yaml
api:
    entities:
        Acme\Bundle\AppBundle\Entity\AcmeEntity:
            documentation_resource: '@AcmeAppBundle/Resources/doc/api/acme_entity.md'
```

If the documentation was not found neither the configuration file nor in the Markdown documentation file, OroPlatform tries to use system-wide descriptions of entities and fields. These descriptions are usually provided in translation files. This is the best way to document fields because the descriptions can be used in other places, not only in API. Here is an example of a translation file that contains descriptions for entities and fields:

```yaml
# Acme/Bundle/AppBundle/Resources/translations/messages.en.yml
oro:
    sales:
        #
        # Opportunity entity
        #
        opportunity:
            entity_label:         Opportunity
            entity_plural_label:  Opportunities
            entity_description:   The Opportunity represents highly probable potential or actual sales to a new or established customer
            id.label:             Id
            name:
                label:            Opportunity name
                description:      The name used to refer to the opportunity in the system.
            close_date:
                label:            Expected close date
                description:      The expected close date for open opportunity, and actual close date for the closed one
            probability:
                label:            Probability
                description:      The perceived probability of opportunity being successfully closed
```

**Important:** Please note that after changing a documentation you need to run the `oro:api:doc:cache:clear` CLI command to apply the changes to the API sandbox.

## Documentation File Format

The documentation file is a [Markdown](https://daringfireball.net/projects/markdown/) document that contains description of one or multiple API resources. Please note that the [Markdown Extra](https://michelf.ca/projects/php-markdown/extra/) syntax is supported in the documentation file as well.

The only requirement for such document is that it should be written in a particular format.

Each resource documentation should start with the `#` (h1) header that contains Fully-Qualified Class Name (FQCN) of the resource, e.g.:

```markdown
# Acme\Bundle\AcmeBundle\Entity\AcmeEntity
```

As already mentioned above, a single documentation file may contain documentation for several resources. In general, such approach is used to document the main resource and related resources. For example, you can document resources for the User and UseStatus entities in the same file.

Start the next level with the `##` (h2) header and use it to announce one of the documentation sections, e.g.:

```markdown
# Acme\Bundle\AcmeBundle\Entity\AcmeEntity

## ACTIONS
...
## FIELDS
...
## FILTERS
...
## SUBRESOURCES
...
```

The section name is case insensitive. They are used only by the documentation parser to identify the documentation part.  
The following table describes the purposes of each documentation section:

| Section name | Description |
| --- | --- |
| ACTIONS | Contains actions documentation. |
| FIELDS | Contains fields descriptions. |
| FILTERS | Contains filters description. |
| SUBRESOURCES | Contains a documentation for sub-resources. |

The third level `###` (h3) header depends on the section type and can be an action name, field name, filter name, or sub-resource name.

The fourth level `####` (h4) header can be used only for the **FIELDS** and **SUBRESOURCES** sections.
Use it for the **FIELDS** section when you need to specify a description for a field in a particular action. 
Use it for the **SUBRESOURCES** section when you need to specify a sub-resource action name.

The action names in **FIELDS** section can be combined using comma, e.g. "create, update". It helps avoid copy-pasting when you need the same description for several actions.

The description of filters should be a plain text, the markdown markup language is not supported there.

**Example:**

```markdown
# Acme\Bundle\AcmeBundle\Entity\AcmeEntity

## ACTIONS

### get

The documentation for an action. In this example, it is the "get" action.
It may contain any formatting like ordered or unordered lists, request or response examples, links, text in bold or italic, etc.

## FIELDS

### name

The description for the "name" field.
May contain any formatting like ordered or unordered lists, request or response examples, links, text in bold or italic, etc.

#### get

The description for the "name" field of the "get" action.
May contain any formatting like ordered or unordered lists, request or response examples, links, text in bold or italic, etc.

#### create, update

The description for the "name" field of the "create" and "update" actions.
May contain any formatting like ordered or unordered lists, request or response examples, links, text in bold or italic, etc.

## FILTERS

### name

The description for a filter by the "name" field.
The formatting is not allowed here.

## SUBRESOURCES

### contacts

#### get_subresource

The documentation for a sub-resource action. It is a "get_subresource" action for "contacts" sub-resource in this example.
May contain any formatting e.g.: ordered or unordered lists, request or response examples, links, text in bold or italic, etc.
```

Use the `{@inheritdoc}` placeholder to get the common documentation for an action, a field or a filter. This placeholder works only for the **ACTIONS**, **FIELDS** and **FILTERS** sections.

**Example:**

```markdown
# Acme\Bundle\AcmeBundle\Entity\AcmeEntity

## ACTIONS

### create

Create a new Acme entity record.

The created record is returned in the response.

{@inheritdoc}

## FIELDS

### name

#### create

{@inheritdoc}

**The required field**
```

Use the `{@request}` tag to add documentation depending on the request type. The full signature of this tag is `{@request:expression}some text{@/request}`. The expression can contain the following operators:

- `&` - logical AND
- `|` - logical OR
- `!` - logical NOT

For example, to add a text for the JSON API request type for all requests excluding REST API, use the following expression: `json_api&!rest`.

**Example:**

```markdown
# Acme\Bundle\AcmeBundle\Entity\AcmeEntity

## ACTIONS

### create

Create a new Acme entity record.

The created record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

` ` `JSON
{
    "data": {
       "type": "entities",
       "attributes": {
          "name": "Test Entity"
       }
    }
}
` ` `
{@/request}

## FIELDS

### name

#### create

{@inheritdoc}

**The required field**
```
