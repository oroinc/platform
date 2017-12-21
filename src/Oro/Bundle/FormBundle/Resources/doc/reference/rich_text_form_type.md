# Rich Text Form Type

## Overview

Rich Text editor based on [TinyMCE](https://www.tinymce.com/).

## HTML Purifier

Rich Text editor use [HTML Purifier](http://htmlpurifier.org/) that helps to prevent XSS attacks.
List of allowed HTML tags you can find [here](../../config/oro/app.yml).

### Example how to allow own HTML tags

```yaml
# src/Acme/Bundle/DemoBundle/Resources/config/oro/app.yml

oro_form:
    wysiwyg:
        html_allowed_elements:
            div:
                attributes:
                    - data-url
                    - data-src
                    - data-value
```
