Documenting API Resources
=========================

Overview
--------

You need to provide detailed documentation for your API resources because it is an important part of API and it could help a lot to developers to use your API.

The Oro Platform collects documentation for API resources from several sources:

* The documentation can be written in a [configuration file](./configuration.md).
* A [markdown](https://en.wikipedia.org/wiki/Markdown) document. The detailed information you can find bellow in this document.
* System-wide descriptions of entities and fields.

The most priority source is the configuration file. The documentation provided there overrides all other sources. But as it is YAML file it is not the best way to write a big multi-line texts there. The more appropriate place for the documentation is a separate [markdown](https://en.wikipedia.org/wiki/Markdown) file. To use such file you need to provide a link to it in the configuration file, e.g.:

```yaml
api:
    entities:
        Acme\Bundle\AppBundle\Entity\AcmeEntity:
            documentation_resource: '@AcmeAppBundle/Resources/doc/api/acme_entity.md'
```

If the documentation was not found neither the configuration file nor the documentation file, the Oro Platform will try to use system-wide descriptions of entities and fields. These descriptions are usually provided in translation files and, actually, they are the best way to document fields, because these descriptions can be used not only in API. Here is an example of a translation file contains descriptions for entities and fields:

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
            entity_description:   The Opportunity represent highly probable potential or actual sales to a new or established customer
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

Please note that after changing a documentation you need to run `oro:api:doc:cache:clear` CLI command to apply the changes to API sandbox.

Documentation File Format
-------------------------

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
...
## SUBRESOURCES
...
```

The section name is case insensitive. They are used only by documentation parser to identify the documentation part.  
The following table describes the purposes of each documentation section:

| Section name | Description |
| --- | --- |
| ACTIONS | Contains a documentation of actions. |
| FIELDS | Contains a description of fields. |
| FILTERS | Contains a description of filters. |
| SUBRESOURCES | Contains a documentation of sub-resources. |

The third level `###` (h3) header depends on the section type and can be action name, field name, filter name or sub-resource name.

The fourth level `####` (h4) header can be used only for **FIELDS** and **SUBRESOURCES** sections.
For **FIELDS** section it can be used for the case when it is needed to specify the description for a field in a particular action. For **SUBRESOURCES** section it is a sub-resource action name.

The action names in **FIELDS** section can be combined using comma, e.g.: "Create, Update". It allows to avoid copy-paste when you need the same description for several actions.

An example:

```markdown
# Acme\Bundle\AcmeBundle\Entity\AcmeEntity

## ACTIONS

### get

The documentation for an action, in this example for "get" action.
May contain any formatting e.g.: ordered or unordered lists,
 request or response examples, links, text in bold or italic, etc.

## FIELDS

### name

The description for "name" field.
May contain any formatting e.g.: ordered or unordered lists,
 request or response examples, links, text in bold or italic, etc.

#### get

The description for "name" field for "get" action.
May contain any formatting e.g.: ordered or unordered lists,
 request or response examples, links, text in bold or italic, etc.

#### create, update

The description for "name" field for "create" and "update" actions.
May contain any formatting e.g.: ordered or unordered lists,
 request or response examples, links, text in bold or italic, etc.

## FILTERS

### name

The description for a filter by "name" field.
The formatting is not allowed here.

## SUBRESOURCES

### contacts

#### get_subresource

The documentation for a sub-resource, in this example for "get_subresource" action for "contacts" sub-resource.
May contain any formatting e.g.: ordered or unordered lists,
 request or response examples, links, text in bold or italic, etc.
```
