Oro Dashboard Bundle
====================

This bundle allows you to manage dashboards for your application created using ORO Platform. You can have any number of dashboards in you application. The dashboard is a page is displayed as a first page of your application and contains a set of blocks (named as `widgets`) contain important or useful information for your customers.
Currently the following widgets is available:

 - Grid
 - Tabbed
 - Pie chart
 - Bar chart

But you can add you own widget type easily. It will be described in How to create new widget type section.

Table of Contents
-----------------

- [Dashboard configuration](#dashboard_configuration)
- [How to ...](#how-to)
    - [make a dashboard a first page of your application](#how_to_first_page)
    - [setup default dashboard](#how_to_default_dashboard)
    - [rename or localize a name of existing dashboard](#how_to_rename_dashboard)
    - [remove existing dashboard](#how_to_remove_dashboard)
    - [remove existing widget from a dashboard](#how_to_remove_widget)
    - [add new widget](#how_to_add_widget)

<a href="dashboard_configuration"></a>Dashboard configuration
-------------------------------------------------------------
``` yaml
oro_dashboard_config:
    widgets:                                            # widget declaration section
        quick_launchpad:                                # widget name
            route:      oro_dashboard_itemized_widget   # widget route
            route_parameters: { bundle: OroDashboardBundle, name: quickLaunchpad } # additional route parameters

    dashboards:                                         # dashboard configuration section
        main:                                           # dashboard name
            label:      oro.dashboard.title.main        # a label name used to localize dashboard name
            position:   20                              # a position determines an order a dashboard is shown in the dropdown list
            widgets:                                    # a set of widgets available on a dashboard
                quick_launchpad:                        # a dashboard name (must be declared in `widget` section first)
                    position:   10                      # a widget position
        quick_launchpad:
            label:      oro.dashboard.title.quick_launchpad
            twig:       OroDashboardBundle:Index:quickLaunchpad.html.twig
            position:   10
```
To view all configuration options you can launch `config:dump-reference` command:
```bash
php app/console config:dump-reference OroDashboardBundle
```

<a href="how_to"></a>How to ...
-------------------------------

## <a href="how_to_first_page"></a>How to make a dashboard a first page of your application ##

Make the following changes in `app/config/routing.yml`:
```yaml
oro_default:
    pattern:  /
    defaults:
        _controller: OroDashboardBundle:Dashboard:index
```

## <a href="how_to_default_dashboard"></a>How to setup default dashboard ##

The default dashboard can be setting up in `app/config/config.yml`:
```yaml
oro_dashboard:
    default_dashboard: quick_launchpad
```

## How to rename or localize a name of existing dashboard ##

To rename existing dashboard you need to change `label` option in `app/config/config.yml` or `dashboard.yml` of your bundle. For example to rename `quick_launchpad` dashboard you can use:
```yaml
oro_dashboard_config:
    dashboards:
        main:
            quick_launchpad:
                label: acme.dashboard.title.quick_launchpad
```
Also you need to provide localization of new label (in this example it is `acme.dashboard.title.quick_launchpad`) in `Resouces\translations\messages.en.yml`:
```yaml
acme:
    dashboard:
        title:
            quick_launchpad: Quick Launchpad
```

## How to remove existing dashboard ##

To remove existing dashboard you need to make it invisible in `app/config/config.yml` or `dashboard.yml` of your bundle. For example to remove `quick_launchpad` dashboard you can use:
```yaml
oro_dashboard_config:
    dashboards:
        main:
            quick_launchpad:
                visible: false
```

## How to remove existing widget from a dashboard ##

To remove existing widget from a dashboard you need to make it invisible in `app/config/config.yml` or `dashboard.yml` of your bundle. For example to remove `quick_launchpad` widget from `main` dashboard you can use:
```yaml
oro_dashboard_config:
    dashboards:
        main:
            widgets:
                quick_launchpad:
                    visible: false
```

## How to add new widget ##

In this example lets create a grid widget. First you need to create a grid. Use `datagrid.yml` of your bundle to do this. For example lets create `dashboard-recent-calls-grid` grid:
```yaml
datagrid:
    dashboard-recent-calls-grid:
        options:
            entityHint: call
        source:
            type: orm
            acl_resource: orocrm_call_view
            query:
                select:
                    - call.id
                    - CONCAT(contact.firstName, CONCAT(' ', contact.lastName)) as contactName
                    - contact.id as contactId
                    - account.name as accountName
                    - call.subject
                    - CONCAT(CASE WHEN call.phoneNumber IS NOT NULL THEN call.phoneNumber ELSE contactPhone.phone END, '') as phone
                    - call.callDateTime as dateTime
                    - directionType.name as callDirection
                from:
                    - { table: %orocrm_call.call.entity.class%, alias: call }
                join:
                    left:
                        - { join: call.relatedContact, alias: contact }
                        - { join: call.contactPhoneNumber, alias: contactPhone }
                        - { join: call.relatedAccount, alias: account }
                        - { join: call.direction, alias: directionType }
                    inner:
                        - { join: call.owner, alias: ownerUser }
                where:
                    and:
                      - ownerUser.id = @oro_security.security_facade->getLoggedUserId
        columns:
            callDirection:
                type: twig
                label: ~
                frontend_type: html
                template: OroCRMCallBundle:Datagrid:Column/direction.html.twig
            dateTime:
                label: orocrm.call.datagrid.date_time
                frontend_type: datetime
            contactName:
                type: twig
                label: orocrm.call.datagrid.contact_name
                frontend_type: html
                template: OroCRMCallBundle:Datagrid:Column/contactName.html.twig
            subject:
                type: twig
                label: orocrm.call.subject.label
                frontend_type: html
                template: OroCRMCallBundle:Datagrid:Column/subject.html.twig
            phone:
                label: orocrm.call.phone_number.label
        sorters:
            columns:
                dateTime:
                    data_name: call.callDateTime
            default:
                dateTime: DESC
        options:
            toolbarOptions:
                hide: true
                pageSize:
                    items: [10]
                    default_per_page: 10
```

Next you need to create a TWIG template renders your grid. This template should be located `Resources/views/Dashboard` directory in of your bundle. For example lets create `recentCalls.html.twig`:
```twig
{% extends 'OroDashboardBundle:Dashboard:widget.html.twig' %}
{% import 'OroDataGridBundle::macros.html.twig' as dataGrid %}

{% block content %}
    {{ dataGrid.renderGrid('dashboard-recent-calls-grid') }}
    <script type="text/javascript">
        require(['orocrm/call/info-opener']);
    </script>
{% endblock %}

{% block actions %}
    {% set actions = [{
        'url': path('orocrm_call_index'),
        'type': 'link',
        'label': 'orocrm.dashboard.recent_calls.view_all'|trans
    }] %}

    {{ parent() }}
{% endblock %}
```

After that you need to register your widget and add it on the appropriate dashboard. Use `dashboard.yml` of your bundle to do this. For example:
```yaml
oro_dashboard_config:
    widgets:
        recent_calls:                               # register a widget
            label:      orocrm.dashboard.recent_calls.title
            route:      oro_dashboard_widget        # you can use existing controller to render your TWIG template
            route_parameters: { bundle: OroCRMCallBundle, name: recentCalls }   # just specify a bundle and a TWIG template name
            acl:        orocrm_call_view

    dashboards:
        main:
            widgets:
                recent_calls:                       # add 'recent_calls' widget on 'main' dashboard
                    position:   50
```

Also there are some additional TWIG templates for most used widgets, for example `tabbed`, `itemized` (a widget contains some items, for example links), `chart` and others. You can find them in `OroDashboardBundle/Resources/views/Dashboard` directory.
