Custom form type and options
---------------------
Extended fields rendered as html controls, and control type (text, textarea, number, checkbox, etc) guessed by 
classes implementing FormTypeGuesserInterface. 
In case of extend fields, platform has three guessers (with increasing priority : DoctrineTypeGuesser, FormConfigGuesser and ExtendFieldTypeGuesser,
each provide own guesses and best guess selected based on guesser's confidence (low, medium, high, very high).

So, to define custom form type for particular field there are few ways:
### Through the compiler pass to add or override guesser's mappings 

```php
<?php

namespace Acme\Bundle\AcmeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AcmeExtendGuesserPass implements CompilerPassInterface
{
    const GUESSER_SERVICE_KEY = 'oro_entity_extend.form.guesser.extend_field';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $guesser = $container->findDefinition(self::GUESSER_SERVICE_KEY);
        $guesser->addMethodCall('addExtendTypeMapping', ["extend-type", "form-type", [option1: 12, option2: false, ...]]);
    }
}
```

###With custom guesser, that will have higher priority or will provide guess with highest confidence value.

```php
class CustomTypeGuesser implements FormTypeGuesserInterface
{
    /**
     * {@inheritdoc}
     */
    public function guessType($className, $property)
    {
        // some conditions here
        if ($className == '...' && $property == '') {
            $guessedType = '';
            $options = [...];
            return new TypeGuess($guessedType, $options, TypeGuess::HIGH_CONFIDENCE);
        }
        
        // not guessed
        return new ValueGuess(false, ValueGuess::LOW_CONFIDENCE);
    }
    
    /**
     * {@inheritdoc}
     */
    public function guessRequired($class, $property)
    {
        return new ValueGuess(false, ValueGuess::LOW_CONFIDENCE);
    }

    /**
     * {@inheritdoc}
     */
    public function guessMaxLength($class, $property)
    {
        return new ValueGuess(null, ValueGuess::LOW_CONFIDENCE);
    }

    /**
     * {@inheritdoc}
     */
    public function guessPattern($class, $property)
    {
        return new ValueGuess(null, ValueGuess::LOW_CONFIDENCE);
    }    
}

```

And register it in services.yml:

```yaml
    acme.form.guesser.extend_field:
        class: %acme.form.guesser.extend_field.class%
        tags:
            - { name: form.type_guesser, priority: N }
```

Here is some idea of what N should be, existing guessers have priorities:
* DoctrineTypeGuesser - 10
* FormConfigGuesser - 15
* ExtendFieldTypeGuesser - 20

Select it according to what you need to achieve.

###Using annotation to field or related entity (if extended field is a relation)

```php
/*
 * @Config(
 *      defaultValues={
            ...
 *          "form"={
 *              "form_type"="oro_user_select",
 *              "form_option"="{option1: ..., ...}"
 *          }
 *      }
 * )
 */

```
