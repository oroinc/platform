oro_sort_by
===========
The **oro_sort_by** filter sorts an array by a property. The following example shows how items can be sorted by priority:

``` twig
{% set dataBlocks = [
    {
        'title': 'orocrm.account.sections.general'|trans,
        'class': 'active',
        'subblocks': generalSectionBlocks
    },
    {
        'title': 'orocrm.account.sections.sales'|trans,
        'priority': 100,
        'subblocks': salesSectionBlocks
    }
] %}
{% if channelSectionBlocks %}
    {% set dataBlocks = dataBlocks|merge([
        {
            'title': 'orocrm.account.sections.channels'|trans,
            'priority': 50,
            'subblocks': channelSectionBlocks
        }
    ]) %}
{% endif %}

{% set data = {'dataBlocks': dataBlocks} %}

{% set dataBlocks = data.dataBlocks|oro_sort_by %}
```
In this example the `channelSectionBlocks` can be empty and in this case it should not be rendered, but if channels exist this block should be rendered between the `general` and `sales` blocks. To achieve this we set the priority property for `channels` and `sales` blocks and than use **oro_sort_by** filter to sort blocks in correct order.

Also you can use the following options to tune **oro_sort_by** filter:

 - **property** - The path of the property by which the array should be sorted. It can be just the property name or any valid expression supported by [Symfony PropertyAccess Component](http://symfony.com/doc/current/components/property_access/introduction.html). The default value of this option is **priority**.
 - **reverse** - Indicates whether the sorting should be performed in reverse order. The default value of this option is **false**.
 - **sorting-type** - The sorting type. You can use **number**, **string** or **string-case**. The **string-case** means that case-insensitive string comparison should be used. The default value of this option is **number**.

The following example shows how you can sort array by `name` property using case-insensitive comparison:

``` twig
{% set items = items|oro_sort_by({'property': 'name', 'sorting-type': 'string-case'}) %}
```
