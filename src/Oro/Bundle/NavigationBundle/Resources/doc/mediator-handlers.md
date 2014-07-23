Mediator Handlers
=================

OroNavigationBundle declares some mediator handlers.

##Content Manager

###Cache management handler

Handler Name | Method | Description
------------ | ------ | -----------
`pageCache:init` | `contentManager.init` | setups content management component, sets initial URL
`pageCache:add` | `contentManager.add` | add current page to permanent cache
`pageCache:get` | `contentManager.get` | fetches cache data for url, by default for current url
`pageCache:remove` | `contentManager.remove` | clear cached data, by default for current url

###State management handler

Handler Name | Method | Description
------------ | ------ | -----------
`pageCache:state:save` | `contentManager.saveState` | saves state of a page component in a cache
`pageCache:state:fetch` | `contentManager.fetchState` | fetches state of a page component from cached page
`pageCache:state:check` | `contentManager.checkState` | check if state's GET parameter (pair key and hash) reflects current URL

###Helper methods handler

Handler Name | Method | Description
------------ | ------ | -----------
`currentUrl` | `contentManager.currentUrl` | returns current url (path + query)
`compareUrl` | `contentManager.compareUrl` | retrieve meaningful part of path from url and compares it with reference path (or with current if last ont is undefined)

See [`oronavigation/js/content-manager`](../public/js/content-manager.js) module for details.
