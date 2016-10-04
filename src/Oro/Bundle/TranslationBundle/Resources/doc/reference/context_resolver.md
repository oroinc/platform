Translation Context Resolver
-------------

Translation context resolver should be used to humanize translation keys and give some additional info to developers and translators.


### Classes Description

* **TranslationBundle\Extension\TranslationContextResolverInterface** - extensions interface for resolving Translation Context by translation key


### Configuration

The context resolver must implement `Oro\Bundle\TranslationBundle\Extension\TranslationContextResolverInterface`, for example:
```php
namespace Oro\Bundle\TranslationBundle\Extension;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Default context resolver
 */
class TranslationContextResolver implements TranslationContextResolverInterface
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($id)
    {
        /**
         * Do something with key, for example parse key and based on parsed data prepare context string
         */    
        return $this->translator->trans('oro.translation.context.ui_label');
    }
}
```

Context resolver should be registered with tag `oro_translation.extension.translation_context_resolver`, for example:

```yml
    # default context resolver definition
    oro_translation.extension.translation_context_resolver:
        class: 'Oro\Bundle\TranslationBundle\Extension\TranslationContextResolver'
        arguments:
            - '@translator'
        tags:
            - { name: oro_translation.extension.translation_context_resolver, priority: 100 }
```
