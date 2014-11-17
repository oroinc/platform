OroActivityListBundle
=====================
The `OroActivityListBundle` provide ability to show all activities in one widget.

Table of content
----------------
- [Fundamentals](#fundamentals)
- [Add widget into a page](#add-widget-into-a-page)
- [Register an new activity](#register-an-new-activity)
- [Configuration](#configuration)
- [How it works](#how-it-works)

Fundamentals
------------
Activity is a combination of a common and ubiquitous activity and an entity that encompasses this activity.

Examples of activities are:

- Emails
- Calls
- Tasks
- Comments
- Notes
- Calendar events

Activity list is an entity with user owner.  
Visualization of Activity list defined as widget - a small block that performs a certain function in our case is displaying activities related to the entity record in a single list on its view form with ability to filter by activity type (multiselect) and by date of last change made.

The most important details of the activity is shown by default, but you have access to the full activity record. Only a limited number of records displayed by default, but you can load more by clicking some button on the bottom of the list.

The widget currently displayed under Activities & Actions section at such pages like contact, user, lead, opportunity and other entities for which at least one activity is associated.

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

Register an new activity
------------------------
To add an new activity you need to register a service that implements **ActivityListProviderInterface** with **oro_activity_list.provider** tag. 
This will add your provider class into a bunch of providers that will be invoked to fetch data ordering by priority (added in service definition).
Each activity have its own template placed at js/activityItemTemplate.js

Configuration
-------------
A few options of the widget could be changed through System Configuration, for example the list should display the most recently updated activities at the top. Under Display settings: 

 - field sorting - sorting available by creation/updation date
 - sorting direction - ascending or descending
 - bunch size - number of activities displayed on the activities list and every time on "load more" action
 
If necessary to add a new option then it should be defined in system_configuration.yml file.
See **OroConfigBundle documentation** for more info.

How it works
------------

Describe here the whole process of how activity list works
