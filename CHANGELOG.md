CHANGELOG for 1.4.0-RC1
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.4.0-RC1 versions.
* 1.4.0-RC1 (2014-09-30)
 * The re-introduction of Channels.
We started the implementation of a new vision for the Channels in 1.3 version and now we bring Channels back, although under a new definition.
The general idea behind channels may be explained as follows: a channel in OroCRM represents an outside source customer and sales data, where "customer" and "sales" must be understood in the broadest sense possible. Depending on the nature of the outside source, the channel may or may not require a data integration.
This new definition leads to multiple noticeable changes across the system.
 * Integration management.
Albeit the Integrations grid still displays all integrations that exist in the system, you now may create only "non-customer" standalone integrations, such as Zendesk integration. The "customer" integrations, such as Magento integration, may be created only in scope of a channel and cannot exist without it.
 * Marketing lists.
Marketing lists serve as the basis for marketing activities, such as email campaigns (see below). They represent a target auditory of the activity—that is, people, who will be contacted when the activity takes place. Marketing lists have little value by themselves; they exist in scope of some marketing campaign and its activities.
Essentially, marketing list is a segment of entities that contain some contact information, such as email or phone number or physical address. Lists are build based on some rules using Oro filtering tool. Similarly to segments, marketing lists can be static or dynamic; the rules are the same. The user can build marketing lists of contacts, Magento customers, leads, etc.
In addition to filtering rules, the user can manually tweak contents of the marketing list by removing items ("subscribers") from it. Removed subscribers will no longer appear in the list even if they fit the conditions. It is possible to move them back in the list, too.
Every subscriber can also unsubscribe from the list. In this case, he will remain in the list, but will no longer receive email campaigns that are sent to this list. Note that subscription status is managed on per-list basis; the same contact might be subscribed to one list and unsubscribed from another.
 * Email campaigns.
Email campaign is a first example of marketing activity implemented in OroCRM. The big picture is following: Every marketing campaign might contain multiple marketing activities, e.g. an email newsletter, a context ad campaign, a targeted phone advertisement. All these activities serve the common goal of the "big" marketing campaign.
In its current implementation, email campaign is a one-time dispatch of an email to a list of subscribers. Hence, the campaign consists of three basic parts:
Recipients—represented by a Marketing list.
Email itself—the user may choose a template, or create a campaign email from scratch.
Sending rules—for now, only one-time dispatch is available.
Email campaign might be tied to a marketing campaign, but it might exist on its own as well.
 * Improved Email templates.
Previously, email templates were used only for email notifications. Now their role is expanded: it is now possible to use templates in email activities to create a new email from the template, and for email campaigns.
Support for variables in templates was extended: in addition to "contextual" variables that were related to attributes of the template entity, templates may include "system-wide" variables like current user's first name, or current time, or name of the organization. It is also possible to create a "generic" template that is not related to any entity; in this case it may contain only system variables.
New templates are subject to ACL and have owner of user type.
 * Other improvements
 <ul><li>Multiple improvements to Web API</li>
 <li>A new implementation of option sets</li>
 <li>Improved grids</li></ul>
 * Community requests.
Here is the list of Community requests that were addressed in this version.
Features & improvements
  <ul><li>#50 Add the way to filter on empty fields</li>
  <li>#116 Add custom templates to workflow transitions</li>
  <li>#118 Extending countries</li>
  <li>#136 Console command for CSV import/export</li>
  <li>#149 New "link" type for datagrid column format</li></ul>
 * Bugs fixed
  <ul><li>#47 Problems with scrolling in iOS 7</li>
  <li>#62 Problems with the Recent Emails widget</li>
  <li>#139 Error 500 after removing unique key of entity</li>
  <li>#158 Update doctrine version to 2.4.4</li></ul>

CHANGELOG for 1.3.1
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.3.1 versions.

* 1.3.1 (2014-08-14)
 * Minimum PHP version: PHP 5.4.9
 * PostgreSQL support
 * Fixed issue: Not entire set of entities is exported
 * Fixed issue: Page crashes when big value is typed into the pagination control
 * Fixed issue: Error 500 on Schema update
 * Other minor issues

CHANGELOG for 1.3.0
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.3.0 versions.

* 1.3.0 (2014-07-23)
 * Redesign of the Navigation panel and left-side menu bar
 * Website event tracking
 * Processes
 * New custom field types for entities: File and Image
 * New control for record lookup (relations)
 * Data import in CSV format

CHANGELOG for 1.2.0
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.2.0 versions.

* 1.2.0 (2014-05-28)
 * Ability to delete Channels
 * Workflow view
 * Reset of Workflow data
 * Line charts in Reports
 * Fixed issues with Duplicated emails
 * Fixed Issue Use of SQL keywords as extended entity field names
 * Fixed Issue Creating one-to-many relationship on custom entity that inverses many-to-one relationship fails
 * Fixed Community requests

CHANGELOG for 1.2.0-rc1
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.2.0 RC1 versions.

* 1.2.0 RC1 (2014-05-12)
 * Ability to delete Channels
 * Workflow view
 * Reset of Workflow data
 * Fixed issues with Duplicated emails
 * Fixed Issue Use of SQL keywords as extended entity field names
 * Fixed Issue Creating one-to-many relationship on custom entity that inverses many-to-one relationship fails

CHANGELOG for 1.1.0
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.1.0 versions.

* 1.1.0 (2014-04-28)
 * Dashboard management
 * Fixed problem with creation of on-demand segments
 * Fixed broken WSSE authentication
 * Fixed Incorrectly calculated totals in grids

CHANGELOG for 1.0.1
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.1 versions.

* 1.0.1 (2014-04-18)
 * Issue #3979 � Problems with DB server verification on install
 * Issue #3916 � Memory consumption is too high on installation
 * Issue #3918 � Problems with installation of packages from console
 * Issue #3841 � Very slow installation of packages
 * Issue #3916 � Installed application is not working correctly because of knp-menu version
 * Issue #3839 � Cache regeneration is too slow
 * Issue #3525 � Broken filters on Entity Configuration grid
 * Issue #3974 � Settings are not saved in sidebar widgets 
 * Issue #3962 � Workflow window opens with a significant delay
 * Issue #2203 � Incorrect timezone processing in Calendar
 * Issue #3909 � Multi-selection filters might be too long
 * Issue #3899 � Broken link from Opportunity to related Contact Request

CHANGELOG for 1.0.0
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0 versions.

* 1.0.0 (2014-04-01)
 * Workflow management UI
 * Segmentation
 * Reminders
 * Package management
 * Page & Grand totals for grids
 * Proper formatting of Money and Percent values
 * Configurable Sidebars
 * Notification of content changes in the Pinbar

CHANGELOG for 1.0.0-rc3
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-rc3 versions.

* 1.0.0-rc3 (2014-02-25)
 * Embedded forms
 * CSV export

CHANGELOG for 1.0.0-rc2
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-rc2 versions.

* 1.0.0-rc2 (2014-01-30)
 * Package management
 * Translations management
 * FontAwesome web-application icons

CHANGELOG for 1.0.0-rc1
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-rc1 versions.

* 1.0.0-rc1 (2013-12-30)
 * Table reports creation wizard
 * Manageable labels of entities and entity fields
 * Record updates notification
 * Sidebars widgets
 * Mobile Web
 * Package Definition and Management
 * Themes
 * Notifications for owners
 * --force option for oro:install
 * Remove old Grid bundle
 * Basic dashboards

CHANGELOG for 1.0.0-beta5
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-beta5 versions.

* 1.0.0-beta5 (2013-12-05)
 * ACL management in scope of organization and business unit
 * "Option Set" Field Type for Entity Field
 * Form validation improvements
 * Tabs implementation on entity view pages
 * Eliminated registry js-component
 * Implemented responsive markup on most pages

CHANGELOG for 1.0.0-beta4
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-beta4 versions.

* 1.0.0-beta4 (2013-11-21)
 * Grid refactoring
 * Form validation improvements
 * Make all entities as Extended
 * JavaScript Tests
 * End support for Internet Explorer 9 

CHANGELOG for 1.0.0-beta3
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-beta3 versions.

* 1.0.0-beta3 (2013-11-11)
 * Upgrade the Symfony framework to version 2.3.6
 * Oro Calendar
 * Email Communication
 * Removed bundle dependencies on application
 * One-to-many and many-to-many relations between extended/custom entities
 * Localizations and Internationalization of input and output

CHANGELOG for 1.0.0-beta2
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-beta2 versions.

* 1.0.0-beta2 (2013-10-28)
 * Minimum PHP version: PHP 5.4.4
 * Installer enhancements
 * Automatic bundles distribution for application
 * Routes declaration on Bundles level
 * System Help and Tooltips
 * RequireJS optimizer utilization
 * ACL Caching

CHANGELOG for 1.0.0-beta1
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-beta1 versions.

* 1.0.0-beta1 (2013-09-30)
 * New ACL implementation
 * Emails synchronization via IMAP
 * Custom entities and fields in usage
 * Managing relations between entities
 * Grid views

CHANGELOG for 1.0.0-alpha6
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-alpha6 versions.

* 1.0.0-alpha6 (2013-09-12)
 * Maintenance Mode
 * WebSocket messaging between browser and the web server
 * Asynchronous Module Definition of JS resources
 * Added multiple sorting for a Grid
 * System configuration

CHANGELOG for 1.0.0-alpha5
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-alpha5 versions.

* 1.0.0-alpha5 (2013-08-29)
 * Custom entity creation
 * Cron Job
 * Record ownership
 * Grid Improvements
 * Filter Improvements
 * Email Template Improvements
 * Implemented extractor for messages in PHP code
 * Removed dependency on SonataAdminBundle
 * Added possibility to unpin page using pin icon

CHANGELOG for 1.0.0-alpha4
===================
This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-alpha4 versions.

* 1.0.0-alpha4 (2013-07-31)
 * Upgrade Symfony to version 2.3
 * Entity and Entity's Field Management
 * Multiple Organizations and Business Units
 * Transactional Emails
 * Email Templates
 * Tags Management 
 * Translations JS files
 * Pin tab experience update
 * Redesigned Page Header
 * Optimized load time of JS resources

CHANGELOG for 1.0.0-alpha3
===================

This changelog references the relevant changes (new features, changes and bugs) done in 1.0.0-alpha3 versions.

* 1.0.0-alpha3 (2013-06-27)
 * Placeholders
 * Developer toolbar works with AJAX navigation requests
 * Configuring hidden columns in a Grid
 * Auto-complete form type
 * Added Address Book
 * Localized countries and regions
 * Enhanced data change log with ability to save changes for collections
 * Removed dependency on lib ICU

