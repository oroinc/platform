# Oro Config Expression Component

The Config Expression component provides an engine that can compile and evaluate expressions came from configuration files, for example YAML.

```php
$language = new ConfigExpressions();

$expr    = [
    '@empty' => [
        ['@trim' => '$foo']
    ]
];
echo $language->evaluate($expr, ['foo' => ' '])
```

Or the same example but when the expression is defined in YAML file:

```yaml
@empty:
    - @trim: $foo
```

```php
$language = new ConfigExpressions();

$expr    = Yaml::parse($yaml);
echo $language->evaluate($expr, ['foo' => ' '])
```

Here is an example of more complex expression:

```yaml
@or:
    - @empty: [$call_timeout]
    - @or:
        - @and:
            message: Call timeout must be between 60 and 100
            parameters:
                - @gte: [$call_timeout, 60]
                - @lt: [$call_timeout, 100]
        - @and:
            message: Call timeout must be between 0 and 30
            - @lte: [$call_timeout, 30]
            - @gt: [$call_timeout, 0]
```

All expressions provided by this component out of the box you can find in [CoreExtension](./Extension/Core/CoreExtension.php) class.

By default, the this component implements simple logic operators and functions, and you can add any expressions you want. Here is a list of core classes you may learn to understand how to extend your DSL and customize the engine for your needs:

 - [ExpressionAssembler](./ExpressionAssembler.php)
 - [ConfigurationPassInterface](./ConfigurationPass/ConfigurationPassInterface.php) ([ReplacePropertyPath](./ConfigurationPass/ReplacePropertyPath.php))
 - [ExpressionFactoryInterface](./ExpressionFactoryInterface.php) ([ExpressionFactory](./ExpressionFactory.php))
 - [ExtensionInterface](./Extension/ExtensionInterface.php) ([CoreExtension](./Extension/Core/CoreExtension.php), [DependencyInjectionExtension](./Extension/DependencyInjection/DependencyInjectionExtension.php))
 - [ContextAccessorInterface](./ContextAccessorInterface.php) ([ContextAccessor](./ContextAccessor.php))
 - [Logical Expressions](./Condition/)
 - [Functions](./Func/)
