Internationalization Use Cases
==============================

There are 3 ways to translate content (text) displayed to a user in Oro applications. You can use:

* [Standard Symfony Translator](#standard-symfony-translator)
* [Translatable Doctrine Extension](#translatable-doctrine-extension)
* [LocalizedFallbackValue Entity from OroLocaleBundle](#localizedfallbackvalue-entity-from-orolocalebundle)

This topic explains when to use each of the three approaches and provides implementation examples. 

Standard Symfony Translator
---------------------------

The application you are developing is highly likely to contain some static content that is independent of any dynamic
application data, is always displayed in the same place, and never changes. Examples of such content are labels of field
forms, static text in the interface, flash messages, etc. Keep in mind this translation approach is used only for static
content that does not have impact on any entity (entity field values).

To translate labels, use the Translation component, which is one of the main Symfony framework components.

Oro application adds the translation functionality on top of Symfony's standard approach which enables modification of
translations via UI.

To use this approach, add the translation file to the bundle:
**Resources/translations/messages.en.yml**
```yml
oro:
   translation:
       some_field:
           label: Localized value
```

Use the Symfony translator to translate a label in the `twig` template:
**Resources/views/Form/form.html.twig**
```twig
{{ ‘oro.translation.some_field.label’|trans }}

```

or in the `php` code:
```php
<?php

namespace Oro\Bundle\AcmeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AcmeController extends Controller
{
    /**
     * @return array
     */
    public function viewAction()
    {
        return [
            'label' => $this->get('translator')->trans('oro.translation.some_field.label')
        ];
    }
}
```

More information on how to use it is available in [Symfony Documentation](https://symfony.com/doc/current/translation.html).

#### Pros:
* Does not require additional implementation and can be used in Symfony framework out-of-the-box.

#### Cons:
* Cannot be applied to translate dynamic content in the application.

Translatable Doctrine Extension
-------------------------------

Dynamic content is another type of content used in Oro applications. What is displayed in the UI is based on data loaded
from fixtures into the database and entered by users in the UI. As a rule, this data is based on dictionaries used in
certain entities.

Examples of such data are the Country and Region fields which are used in the Address entity. The application has
dictionaries for each of these entities with all available translations for all translatable fields of these entities
(into all available languages).
For instance, these fields must take into account the language selected for the interface in cases when users must be
able to filter and sort data by Country and Region in a grid with addresses. In this case, use
[Gedmo/Translatable](http://atlantic18.github.io/DoctrineExtensions/doc/translatable.html).  
Such fields are displayed in the UI in the selected language. All requests to the database will change in order for the
translations grid to retrieve data based on the current locale. 

Bellow is an example of an entity which must work with `Gedmo/Translatable` for the `name` field of this entity.
```php
<?php

namespace Oro\Bundle\AcmeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Table("oro_acme_country")
 * @ORM\Entity()
 * @Gedmo\TranslationEntity(class="Oro\Bundle\AcmeBundle\Entity\CountryTranslation")
 */
class Country implements Translatable
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Gedmo\Translatable
     */
    private $name;

    /**
     * @var string
     *
     * @Gedmo\Locale
     */
    private $locale;

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $locale
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }
}
```

Also, `Gedmo/Translatable` requires a dictionary with all translations for the original entity fields:
```php
<?php

namespace Oro\Bundle\AcmeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;

/**
 * @ORM\Table(name="oro_acme_country_trans")
 * @ORM\Entity()
 */
class CountryTranslation extends AbstractTranslation
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $content;
}
```

For the grid to have working translations for entities with `Gedmo` fields, add the `HINT_TRANSLATABLE` hint:
**Resources/config/oro/datagrids.yml**
```yml
datagrids:
   acme-grid:
       source:
           type: orm
           query:
               ...
           hints:
               - HINT_TRANSLATABLE
```

Below is a simple example of a grid configuration which uses the hint:
**Resources/config/oro/datagrids.yml**
```yml
datagrids:
   acme-grid:
       source:
           type: orm
           query:
               select:
                   - country.id
                   - country.name
               from:
                   - { table: Oro\Bundle\AcmeBundle\Entity\Country, alias: country }
           hints:
               - HINT_TRANSLATABLE

       columns:
           name:
               label: Country Name

       sorters:
           columns:
               name:
                   data_name: country.name

       filters:
           columns:
               name:
                   type: string
                   data_name: country.name
```

In this case, the values in the name field are displayed in the required language, and filtering and sorting for the
values happens in the selected language.

#### Pros:
* Dynamic content in the application can be easily translated.
* The translatable fields have value related to the actual language of the application.

#### Cons:
* The user must switch the current language into the required language in the system configuration to
fill in the fields with the required values.
* Translatable fields can have values only for some languages but not for `Localizations`.

LocalizedFallbackValue Entity from OroLocaleBundle
--------------------------------------------------

UI language is incorporated into the localization entity. You can have several localizations in the application with the
same interface language. However, data for various localizations may differ.
In addition, if the current localization is assigned a parent localization then in cases when a field value does not
exist, it is taken from the parent. This allows for setting up a flexible translation tree via the UI.
 
To implement this approach, use the
[LocalizedFallbackValue](https://github.com/oroinc/platform/blob/master/src/Oro/Bundle/LocaleBundle/Resources/doc/reference/entities.md#localizedfallbackvalue).

To use `LocalizedFallbackValue` for fields into the entity, make it is extendable:
 ```php
<?php

namespace Oro\Bundle\AcmeBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AcmeBundle\Model\ExtendAcme;

/**
 * @ORM\Table(name="oro_acme")
 * @ORM\Entity()
 */
class Acme extends ExtendAcme
{
    /**
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_acme_name",
     *      joinColumns={
     *          @ORM\JoinColumn(name="acme_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $names;
}
```

```php
<?php

namespace Oro\Bundle\AcmeBundle\Model;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * @method LocalizedFallbackValue getName(Localization $localization = null)
 * @method LocalizedFallbackValue getDefaultName()
 * @method void setDefaultName(string $value)
 */
class ExtendAcme
{
    public function __construct()
    {
    }
}
```

Enable `Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass` for the entity and the field
inside the bundle class:
```php
<?php

namespace Oro\Bundle\AcmeBundle;

use Oro\Bundle\AcmeBundle\Entity\Acme;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroAcmeBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(
            new DefaultFallbackExtensionPass(
                [
                    Acme::class => [
                        'name' => 'names',
                    ]
                ]
            )
        );
    }
}
```

As the result, a proxy class is generated in the application cache:
**cache/prod/oro_entities/Extend/Entity/EX_OroAcmeBundle_Acme.php**
```php
<?php

namespace Extend\Entity;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

abstract class EX_OroAcmeBundle_Acme extends \Oro\Bundle\LocaleBundle\Model\ExtendFallback implements \Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface
{
  /**
   * @param Localization|null $localization
   * @return LocalizedFallbackValue|null
   */
   public function getName(\Oro\Bundle\LocaleBundle\Entity\Localization $localization = NULL)
   {
       return $this->getFallbackValue($this->names, $localization);
   }
}
```

To be able to provide translations in the UI, use the following example of the form type:
```php
<?php

namespace Oro\Bundle\AcmeBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AcmeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'names',
            LocalizedFallbackValueCollectionType::class,
            ['label' => 'oro.acme.names.label']
        );
    }
}
```

To retrieve a name for the `Localization`, it is enough to use the `getName()` method.

#### Pros:
* The translatable fields can be translated for each `Localization` available in the application.
* It is easy to provide values for the `Localizations` in the entity form without changing the actual UI language.

#### Cons:
* Translated values cannot be used in the datagrids for filtering and sorting out-of-the-box.
* Additional implementation is required to render translated values for the actual `Localization`.
