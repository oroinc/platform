Email templates
===============

Any bundle can define it's own templates using Data Fixtures.
To achieve this - add a fixture in SomeBundle\DataFixtures\ORM folder that extends Oro\Bundle\EmailBundle\DataFixtures\ORM\AbstractEmailFixture
abstract class and implements the only method - getEmailsDir:
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

Also there are additional Twig functions, filters, and tags, that registered and allowed to be used in Email Templates. The full list of these functions, filters, and tags you can find by searching for mentions of the 'oro_email.twig.email_security_policy' service in Oro Application CompillerPass Classes or see on the [Email Templates](https://oroinc.com/b2b-ecommerce/doc/current/admin-guide/email/email-templates) section of the Admin Guide.

Extend available data in email templates
----------------------------------------

Currently, there is only one way to extend available data (variables) in Email templates: to create a Twig function and register this function in the Email templates Twig environment.

An example on how to do this - the "order_line_items" twig function:

* There is a Class of [custom Twig Extension](https://symfony.com/doc/3.4/templating/twig_extension.html) [Oro\Bundle\CheckoutBundle\Twig\LineItemsExtension](https://github.com/laboro/dev/blob/master/package/commerce/src/Oro/Bundle/CheckoutBundle/Twig/LineItemsExtension.php#L47) that declares the Twig function "order_line_items"
* This class [registered, actually, as the Twig Extension in DI container](https://github.com/laboro/dev/blob/master/package/commerce/src/Oro/Bundle/CheckoutBundle/Resources/config/services.yml#L115)
* This Twig Extension added to the Email Twig Environment in the [Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass](https://github.com/laboro/dev/blob/master/package/commerce/src/Oro/Bundle/CheckoutBundle/DependencyInjection/Compiler/TwigSandboxConfigurationPass.php#L33)
* The "order_line_items" function [registered as allowed in the Email Templates](https://github.com/laboro/dev/blob/master/package/commerce/src/Oro/Bundle/CheckoutBundle/DependencyInjection/Compiler/TwigSandboxConfigurationPass.php#L24)

After these steps, the Twig function "order_line_items" became available for usage in Email templates.

Please note, that in Emails Templates could be iterated only data sets that contained in plain PHP arrays, not objects or collections. Thereby the custom twig function has to return an array if you need to iterate through this dataset in your Email Templates.

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