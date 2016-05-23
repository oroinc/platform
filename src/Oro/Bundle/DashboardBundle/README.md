OroDashboardBundle
==================
 
This bundle allows you to manage dashboards for your application created using ORO Platform.
Dashboard is an entity with user owner. Dashboard view page contains a set of blocks (widgets) with important or useful
information for your users. User with permissions can add any available widget to dashboard.
 
Developer also can configure what widgets are available using configuration files.
 
Dashboard configuration
-----------------------
```yaml
oro_dashboard_config:
    # Configuration of widgets
    widgets:                                                 # widget declaration section
        quick_launchpad:                                     # widget name
            icon:       icon.png                             # widget icon shown on widget add dialog
            description: Text                                # description of widget
            acl:        acl_resource                         # acl resource of dashboard
            route:      oro_dashboard_itemized_widget        # widget route
            route_parameters: { bundle: OroDashboardBundle, name: quickLaunchpad } # additional route parameters
            isNew: true                                      # show or not "New" label next to the title
 
    # Configuration of dashboards
    dashboards:                                              # dashboard configuration section
        main:                                                # dashboard name
            twig: OroDashboardBundle:Index:default.html.twig # dashboard template (used by default)
```
To view all configuration options you can launch `config:dump-reference` command:
```bash
php app/console config:dump-reference OroDashboardBundle
```

How to add new dashboard
------------------------

To add new dashboard you need to create new data migration:

```php
<?php

namespace Oro\Bundle\DashboardBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class LoadDashboardData extends AbstractDashboardFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        // we need admin user as a dashboard owner
        return ['Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // create new dashboard
        $dashboard = $this->createAdminDashboardModel(
            $manager,      // pass ObjectManager
            'new_dashoard' // dashboard name
        );
        
        // to update existing one
        $dashboard = $this->findAdminDashboardModel(
            $manager,      // pass ObjectManager
            'existing_one' // dashboard name
        );
                
        $dashboard
            // if user doesn't have active dashboard this one will be used
            ->setIsDefault(true)
            
            // dashboard label
            ->setLabel(
                $this->container->get('translator')->trans('oro.dashboard.title.main')
            )
            
            // add widgets one by one
            ->addWidget(
                $this->createWidgetModel(
                    'quick_launchpad',  // widget name from yml configuration
                    [
                        0, // column, starting from left
                        10 // position, starting from top
                    ]
                )
            );

        $manager->flush();
    }
}

```
 
How to make a dashboard a first page of your application
--------------------------------------------------------
 
Make the following changes in `app/config/routing.yml`:
```yaml
oro_default:
    pattern:  /
    defaults:
        _controller: OroDashboardBundle:Dashboard:view
```
 
How to add new widget
---------------------
 
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
                    - call.subject
                    - call.phoneNumber as phone
                    - call.callDateTime as dateTime
                    - directionType.name as callDirection
                from:
                    - { table: %orocrm_call.call.entity.class%, alias: call }
                join:
                    left:
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
```
 
Also there are some additional TWIG templates for mostly used widgets, for example `tabbed`, `itemized` (a widget contains some items, for example links), `chart` and others.
You can find them in `OroDashboardBundle/Resources/views/Dashboard` directory.

Widget configuration
---------------------

Each widget can have own configuration. Configuration values stores for each widget instance on dashboard.

To add configuration, the widget configuration should contain 'configuration' block, there should be list of available configuration values. For example:

```yaml
oro_dashboard_config:
    widgets:
        my_test_chart:
  ...
            configuration:
                testValue:                       # field name
                    type: text                   # field type
                    options:                     # field options    
                       label: acme.test.label    # field label            
                    show_on_widget: true         # if true - value of config parameter will be shown at the bottom of widget. By default - false
```
If developer wants to add some config value to all widgets, he can use 'widgets_configuration' block of dashboard.yml file. For example:

```yaml
oro_dashboard_config:
    widgets_configuration:
        globalConfigParameter:
            type: text
            options:
               label: acme.globalConfigParameter.label
```

Grid widget configuration
-------------------------

There is special route "oro_dashboard_grid" for rendering grids allowing to set grid specific options.

This action also allows you to configure shown view for grid.

### dashboard.yml

``` yml
    accounts_grid:
        label: Accounts grid
        route: oro_dashboard_grid # using this route configuration for selection of grid view will be added automatically
        route_parameters:
            widget: accounts_grid
            gridName: accounts-grid
            renderParams:
                routerEnabled: true # enable storing grid state in url
                enableFilters: true # enable showing filters (default: false)
                enableViews: true # enable showing views (default: enableFilters)
```
