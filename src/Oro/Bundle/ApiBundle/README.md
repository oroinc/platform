OroApiBundle
============

The goal of this bundle is to make a creation of different kinds of Data APIs as easy as possible.

The main idea of this bundle is to provide some default implementation which can be reused and easily changed for any entity if required.

To achieve this, this bundle is implemented based on two ORO components: [ChainProcessor](../../Component/ChainProcessor/) and [EntitySerializer](../../Component/EntitySerializer/). The ChainProcessor component is responsible to organize data processing flow. The EntitySerializer component provides the fast access to entities data.

Also we use [FOSRestBundle](https://github.com/FriendsOfSymfony/FOSRestBundle) and [NelmioApiDocBundle](https://github.com/nelmio/NelmioApiDocBundle) for REST API.

**Notes**:
 - The main format for REST API is described in [JSON API](http://jsonapi.org/). Please be sure that you are familiar with it before you start creating REST API for your entities.
 - The auto-generated documentation and sandbox for REST API is available at `/api/doc/rest_json_api`, e.g. `http://demo.orocrm.com/api/doc/rest_json_api`. If you plan to use the sandbox do not forget to generate API key on the user profile page.
 - By default all entities, except custom entities, dictionaries and enumerations are not accessible through Data API. The [Configuration Reference](./Resources/doc/configuration.md) describes how to add an entity to Data API.

Please see [documentation](./Resources/doc/index.md) for more details.
