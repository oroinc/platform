UPGRADE FROM 1.4 to 1.5
=======================

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

####OroIntegrationBundle:
- `Oro\Bundle\IntegrationBundle\Entity\Channel#getEnabled` deprecated in favor of `isEnabled` of the same class

####OroFormBundle:
- Added `oro_simple_color_picker` Symfony2 form type based on `hidden` and using [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff and [jquery.minicolors](https://github.com/claviska/jquery-miniColors) by Cory LaViska.
- Added `oro_simple_color_choice` Symfony2 form type based on `choice` and using [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff.
- Added `oro_color_table` Symfony2 form type intended to edit any color in a list and using [jquery.simplecolorpicker](https://github.com/tkrotoff/jquery-simplecolorpicker) by Tanguy Krotoff and [jquery.minicolors](https://github.com/claviska/jquery-miniColors) by Cory LaViska.

####OroNavigationBundle
- Added support of [System Aware Resolver](/src/Oro/Component/Config/Resources/doc/system_aware_resolver.md) in navigation.yml

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

####OroSearchBundle:
- Added possibility to search within hierarchy of entities using parent search alias. `mode` parameter was added to configuration.
