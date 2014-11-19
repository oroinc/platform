OroActivityListBundle
=====================
The `OroActivityListBundle` provide ability to show all activities in one widget.

Table of content
----------------
- [Fundamentals](#fundamentals)
- [Add widget into a page](#add-widget-into-a-page)
- [Register an new entity](#register-an-new-entity)
- [Configuration](#configuration)

Fundamentals
------------
Activity is a combination of a common and ubiquitous activity and an entity that encompasses this activity.

Examples of activities are:

- Emails
- Calendar events

Activity list is an entity.  
Visualization of Activity list defined as widget - a block that performs a certain function. In our case is displaying activities related to the entity record in a single list with ability to filter by activity type (multiselect) and by date (daterange filter).

The most important details of the activity is shown by default, but you have access to the full activity record. Only a limited number of records displayed by default, sorted by update date in descending order. The limitation and sorting can be changed in [UI](#configuration) .

The widget currently displayed in "Record activities" placeholder at view pages of entities witch was configured as receiver of activities.

**Example UI within contact page**
![An example of widget](./Resources/doc/example.png)

Add widget into a page
----------------------
Place widget into your page using placeholders, passing an entity object into template.

```
  {%- set activitiesData -%}
    {% placeholder view_content_data_activities with {entity: entity} %}
  {%- endset -%}
  {% set dataBlocks = dataBlocks|merge([{
    'title': 'Title',
    'subblocks': [{
        'spanClass': 'empty',
        'data': [activitiesData]
    }]
  }]) %}
```

Register an new entity
----------------------
To add an new entity to be displayed within widget you need to register a service that implements **ActivityListProviderInterface** and tagged as **oro_activity_list.provider**. Working example can be found in EmailBundle or CalendarBundle:
```
    oro_calendar.activity_list.provider:
        class: %oro_calendar.activity_list.provider.class%
        arguments:
           - @oro_entity.doctrine_helper
           - @doctrine
        tags:
           - {name: oro_activity_list.provider, priority: 50}
```
This will add your provider class into a bunch of providers (**ActivityListChainProvider**) that will be invoked to fetch data ordering by priority (added in service definition). For now 'priority' do not have much sense, but it may be useful in future implementations or in case of overriding some existing providers in 3rd party bundles.

Each activity entity has its own row template for UI component. Generally you are free to place it anywhere you want, only requirement is: it's path should be returned in Provider via method getTemplate(). E.g.
```
class CalendarEventActivityListProvider implements ActivityListProviderInterface
{
...
/**
* {@inheritdoc}
*/
public function getTemplate()
{
   return 'OroCalendarBundle:CalendarEvent:js/activityItemTemplate.js.twig';
}
...

```

Configuration
-------------
Sorting and limitation could be changed in UI: System -> Configuration -> Display settings -> section "Activity lists".

 - Sort by field - sorting available by "Created date" or "Updated date"(default).
 - Sort direction - Ascending or Descending(default).
 - Items Per Page By Default - number of activities displayed in list. Default is 25. 
