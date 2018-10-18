# The Request Type

The request type is key concept of the ApiBundle. It is very simple, but very important for understanding
how this bundle works and how to extend its functionality.

Each API request is processed by a set of processors, each processor does its own small piece of work to reach the
requested result. Some processors analyze and validate the request data, other processors update the database
and prepare a correct response. It is easy to imagine that if you need to process different types of
API request, e.g. REST API and REST API that conforms [JSON.API](http://jsonapi.org/) specification,
you need to have a different set of processors. Some of them may work for all request types, but others only
for specific request types. The concept of the request type in ApiBundle reflects all mentioned above and
allows you to configure shared and specific processors easily.

Take a look at [RequestType](../../Request/RequestType.php) class. It was designed to contain different aspects
of a request, and a combination of these aspects represents a specific request type.
For instance, if this class contains both `rest` and `json_api` it can be interpreted as a request type for REST API
that conforms JSON.API specification. If we add, for example, `my_erp` aspect to this request type, it will mean
that such request type represents REST API specially designed for integration with "My ERP" system and is based on
JSON.API specification. As an another example, lets assume that you have two types of REST API, one conforms
JSON.API specification and another one conforms GraphQL specification. In this case RequestType object can
contain `rest` and `json_api` for JSON.API requests and `rest` and `graphql` for GraphQL requests.
Such combinations of aspects allows you to configure different sets of processors for each request type.
For examples how to configure processors for different aspects and its combinations see
[Processor Conditions](./processors.md#processor-conditions).
