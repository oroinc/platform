# Oro PHP Utils Component

`Oro PHP Utils Component` provides some useful PHP libraries.

## ArrayUtil class

**Description:**
Provides a set of useful functions to work with PHP arrays.

**Methods:**

- **isAssoc** - Checks whether the array is associative or sequential.
- **sortBy** - Sorts an array by specified property using the stable sorting algorithm. See http://en.wikipedia.org/wiki/Sorting_algorithm#Stability.

## ReflectionClassHelper class

**Description:**
Provides a set of useful extensions of PHP class reflection API.

**Methods:**

- **hasMethod** - Checks whether a method exists in class declaration.
- **isValidArguments** - Validates whether a method has the given arguments.
- **completeArguments** - Completes arguments array by default values that were not passed, but set at a method 
