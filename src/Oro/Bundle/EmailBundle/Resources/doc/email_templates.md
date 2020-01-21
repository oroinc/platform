Email templates
===============

Any bundle can define it's own templates using Data Fixtures.
To achieve this - add a fixture in SomeBundle\Migrations\Data\ORM folder that extends Oro\Bundle\EmailBundle\Migrations\Data\ORM\AbstractEmailFixture abstract class and implements the only method - getEmailsDir:
``` php
class DataFixtureName extends AbstractEmailFixture
{
    /**
     * Return path to email templates
     *
     * @return string
     */
    public function getEmailsDir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '../data/emails';
    }
}
```
Place email templates in that defined folder with any file name.

Email format
------------
It's possible to define email format based on file name, e.g.:

 - html format: update_user.html.twig, some_name.html
 - txt format: some_name.txt.twig, some_name.txt
 - default format - html, if file extension can't be recognized as html or txt

Email parameters
-----------------
Each template must define these params:

 - entityName - each template knows how to display some entity
 - subject - email subject

Optional parameter:

 - name - template name; the template file name without extension is used if this parameter is not specified 
 - isSystem - 1 or 0, default - false (0)
 - isEditable - 1 or 0, default - false (0); make sense only if isSystem = 1 and allow to edit content of system templates

Params defined with syntax at the top of the template
```
@entityName = Oro\Bundle\UserBundle\Entity\User
@subject = Subject {{ entity.username }}
@isSystem = 1
```

Available variables in email templates
--------------------------------------

For the security reasons, for Email templates is enabled the ["Sandbox" mode](https://twig.symfony.com/doc/2.x/api.html#sandbox-extension) for the Twig Templating Engine.

It means that in email templates allowed only the limited set of variables:

* the set of system variables and
* the set of entity variables (entity object of the entityName class with all it's fields if the entity is set for template).

The list of these variables is provided on the Email Template edit page of the admin UI (on the System > Emails > Templates menu item).

Also there are additional Twig functions, filters, and tags, that registered and allowed to be used in Email Templates. The full list of these functions, filters, and tags you can find by searching for mentions of the 'oro_email.twig.email_security_policy' service in Oro Application CompillerPass Classes or see on the [Email Templates](https://doc.oroinc.com/user/back-office/system/emails/email-templates/) section of the Admin Guide.

Extend available data in email templates
----------------------------------------

To extend the available data (variables) in Email templates, you can create your own variable provider and processor.
The variable provider must implement
[EntityVariablesProviderInterface](../../../EntityBundle/Twig/Sandbox/EntityVariablesProviderInterface.php)
and be registered in the DI container with the `oro_email.emailtemplate.variable_provider` tag.
The variable processor must implement
[VariableProcessorInterface](../../../EntityBundle/Twig/Sandbox/VariableProcessorInterface.php)
and be registered in the DI container with the `oro_email.emailtemplate.variable_processor` tag.

An example:

1. Create a variable provider:

```php
class MyVariablesProvider implements EntityVariablesProviderInterface
{
    public function getVariableDefinitions(string $entityClass = null): array
    {
        return [
            MyEntity::class => [
                'someVariable' => [
                    'type'  => RelationType::TO_ONE,
                    'label' => $this->translator->trans('acme.my_entity.some_variable')
                ]
            ]
        ];
    }

    public function getVariableGetters(): array
    {
        return [];
    }

    public function getVariableProcessors(string $entityClass): array
    {
        if (MyEntity::class === $entityClass) {
            return [
                'someVariable'  => [
                    'processor' => 'my_processor'
                ]
            ];
        }

        return [];
    }
}
```

2. Create a variable processor:

```php
class MyVariableProcessor implements VariableProcessorInterface
{
    public function process(string $variable, array $processorArguments, TemplateData $data): void
    {
        $someObject = new SomeObject();
        $someObject->setName('test')

        $data->setComputedVariable($variable, $someObject);
    }
}
```

3. Register variable provider and processor in the DI container:

```yaml
services:
    acme.emailtemplate.my_variable_provider:
        class: Acme\Bundle\AcmeBundle\Provider\MyVariablesProvider
        public: false
        tags:
            - { name: oro_email.emailtemplate.variable_provider, scope: entity }

    acme.emailtemplate.my_variable_processor:
        class: Acme\Bundle\AcmeBundle\Provider\MyVariableProcessor
        public: false
        tags:
            - { name: oro_email.emailtemplate.variable_processor, alias: my_processor }
```

Another way to extend the available data is to create a Twig function and register this function in the Email templates
Twig environment.

An example:

1. Create a Twig extension:

```php
use Twig\Extension\AbstractExtension;

class MyExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [new Twig\TwigFunction('some_function', [$this, 'getSomeVariableValue'])];
    }

    public function getSomeVariableValue(MyEntity $entity): array
    {
        $result = [];
        foreach ($entity->getProducts() as $product) {
            $result[] = [
                'productName' => $product->getName()
            ];
        }

        return $result;
    }
}
```

2. Register the Twig extension in the DI container:

```yaml
services:
    acme.twig.my_extension:
        class: Acme\Bundle\AcmeBundle\Twig\MyExtension
        public: false
        tags:
            - { name: twig.extension }
```

3. Create a DI compiler pass to register the created extension and function in the Email Twig Environment:

```php
class TwigSandboxConfigurationPass implements CompilerPassInterface
{
    const EMAIL_TEMPLATE_SANDBOX_SECURITY_POLICY_SERVICE_KEY = 'oro_email.twig.email_security_policy';
    const EMAIL_TEMPLATE_RENDERER_SERVICE_KEY = 'oro_email.email_renderer';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $securityPolicyDef = $container->getDefinition('oro_email.twig.email_security_policy');
        $securityPolicyDef->replaceArgument(
            4,
            array_merge($securityPolicyDef->getArgument(4), ['some_function'])
        );

        $container->getDefinition('oro_email.email_renderer')
            ->addMethodCall('addExtension', [new Reference('acme.twig.my_extension')]);
    }
}
```

4. Register the created compiler pass:

```php
class AcmeMyBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TwigSandboxConfigurationPass());

        parent::build($container);
    }
}
```

Once you complete these steps, the "some_function" Twig function becomes available in Email templates.


Basic email template structure
------------------------------

Beware that HTML email templates are passed to WYSIWYG when being edited. **WYSIWYG automatically tries to modify given HTML according to HTML specification.** Therefore text and tags that violate HTML specification, should be wrapped in HTML comment. For example, no tags or text are allowed between `<table></table>` tags except `thead`, `tbody`, `tfoot`, `th`, `tr`, `td`.

Examples:

Invalid template:
```
<table>
    <thead>
        <tr>
            <th><strong>ACME</strong></th>
        </tr>
    </thead>
    {% for item in collection %}
    <tbody>
        {% for subItem in item %}
        <tr>
            {% if loop.first %}
            <td>{{ subItem.key }}</td>
            <td>{{ subItem.value }}</td>
            {% endif %}
        </tr>
        {% endfor %}
    </tbody>
    {% endfor %}
</table>
```

Valid template:
```
<table>
    <thead>
        <tr>
            <th><strong>ACME</strong></th>
        </tr>
    </thead>
    <!--{% for item in collection %}-->
    <tbody>
        <!--{% for subItem in item %}-->
        <tr>
            <!--{% if loop.first %}-->
            <td>{{ subItem.key }}</td>
            <td>{{ subItem.value }}</td>
            <!--{% endif %}-->
        </tr>
        <!--{% endfor %}-->
    </tbody>
    <!--{% endfor %}-->
</table>
```
