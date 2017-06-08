Mediator Handlers
=================

OroUIBundle declares some mediator handlers. It's preferable to use indirect method execution with `mediator.execute()` in all components which follows Chaplin architecture. 

## Application

Handler Name | Description
------------ | -----------
`retrieveOption` | returns application's initialization option by its name
`retrievePath` | removes root prefix from passed path and returns meaningful part of path
`combineRouteUrl` | accepts path and query parts and combines url
`combineFullUrl` | accepts path and query parts and combines full url (with root prefix)
`changeURL` | accepts route and options for `Backbone.history.navigate`, allows to change url without dispatching new route

See [`oroui/js/app/application`](../../public/js/app/application.js) module for details.

## Page Controller

Handler Name | Description
------------ | -----------
`isInAction` | allows to detect if controller is in action (period of time between `'page:beforeChange'` and `'page:afterChange'` events)
`redirectTo` | perform redirect to a new location, accepts two parameters: object with location information and navigation options
`refreshPage` | reloads current page, accepts navigation options
`submitPage` | performs submit form action via save call for a model, accepts options object with packed in data

See [`oroui/js/app/controllers/page-controller`](../../public/js/app/controllers/page-controller.js) module for details

## Messenger

Handler Name | Method
------------ | -----------
`addMessage` | `messenger.addMessage`
`showMessage` | `messenger.notificationMessage`
`showFlashMessage` | `messenger.notificationFlashMessage`
`showErrorMessage` | `messenger.showErrorMessage`

See [`oroui/js/messenger`](../../public/js/messenger.js) module for details

## Widgets (Widget Manager)

Handler Name | Method | Description
------------ | ------ | -----------
`widgets:getByIdAsync` | `widgetManager.getWidgetInstance` | asynchronously fetches widget instance by widget id
`widgets:getByAliasAsync` | `widgetManager.getWidgetInstanceByAlias` | asynchronously fetches widget instance its alias

See [`oroui/js/widget-manager`](../../public/js/widget-manager.js) module for details

## PageLoadingMaskView

Handler Name | Description
------------ | -----------
`showLoading` | shows loading mask
`hideLoading` | hides loading mask

## Layout

Handler Name | Description
------------ | -----------
`layout:init` | initializes proper widgets and plugins in the container
`layout:dispose` | removes some plugins and widgets from child elements of the container

## DebugToolbarView

Handler Name | Description
------------ | -----------
`updateDebugToolbar` | accepts XHR object, retrieves data and updates debug bar
