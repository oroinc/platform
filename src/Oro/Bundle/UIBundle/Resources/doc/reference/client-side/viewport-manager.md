# Viewport Manager

Viewport manager contains a collection of available screen types that can be used on the theme.
Also responsible for triggering event `viewport:change` through mediator, when change the type of screen.
Possibility subscribe to event `viewport:change` in view and create a logic based on the viewport changes.
For example [DOM Relocation View](../../../../../../../../../commerce/src/Oro/Bundle/FrontendBundle/Resources/doc/components/dom-relocation-view.md) already implemented functionality based by Viewport Manager.

## Screen Map
By default these settings for list of screen types synchronized with scss breakpoints.

```scss
// Desktop Media Breakpoint
$breakpoint-desktop: 1100px;

// iPad mini 4 (768*1024), iPad Air 2 (768*1024), iPad Pro (1024*1366)
$breakpoint-tablet: $breakpoint-desktop - 1px;
$breakpoint-tablet-small: 992px;

// iPhone 4s (320*480), iPhone 5s (320*568), iPhone 6s (375*667), iPhone 6s Plus (414*763)
$breakpoint-mobile-big: 767px;
$breakpoint-mobile-landscape: 640px;
$breakpoint-mobile: 414px;
$breakpoint-mobile-small: 360px;

$oro_breakpoints: (
    'desktop': '(min-width: ' + $breakpoint-desktop + ')',
    'tablet': '(max-width: ' +  $breakpoint-tablet + ')',
    'strict-tablet': '(max-width: ' +  $breakpoint-tablet + ') and (min-width: ' + ($breakpoint-tablet-small + 1) + ')',
    'tablet-small': '(max-width: ' +  $breakpoint-tablet-small + ')',
    'strict-tablet-small': '(max-width: ' +  $breakpoint-tablet-small + ') and (min-width: ' + ($breakpoint-mobile-landscape + 1) + ')',
    'mobile-landscape': 'screen and (max-width: ' +  $breakpoint-mobile-landscape + ')',
    'strict-mobile-landscape': '(max-width: ' +  $breakpoint-mobile-landscape + ') and (min-width: ' + ($breakpoint-mobile + 1) + ')',
    'mobile': '(max-width: ' + $breakpoint-mobile + ')',
    'mobile-big': '(max-width: ' +  $breakpoint-mobile-big + ')',
);

```

[Default scss breakpoints](../../../public/blank/scss/settings/partials/_breakpoints.scss) converted to the following array:
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
        name: 'strict-tablet',
        max: 1099,
        min: 993
    },
    {
        name: 'tablet-small',
        max: 992
    },
    {
        name: 'strict-tablet-small', 
        max: 992,
        min: 641
    },
    {
        name: 'mobile-big', 
        max: 767
    },
    {
        name: 'strict-mobile-big', 
        max: 767,
        min: 641,
    },
    {
        name: 'mobile-landscape',
        max: 640
    },
    {
        name: 'strict-mobile-landscape',
        max: 640,
        min: 415
    },
    {
        name: 'mobile',
        max: 414
    },
    {
        name: 'mobile-small',
        max: 360
    }
]
```

#####You can override these breakpoints [via scss variables](https://github.com/oroinc/customer-portal/blob/master/src/Oro/Bundle/FrontendBundle/Resources/doc/frontendStylesCustomization.md#how-to-change-media-breakpoints)

###Overriding via js module config for specific theme
#####This config will has the highest priority

```twig
{% import '@OroAsset/Asset.html.twig' as Asset %}
{{ Asset.js_modules_config({
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
                    name: 'desktop-hd',
                    max: 1920
                }
            ]
        }
}); }}

```


####To delete inherited screen type need set `skip: true` for a specific screen name
```twig
{% import '@OroAsset/Asset.html.twig' as Asset %}
{{ Asset.js_modules_config({
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
}); }}
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

Set max *viewport* size for screen type

## min
**Type:** Number

Set min *viewport* size for screen type

## Events

### viewport:change
**Event Data:** Object

**Data Structure:**

* **type:** Object

Current viewport screen type.

* **width:** Number

Current viewport width.
