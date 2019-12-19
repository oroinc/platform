# OroApiBundle

OroApiBundle enables the Web API development framework for the application data. It provides the ability to define API in the YAML configuration files regardless of standards or formats. Out-of-the-box, the bundle opens REST API that conforms the JSON API specification and enables CRUD operations support for the application ORM entities.

## Overview

The implementation of the OroApiBundle based on the following components:

* [ChainProcessor](../../Component/ChainProcessor/) - Organizes data processing flow. 
* [EntitySerializer](../../Component/EntitySerializer/) - Provides the fast access to entities data.
* [Symfony Form](https://symfony.com/doc/current/components/form.html) - Provides a flexible way to map request data to entity object. 

Also, [FOSRestBundle](https://github.com/FriendsOfSymfony/FOSRestBundle) and [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) are used for REST API.

**Notes**:

 - The main format for REST API is described at [JSON API](http://jsonapi.org/). Please be sure that you are familiar with it before you start creating REST API for your entities.
 - The auto-generated documentation and sandbox for REST API is available at `/api/doc`, e.g. `http://demo.orocrm.com/api/doc`.
 - By default, only custom entities, dictionaries, and enumerations are accessible through the API. For how to make other entities available via the API, see [Configuration Reference](./Resources/doc/configuration.md).

Please see [documentation](https://doc.oroinc.com/backend/api/) for more details.
