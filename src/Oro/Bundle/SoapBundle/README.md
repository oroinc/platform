# OroSoapBundle

OroSoapBundle **is deprecated**. Please, use [OroApiBundle](https://github.com/oroinc/platform/tree/master/src/Oro/Bundle/ApiBundle) instead.

OroSoapBundle extends FOSRestBundle and provides developers with a way to create REST and SOAP API in their bundles by extending abstract API controller classes.

## Soap

Bundle adds support of SOAP controller classes and builds a single WSDL file for SOAP API definition.

Configuration in config/config.yml file:

    be_simple_soap:
        ...
        services:
            ...
            soap:
                namespace:     http://symfony.loc/Api/1.0/
                binding:       rpc-literal
                resource:      "%kernel.project_dir%/src/Oro/Bundle/SoapBundle/Resources/config/soap.yml"
                resource_type: yml

Parameters:

 - **namespace** - namespase of WSDL file
 - **binding** - SOAP messaging format (rpc-literal)
 - **resource** - full path to yml config file
 - **resource_type** - for SoapBundle is 'yml'

Resource file consists of array of SOAP API controllers.

Example of yml resource file:

    classes:
      - Oro\Bundle\SearchBundle\Controller\Api\SoapController
      - Acme\Bundle\DemoBundle\Controller\Api\SoapController

## REST

### Include additional info into response

We enabled support of `X-Include` header that allows client to ask server to fill request with some additional info that
is not required for each call but useful single time (such as total count of records in collection, last modified date etc..).
This header should contain *data keys* separated by semicolon.
In order to support this feature your REST controller should extend one of base controllers from `OroSoapBundle` and send responses
using `buildResponse` method.

Out of the box we support `totalCount` add-in, but there is possibility to develop handlers responsible for another add-in requests.
Handler should implement `Oro\Bundle\SoapBundle\Request\Handler\IncludeHandlerInterface` and registered as service with tag `oro_soap.include_handler`
with `alias` option that should correspond to requested add-in key that it handles.

**Example**:

Client asked for info with following header `X-Include: totalCount, lastModifiedDate`
Now system contains handler that could process `totalCount` add-in only, so response will contain header
`X-Include-Unknown: lastModifiedDate` and header with record count (e.g. `X-Include-Total-Count: 200`)

Then you want to add support of `lastModifiedDate` add-in then you develop new handler(let's call it `LastModifiedHandler`)
then register it as service

```
   acme.demo.handler.last_modified:
       class: Acme\Bundle\DemoBundle\Handler\LastModifiedHandler
       tags:
           - { name: oro_soap.include_handler, alias: lastModifiedDate }
```

If system has handlers to process add-ins requested, but current resource/controller does not provide all dependencies 
that handler requires then those add-ins will be listed in response header `X-Include-Unsupported`
