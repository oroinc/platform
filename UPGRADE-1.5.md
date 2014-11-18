UPGRADE FROM 1.4 to 1.5
=======================

####OroAddressBundle:
- `PhoneProvider` class has been added to help getting phone number(s) from object.

####OroEntityExtendBundle:
- `Tools\ExtendConfigDumper` constant `ENTITY` has been deprecated
- Naming of proxy classes for extended entities has been changed to fix naming conflicts
- Adding of extended fields to form has been changed. From now `form.additional` is not available in TWIG template, because extended fields are added to main form and have  `extra_field` flag. The following statement can be used to loop through extended fields in TWIG template: `{% for child in form.children if child.vars.extra_field is defined and child.vars.extra_field %}`.

####OroConfigBundle:
- `oro_config_entity` twig function was removed(deprecated since **1.3**)

####OroDataAuditBundle
- REST `Oro\Bundle\DataAuditBundle\Controller\Api\Rest\AuditController` was refactored to be based on `Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController`
- REST data representation was changed for resource `audit`, keys `object_class` and `object_name` was deprecated in favor of new camel case keys name.
- REST added possibility to paginate and filter collection for resource `audit`. Possible filtering by: `loggedAt`, `action`, `user`, `objectClass`.
- SOAP `Oro\Bundle\DataAuditBundle\Controller\Api\Soap\AuditController` was refactored to be based on `Oro\Bundle\SoapBundle\Controller\Api\Soap\SoapGetController`.

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

