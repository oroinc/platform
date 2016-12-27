#Viewport Manager

Viewport manager contains a collection of available screen types that can be used on the theme.
Also responsible for triggering event `viewport:change` through mediator, when change the type of screen.
Possibility subscribe to event `viewport:change` in view and create a logic based on the viewport changes.
For example [DOM Relocation View](../../../../../../../../../commerce/src/Oro/Bundle/FrontendBundle/Resources/doc/components/dom-relocation-view.md) already implemented functionality based by Viewport Manager.

##Screen Map
Settings for list of screen types. By default has parameters:
```javascript
screenMap: [
    {
        name: 'desktop',
        min: 1100
    },
    {
        name: 'tablet',
        max: 1099
    },
    {
        name: 'tablet-small',
        max: 992
    },
    {
        name: 'mobile-landscape',
        max: 640
    },
    {
        name: 'mobile',
        max: 414
    }
]
```
Also can be overridden by require config on specific theme.
```javascript
require({
    config: {
        'oroui/js/viewport-manager': {
            screenMap: [
                {
                    name: 'tablet',
                    min: 640
                },
                {
                    name: 'desktop',
                    min: 1260
                },
                {
                    name: 'easter-egg',
                    min: 800,
                    max: 1600
                }
            ]
        }
    }
});
```


##Screen Types
Screen type used for describe some viewport size range.
It provides an opportunity to describe the parameters like `name`, `min`/`max` size of screen type.
For example:
```javascript
{
    name: 'screen-type',
    max: 1024,
    min: 640
}
```
###name
**Type:** String

Set name for screenType.

###max
**Type:** Number

**Default:** Infinity

Set max *viewport* size for screen type

###min
**Type:** Number

**Default:** 0

Set min *viewport* size for screen type

Also you can use only one of min/max options, other will get default value:
```javascript
{
    name: 'screen-type',
    min: 640
}
```
So it works like `@media` rules in css
```css
@media (min-width: 640px) {
    /* Some code */
}
```
##Events

###viewport:change
**Event Data:** Object

**Data Structure:**

* **screenTypes:** Object

Has {key: value} object, where `key` is screen type name and `value` is boolean.
`value` is true if viewport size placed in screen type range.

* **width:** Number

Current viewport width.
