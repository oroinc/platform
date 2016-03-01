Mode Extension
==============

This extensions provide ability to work with grid in different modes. There are two supported modes:

- **server** (default) - all manipulations with data performed on backend side, grid receives data via AJAX requests.
- **client** - all manipulations with data performed on frontend side, no AJAX requests required. *Notice:* Filters are not supported by client mode for now.

Configuration example:
---------------------

This grid will be rendered and processed in client mode:

```
    account-account-user-grid:
        options:
            mode: client
        ...
```
