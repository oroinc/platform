Error Handler
=============

Error Handler provides a generalized system display of errors in the application. It allows to show different error formats for `prod` and `dev` environment.

## How to use:

Import `oroui/js/error` into your component:

```javascript
define(function(require) {
    'use strict';

    var error = require('oroui/js/error');

    /* Another code */
});
```

To display an error message, use the methods provided in Error Handler

### showError
Options:
* `context`: {Object|Error}
* `errorMessage`: {String|null} _(optional)_

Description:

Show an error both in the UI Flash Message and Console 

### showErrorInUI
Options:
* `context`: {Object|Error|String}

Description:

Show an error only in UI Flash Message.
If `context` is Error, then in `prod` env the output is a simple message, but in `dev` env additional debug information can be shown.

### showErrorInConsole
Options:
* `context`: {Object|Error}

Description:

Show an error only in Console

### showFlashError
Options:
* `message`: {String}

Description:

Show a simple Error Flash Message

### modalHandler
Options:
* `xhr`: {Object|Error}

Description:

Show an error only in modal

## Defaults options

The following options can be redefined when calling the `error` module

### headerServerError

* **Type:** {String}
* **Default:** 'Server error'

Description:

Used as the modal title in modalHandler method if the error comes from the server

### headerUserError

* **Type:** {String}
* **Default:** 'User input error'

Description:

Used as the modal title in modalHandler method if it is a user error

### message

* **Type:** {String}
* **Default:** 'There was an error performing the requested operation. Please try again or contact us for assistance.'

Description:

Default error text message

### loginRoute

* **Type:** {String}
* **Default:** 'oro_user_security_login'

Description:

Specifies the redirect url if XHR status is 401

## Error Handler and `$.ajax()` errors

By default, Error Handler catches and shows all default errors provided by `$.ajax()`.
However, developers can change or disable this behavior by adding the `errorHandlerMessage` option into ajax settings.

### errorHandlerMessage
* **Type:** {Boolean|String|Function}
* **Default:** `true`

Disable ajax Error Flash Message:
```javascript
$.ajax({
    url: 'test',
    errorHandlerMessage: false
});
```

Set a custom error message:
```javascript
$.ajax({
    url: 'test',
    errorHandlerMessage: "Custom Error Message"
});
```

Callback function can also be used for `errorHandlerMessage`:
```javascript
$.ajax({
    url: 'test',
    errorHandlerMessage: function(event, xhr, settings) {
        // Suppress error if it's 404 response
        return xhr.status !== 404;
    }
});
```
