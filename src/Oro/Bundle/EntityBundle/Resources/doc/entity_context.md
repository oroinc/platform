## Context ##

How to configure custom grid for activity context dialog
--------------------------------------------------------

If you want to define context grid for entity(e.g User) in the activity context dialog you need to add the
`context` option in entity class `@Config` annotation, e.g: 

``` php
/**
 * @Config(
 *      defaultValues={
 *          "grid"={
 *              default="default-grid",
 *              context="default-context-grid"
 *          }
 *     }
 * )
 */
class User extends ExtendUser
```

This option is used to recognize grid for entity with higher priority than `default` option.
In cases if these options (`context` or `default`) are not defined for entity, it won`t appear in the context dialog.
