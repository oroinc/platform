Loading Mask View
=================

The loading mask is used for visualizing loading process and blocking some page functionality with transparent overlay to prevent the influence on loading process

The `LoadingMaskView` is an extend of `BaseView` (that inherit all functionality from `Chaplin.View` and `Backbone.View`).

Initialization
--------------
`LoadingMaskView` is rendered automatically once it initialized (has defined property `autoRender: true`). To create an instance it is sufficient to pass one option -- the container (the element that you want to cover).

```javascript
    var loadingMask = new LoadingMaskView({
        container: $myElement
    });

```

Other `LoadingMaskView` specific options that might be passed to constructor are:

 - `loadingHint` is string, the short message that will be show to user during loading process, `'Loading...'`
 - `hideDelay` is number in milliseconds or false, allows to hide loading mask with delay

```javascript
 var loadingMask = new LoadingMaskView({
     container: $myElement,
     loadingHint: 'Saving...',
     hideDelay: 25
 });

```

How to Usage
------------
```javascript

    /**
     * Shows loading mask
     */
    loadingMask.show();

    /**
     * Shows the mask with specific loading hint
     */
    loadingMask.show('Sending...');

    /**
     * Hides loading mask
     */
    loadingMask.hide();

    /**
     * If loading mask was defined with some `hideDelay`,
     * this flag allows to hide loading mask instantly for this time
     */
    loadingMask.hide(true);

    /**
     * Toggles loading mask
     * (shows if it was hidden and hides if it was shown)
     */
    loadingMask.toggle();

    /**
     * Same as show();
     */
    loadingMask.toggle(true);

    /**
     * Same as hide();
     */
    loadingMask.toggle(false);

    /**
     * Returns current state of loading mask
     *  - true if it is shown
     *  - false if it is hidden
     */
    loadingMask.isShown();

    /**
     * Allows to change loading hint for the instance
     */
    loadingMask.setLoadingHint('Processing...');
```

Markup
------
The markup of loading mask is build the way that allows to show only top level loading mask. That means, if you cover some container with loading mask and in same time inside your container some element has own loading mask shown -- user will see only top level loading process. And once top level mask is hidden, user will see remaining mask until it get hidden as well.
