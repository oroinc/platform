Debug commands
--------------

### oro:api:debug
This command shows details about registered API actions and processors.

If you want to know all actions run this command without parameters:

```bash
php app/console oro:api:debug
```

If you want to know which processors are registered for a particular action run this command with the action name as an argument:

```bash
php app/console oro:api:debug get_list
```

Also using `request-type` option you can see the processors which will be executed for a particular request type:

```bash
php app/console oro:api:debug get_list --request-type=rest --request-type=json_api
```

### oro:api:resources:dump
This command shows all API resources.

Run this command without parameters to see all available API resources:

```bash
php app/console oro:api:resources:dump
```

or specify `request-type` option if you need to know API resources for a particular request type:

```bash
php app/console oro:api:resources:dump --request-type=rest --request-type=json_api
```

### oro:api:config:dump
This command shows API configuration for a particular entity.

Just run this command and specify entity class or entity alias as an argument:

```bash
php app/console oro:api:config:dump "Oro\Bundle\UserBundle\Entity\User"
```

or

```bash
php app/console oro:api:config:dump users
```

To see API configuration for a particular request type you can use `request-type` option:

```bash
php app/console oro:api:config:dump users --request-type=rest --request-type=json_api
```

Also, if you want to see human-readable representation of entity and its fields, you can use `with-descriptions` option:

```bash
php app/console oro:api:config:dump users --with-descriptions
```

At the last, you can use `section` option to see a configuration of an entity when it is referenced by another entity:

```bash
php app/console oro:api:config:dump addresses --section=relations
```

### oro:api:metadata:dump
This command shows metadata for a particular entity.

To see metadata run this command and specify entity class or entity alias as an argument:

```bash
php app/console oro:api:metadata:dump "Oro\Bundle\UserBundle\Entity\User"
```

or

```bash
php app/console oro:api:metadata:dump users
```

If you want to see entity metadata that is used for a particular request type you can use `request-type` option:

```bash
php app/console oro:api:metadata:dump users --request-type=rest --request-type=json_api
```
