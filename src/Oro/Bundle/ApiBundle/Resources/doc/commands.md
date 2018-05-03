# CLI Commands

## oro:api:cache:clear

This command clears the data API cache.

Run this command when you add:
 
* a new entity to `Resources/config/oro/api.yml`, 
* a new processor that changes a list of  resources available via the data API.

```bash
php bin/console oro:api:cache:clear
```

## oro:api:doc:cache:clear

This command clears or warms-up the API documentation cache.

If this command is launched without parameters it warm-ups all the API documentation caches:

```bash
php bin/console oro:api:doc:cache:clear
```

To clear the cache without then warming it up, use the `--no-warmup` option:

```bash
php bin/console oro:api:doc:cache:clear --no-warmup
```

To work only with the specified [API documentation views](https://github.com/nelmio/NelmioApiDocBundle/blob/master/Resources/doc/multiple-api-doc.rst) use the `--view` option:

```bash
php bin/console oro:api:doc:cache:clear --view=rest_json_api
```

## oro:api:dump

This command shows all resources accessible via the data API.

Run this command without parameters to display all available resources:

```bash
php bin/console oro:api:dump
```

To display resources for a particular request type, specify the `--request-type` option:

```bash
php bin/console oro:api:dump --request-type=rest --request-type=json_api
```

To show all available sub-resources, use the `--sub-resources` option:

```bash
php bin/console oro:api:dump --sub-resources
```

If you are interested in information about a particular entity, specify an entity class or entity alias as an argument:

```bash
php bin/console oro:api:dump "Oro\Bundle\UserBundle\Entity\User" --sub-resources
```

or

```bash
php bin/console oro:api:dump users --sub-resources
```

To get all entities that are not accessible via the data API, see the `--not-accessible` option:

```bash
php bin/console oro:api:dump --not-accessible
```

## oro:api:debug

This command shows details about the registered Data API actions and processors.

To display all actions, run this command without parameters:

```bash
php bin/console oro:api:debug
```

To display processors registered for a particular action, run this command with the action name as an argument:

```bash
php bin/console oro:api:debug get_list
```

Use the `request-type` option to display the processors related to a particular request type:

```bash
php bin/console oro:api:debug get_list --request-type=rest --request-type=json_api
```

## oro:api:config:dump

This command shows configuration for a particular entity.

Execute this command with an entity class or entity alias specified as an argument:

```bash
php bin/console oro:api:config:dump "Oro\Bundle\UserBundle\Entity\User"
```

or

```bash
php bin/console oro:api:config:dump users
```

To display the configuration used for a particular action, use the `--action` option (please note that the default value for this option is `get`):

```bash
php bin/console oro:api:config:dump users --action=update
```

To display the configuration for a particular request type, use the `--request-type` option:

```bash
php bin/console oro:api:config:dump users --request-type=rest --request-type=json_api
```

To display a configuration of an entity referenced by another entity, use the `--section` option:

```bash
php bin/console oro:api:config:dump addresses --section=relations
```

By default, no extra configuration data are added to the output. To add an extra data, use the `--extra` option.
The value for  the`extra` option can be: 

* actions, 
* definition, 
* filters, 
* sorters, 
* descriptions, or
* the full name of a class implements [ConfigExtraInterface](../../Config/ConfigExtraInterface.php).

```bash
php bin/console oro:api:config:dump users --extra=filters --extra=sorters
```

To display the human-readable representation of an entity and its fields:

```bash
php bin/console oro:api:config:dump users --extra=descriptions
```

If a new extra section was added, pass the FQCN of a ConfigExtra:

```bash
php bin/console oro:api:config:dump users --extra="Acme\Bundle\AcmeBundle\Config\AcmeConfigExtra"
```

You can pass multiple options:

```bash
php bin/console oro:api:config:dump users --extra=sorters --extra=descriptions --extra=filters --extra="Acme\Bundle\AcmeBundle\Config\AcmeConfigExtra"
```

## oro:api:metadata:dump

This command shows the metadata for a particular entity.

To display the metadata, run this command with an entity class or entity alias specified as an argument:

```bash
php bin/console oro:api:metadata:dump "Oro\Bundle\UserBundle\Entity\User"
```

or

```bash
php bin/console oro:api:metadata:dump users
```

To display the entity metadata used for a particular action, use the `--action` option (please note that the default value for this option is `get`):

```bash
php bin/console oro:api:metadata:dump users --action=update
```

To display the entity metadata used for a particular request type, use the `--request-type` option:

```bash
php bin/console oro:api:metadata:dump users --request-type=rest --request-type=json_api
```

## oro:api:config:dump-reference

This command shows the structure of `Resources/config/oro/api.yml`.

```bash
php bin/console oro:api:config:dump-reference
```
