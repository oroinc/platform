UPGRADE FROM 1.4 to 1.5
=======================

####General changes
- FOSRestBundle updated from 0.12.* to 1.5.0-RC2 [FOSRestBundle Upgrading](https://github.com/FriendsOfSymfony/FOSRestBundle/blob/master/UPGRADING.md)
  fos_rest section in config.yml must be updated prior to new version of bundle.

```yaml
fos_rest:
    body_listener:
        decoders:
            json: fos_rest.decoder.json
    view:
        failed_validation: HTTP_BAD_REQUEST
        default_engine: php
        formats:
            json: true
            xml: false
    format_listener:
        rules:
            - { path: '^/api/rest', priorities: [ json ], fallback_format: json, prefer_extension: false }
            - { path: '^/api/soap', stop: true }
            - { path: '^/', stop: true }
    routing_loader:
        default_format: json
```

####OroAddressBundle:
- `PhoneProvider` class has been added to help getting phone number(s) from object.

####OroCalendarBundle:
- Added calendar providers. Calendar Provider gives developers a way to add a different kind of items on a calendar. As example developer can use calendar provider to show emails as "Calendar Events" into Calendar.
- Changed REST API for CalendarConnections. Before Developer send Calendar ID of logged user and Calendar ID that connected with calendar of logged user. In current version he should send "CalendarProperty" ID into PUT and DELETE REST methods.
- Added "context menu" for calendar, based on menu from NavigationBundle. "Context menu" can be extend in any other bundle

####OroConfigBundle:
- `oro_config_entity` twig function was removed (deprecated since **1.3**)

####OroDataAuditBundle
- REST `Oro\Bundle\DataAuditBundle\Controller\Api\Rest\AuditController` was refactored to be based on `Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController`
- REST data representation was changed for resource `audit`, keys `object_class` and `object_name` was deprecated in favor of new camel case keys name.
- REST added possibility to paginate and filter collection for resource `audit`. Possible filtering by: `loggedAt`, `action`, `user`, `objectClass`.
- SOAP `Oro\Bundle\DataAuditBundle\Controller\Api\Soap\AuditController` was refactored to be based on `Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapGetController`.

####OroEntityExtendBundle:
- `Tools\ExtendConfigDumper` constant `ENTITY` has been deprecated
- Naming of proxy classes for extended entities has been changed to fix naming conflicts
- Adding of extended fields to form has been changed. From now `form.additional` is not available in TWIG template, because extended fields are added to main form and have  `extra_field` flag. The following statement can be used to loop through extended fields in TWIG template: `{% for child in form.children if child.vars.extra_field is defined and child.vars.extra_field %}`.
- Extend entity generation changes: all entities that replace their copies via `class_alias` will be generated 
  as **abstract** classes in order to allow to use them in the middle of **doctrine inheritance hierarchy**. This changes affect only 
  entities with `type=Extend`(they actually doctrine `mappedSuperclass`es)
- Added possibility to define **discriminator map** entries on child level using annotation `@Oro\Bundle\EntityExtendBundle\Annotation\ORM\DiscriminatorValue("VALUE")`.
  This is useful when auto-generated strategy fails due to duplication of short class names in the hierarchy.
- Removed not used anymore `is_inverse` config node from `extend` scope in **entity config**

####OroEntityConfigBundle:
- Added additional property to entity config class metadata `routeCreate` that should be used for **CRUD** routes configuration
  as well as already existing `routeName` and `routeView` properties

####OroIntegrationBundle:
- `Oro\Bundle\IntegrationBundle\Entity\Channel#getEnabled` deprecated in favor of `isEnabled` of the same class

####OroFormBundle:
- Added `oro_simple_color_picker` Symfony2 form type based on `hidden` and using [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff and [jquery.minicolors](https://github.com/claviska/jquery-miniColors) by Cory LaViska.
- Added `oro_simple_color_choice` Symfony2 form type based on `choice` and using [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff.
- Added `oro_color_table` Symfony2 form type intended to edit any color in a list and using [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff and [jquery.minicolors](https://github.com/claviska/jquery-miniColors) by Cory LaViska.

####OroNavigationBundle
- Added support of [System Aware Resolver](/src/Oro/Component/Config/Resources/doc/system_aware_resolver.md) in navigation.yml
- Added possibility to hide **pin** and **add to favorites** buttons on pages that does not support this kind of functionality. 

####OroSoapBundle
- Refactored `Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController` added possibility to filter input parameters using **filter objects** as well as closures
- Added `Oro\Bundle\SoapBundle\Request\Parameters\Filter\ParameterFilterInterface` that could be implemented in order to filter/transform input parameters for REST APIs
- Added `Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpEntityNameParameterFilter` - filter that transforms underscore separated class name into valid namespace class name with backslashes
- Added `Oro\Bundle\SoapBundle\Request\Parameters\Filter\IdentifierToReferenceFilter` - filter that transforms identifier into doctrine proxy object for further usage in filtering process
- Added `Oro\Bundle\SoapBundle\Request\Parameters\Filter\HttpDateTimeParameterFilter` - filter that fixes issues with datetime parameters that passed through HTTP query string
- Added **OPTIONS** request handling for all REST resources that extends `RestGetController`.
  This request exposes metadata about particular resource that was collected from `Oro\Bundle\SoapBundle\Provider\MetadataProviderInterface`'s
  by `oro_soap.provider.metadata`.
- Added possibility to add own metadata provider into chain using tag `oro_soap.metadata_provider`
- Added `Oro\Bundle\SoapBundle\Provider\EntityMetadataProvider` - collect metadata from **OroEntityConfigBundle** about entity. It exposes entity FQCN, label, description.
- Added possibility for client to ask server to include additional info into response in REST API. `X-Include` header should be used for this purposes.
- Added possibility to develop handlers that will provide additional info for client based on `X-Include` header. Handler should implement 
  `Oro\Bundle\SoapBundle\Request\Handler\IncludeHandlerInterface` and registered as service with tag `oro_soap.include_handler` with `alias` option that should correspond
  to requested info that it handles

####OroUIBundle:
- Added [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff.
- Added [jquery.minicolors](https://github.com/claviska/jquery-miniColors) by Cory LaViska.
- Added context provider(`oro_ui.provider.widget_context`) that allows to customize application behavior based depends on current context.
- Added `oro_js_template_content` twig filter to allow include `<script>` blocks inside JS templates. Example of usage:

```twig
<script type="text/html" id="my_template">
    {% set data = [
        form_row(form.name),
        form_row(form.assignedUsers),
    ] %}
    <div class="widget-content">
        <div class="alert alert-error" style="display: none;"></div>
        <form id="{{ form.vars.name }}" action="#">
            <fieldset class="form-horizontal">
                {{ UI.scrollSubblock(null, data, true, false)|oro_js_template_content|raw }}
                <div class="widget-actions form-actions" style="display: none;">
                    <button class="btn" type="reset">{{ 'Cancel'|trans }}</button>
                    <button class="btn btn-primary" type="submit">{{ 'Save'|trans }}</button>
                </div>
            </fieldset>
        </form>
        {{ oro_form_js_validation(form)|oro_js_template_content|raw }}
    </div>
</script>
```
- Added `oro_ui_content_provider_manager` global variable in order to fetch content provider's content.
  It contains reference on instance `\Oro\Bundle\UIBundle\ContentProvider\ContentProviderManager`.
- `show_pin_button_on_start_page` config node is node used anymore. Please use ability to hide navigation elements in `navigation.yml` 

####OroSearchBundle:
- Added possibility to search within hierarchy of entities using parent search alias. `mode` parameter was added to configuration.

####OroWorkflowBundle:
- Added `multiple` option for `entity` attribute to allow use many-to-many relations in workflows. Example of usage of Multi-Select type (in this example it is supposed that Opportunity entity has `Multi-Select` field named `interested_in` and `enum_code` of this type is `opportunity_interested_in`):

``` yaml
workflows:
    b2b_flow_sales_funnel:
        attributes:
            opportunity_interested_in:
                label: orocrm.sales.opportunity.interested_in.label
                property_path: sales_funnel.opportunity.interested_in
                type:  entity
                options:
                    class: Extend\Entity\EV_OpportunityInterestedIn
                    multiple: true
        transitions:
            start_from_opportunity:
                form_options:
                    attribute_fields:
                        opportunity_interested_in:
                            form_type: oro_enum_select
                            options:
                                enum_code: opportunity_interested_in
                                expanded: true
```

####OroUserBundle:
 - Added user search handler that return users that was assigned to current organization and limit by search string excluding current user. 
 Autocomplite alias for this handler is `organization_users`. 

####OroTrackingBundle:
 - Entities `TrackingWebsite` and `TrackingEvent` were made extendable

####OroBatchBundle:
 - Added possibility to disable debug logging for integration/import/export processes(were placed in `app/logs/batch/`) 
 on application level under `oro_batch.log_batch` node. Default value is `disabled`
 - Added cleanup job for DB tables of entities from `AkeneoBatchBundle`. It performs by cron every day in 1 am, and also 
  it's possible to run manually using `oro:cron:batch:cleanup` command. By default log records lifetime is `1 month`, but this
  option is configurable on application level under `oro_batch.cleanup_interval` node. For manual run it's possible to pass
  interval directly as command argument `[-i|--interval[="..."]]` 

####OroDistributionBundle:
 - Added possibility to access precise bundle in case of bundle inheritance by adding "!" sign before bundle name.
