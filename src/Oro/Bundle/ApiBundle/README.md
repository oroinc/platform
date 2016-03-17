OroApiBundle
============

The goal of this bundle is to make a creation of different kinds of Data APIs as easy as possible.

The main idea of this bundle is to provide some default implementation which can be reused and easily changed for any entity if required.

To achieve this, this bundle is implemented based on two ORO components: [ChainProcessor](../../Component/ChainProcessor/) and [EntitySerializer](../../Component/EntitySerializer/). The ChainProcessor component is responsible to organize data processing flow. The EntitySerializer component provides the fast access to entities data.

**Notes**:
 - For now only GET requests for REST and JSON.API are implemented.
 - This documentation is not full and it will be completed soon.

Table of Contents
-----------------
 - [Configuration](./Resources/doc/configuration.md)
 - [Actions](./Resources/doc/actions.md)
 - [Processors](./Resources/doc/processors.md)
 - [Debug commands](./Resources/doc/debug_commands.md)
