# Client side form validation
## Setup validation rules for form fields
Main aim of development client side validation was to support same validation annotation which is used for server side - [Symfony validation](http://symfony.com/doc/current/book/validation.html). Once `validation.yml` is created, all rules get translated to fields `data-validation` attribute, e.g.:
```yml
Bundle\UserBundle\Entity\User:
    properties:
        username:
            - NotBlank:     ~
            - Length:
                min:        3
                max:        255
```
will be translated to
```html
<input name="user_form[username]"
    data-validation="{&quot;NotBlank&quot;:null,&quot;Length&quot;:{&quot;min&quot;:3,&quot;max&quot;:255}}">
```
This `data-validation` is supported by client side validation. Which is, by the way, extended version of popular [jQuery Validation Plugin](http://jqueryvalidation.org/).

## Validation rules
Client side validation method is RequireJS module, which should export an array with three values:
 1. Methods name
 2. Validation function
 3. Error message or function which defines message and returns it

Trivial validation rule module would look like:
```js
define(['underscore', 'orotranslation/js/translator']
function (_, __) {
    'use strict';

    var defaultParam = {
        message: 'Invalid input value'
    };

    return [
        'ValidationMethodRule',

        /**
         * @param {string|undefined} value
         * @param {Element} element
         * @param {?Object} param
         * @this {jQuery.validator}
         * @returns {boolean|string}
         */
        function (value, element, param) {
            return true;
        },

        /**
         * @param {Object} param
         * @param {Element} element
         * @this {jQuery.validator}
         * @returns {string}
         */
        function (param, element) {
            param = _.extend({}, defaultParam, param);
            return __(param.message);
        }
    ]
});
```

## Loading custom validation rules
To load custom validator, just call `$.validator.loadMethod` with the name of RequireJS module, which exports validation method:
```js
$.validator.loadMethod('my/validation/method')
```
After it, form fields which have this constraint will be processed by this validation method.

## Validation for optional group
In case you have one form which saves several different entities at once (e.g. contact entity + address sub-entity), it useful to mark container of sub-entity fields elements with attribute `data-validation-optional-group`.
```
<form>
|
+--<fieldset>
|  +--<input>
|  +--<input>
|  +--<input>
|
+--<fieldset data-validation-optional-group>
   +--<input>
   +--<input>
   +--<input>
```
After that, validation for sub-entinty works only if some of fields is not blank. Otherwise it ignores all validation rules for fields elements of sub-entity.

### Override of optional validation logic
In case if you want to customize "optional validation group" behaviour you can override a handler which is responsible for
handle field changes in specific optional validation group. In this case you need:
1) add custom handler to requirejs.yml
```
config:
    paths:
        example/js/custom-handler: 'bundles/example/js/custom-handler.js'
```

Custom optional validation handler should have two methods: initialize and handle.
Method "Initialise" is responsible for update validation state for "optional validation group" after it will be loaded to the page.
Method "Handle" is responsible for update "optional validation group" validation state after the descendant field will be changed.

You can have any level of "optional validation group" inheritance in your page. In case if your field has more than one "optional validation group" ancestor,
all "optional validation group" handlers will be called from closest ancestor to root by default.
This behaviour is configurable, you can simply return `true` or `false` in your custom "Handle" method.

2) add data attribute to validation group
```
   +--<fieldset data-validation-optional-group data-validation-optional-group-handler="example/js/custom-handler">
      +--<input>
      +--<input>
      +--<input>
```
3) all custom handlers should be preloaded to avoid situation when form was loaded but handler was not. To avoid such 
situations you should add custom application module
```
requirejs.yml:
    paths:
        example/js/custom-handler: 'bundles/example/js/custom-handler.js'
    config:
        appmodules:
            - example/js/custom-handler
```

## Ignore validation section
There are cases when developer need to suppress validation for some field or group of fields. It can be done over `data-validation-ignore` attribute of container element. It works the same way as with `data-validation-optional-group` attribute, except validator omit these fields even if they have some value.
```
+<form>
|
+--<fieldset>
|  +--<input>
|  +--<input>
|  +--<input>
|
+--<fieldset data-validation-ignore>
   +--<input>
   +--<input>
   +--<input>
```
This attribute is checked in each validation cycle, so developer can add/remove it in runtime to get required behavior.

## Conformity server side validations to client once
```
+--------------+---------+-----+-------------------------------+---------+
| Server side  | Symfony | Oro |       Client side             | Coment. |
+--------------+---------+-----+-------------------------------+---------+
| All          |    √    |     |                               |   (2)   |
| Blank        |    √    |     |                               |   (2)   |
| Callback     |    √    |     |                               |   (2)   |
| Choice       |    √    |     |                               |   (2)   |
| Collection   |    √    |     |                               |   (2)   |
| Count        |         |  √  | oroform/js/validator/count    |   (1)   |
| Country      |    √    |     |                               |         |
| DateTime     |    √    |  √  | oroform/js/validator/datetime |         |
| Date         |    √    |  √  | oroform/js/validator/date     |         |
| Email        |    √    |     | oroform/js/validator/email    |         |
| False        |    √    |     |                               |   (2)   |
| File         |    √    |     |                               |   (2)   |
| Image        |    √    |     |                               |   (2)   |
| Ip           |    √    |     |                               |         |
| Language     |    √    |     |                               |         |
| Length       |    √    |     | oroform/js/validator/length   |         |
| Locale       |    √    |     |                               |         |
| MaxLength    |    √    |     |                               |         |
| Max          |    √    |  √  |                               |         |
| MinLength    |    √    |     |                               |         |
| Min          |    √    |  √  |                               |         |
| NotBlank     |    √    |     | oroform/js/validator/notblank |   (3)   |
| NotNull      |    √    |  √  | oroform/js/validator/notnull  |   (3)   |
| Null         |    √    |     |                               |   (2)   |
| Range        |    √    |  √  | oroform/js/validator/range    |         |
| Regex        |    √    |     | oroform/js/validator/regex    |         |
| Repeated     |    √    |     | oroform/js/validator/repeated |         |
| SizeLength   |    √    |     |                               |         |
| Size         |    √    |  √  |                               |         |
| Time         |    √    |     |                               |         |
| True         |    √    |     |                               |   (2)   |
| Type         |    √    |     | oroform/js/validator/type     |   (4)   |
| UniqueEntity |    √    |     |                               |         |
| Url          |    √    |     | oroform/js/validator/url      |         |
+--------------+---------+-----+-------------------------------+---------+
```

 1. supports only group of checkboxes with same name (like `user[role][]`)
 2. can't be supported on client side
 3. alias for `required` validator (standard jQuery.validate)
 4. supports only integer type
