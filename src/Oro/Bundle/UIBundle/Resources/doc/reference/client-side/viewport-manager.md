# Viewport Manager

Viewport manager contains a collection of available screen types that can be used on the theme.
Also responsible for triggering event `viewport:change` through mediator, when change the type of screen.
Possibility subscribe to event `viewport:change` in view and create a logic based on the viewport changes.
For example [DOM Relocation View](../../../../../../../../../commerce/src/Oro/Bundle/FrontendBundle/Resources/doc/components/dom-relocation-view.md) already implemented functionality based by Viewport Manager.

## Screen Map
Settings for list of screen types. By default has parameters:
```javascript
screenMap: [
    {
        name: 'desktop',
        max: Infinity
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
                    max: 640
                },
                {
                    name: 'desktop',
                    max: 1260
                },
                {
                    name: 'easter-egg',
                    max: 1600
                }
            ]
        }
    }
});
```
To delete inherited screen type need set `skip: true` for a specific screen name
```javascript
require({
    config: {
        'oroui/js/viewport-manager': {
            screenMap: [
                {
                    name: 'tablet',
                    skip: true
                },
                {
                    name: 'desktop',
                    max: 1260
                }
            ]
        }
    }
});
```


## Screen Types
Screen type used for describe some viewport size range.
It provides an opportunity to describe the parameters like `name`, `max` size of screen type.
For example:
```javascript
{
    name: 'screen-type',
    max: 1024
}
```
### name
**Type:** String

Set name for screen type.

### max
**Type:** Number

**Default:** Infinity

Set max *viewport* size for screen type

## Events

### viewport:change
**Event Data:** Object

**Data Structure:**

* **type:** Object

Current viewport screen type.

* **width:** Number

Current viewport width.
