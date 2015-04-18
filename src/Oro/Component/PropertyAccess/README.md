Oro Property Access Component
=============================

The Oro Property Access component reads/writes values from/to object/array graphs using a simple string notation.
Actually this component is mostly a copy of the [Symfony PropertyAccess](http://symfony.com/doc/current/components/property_access/index.html) component, but the Oro component allows to use the same syntax of the property path for objects and arrays, and it was the main reason why it was created.

Also `remove` method was added to the [PropertyAccessor](./PropertyAccessor.php) to allow to remove items from arrays or objects.
