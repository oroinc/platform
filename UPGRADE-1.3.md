UPGRADE FROM 1.2 to 1.3
=======================

### General
*	Activity bundle was added
*	Attachment bundle was added
*	Note bundle was added
*	Tracking bundle was added
*	Address bundle changed:
	*	Country form type and Region form type now have option `random_id` equal to true by default
	*	Remove normalizers because we don’t need them anymore
	*	Add `region_name` virtual field for AbstractAddress entity. It’s done for use  `region_name` field only  in filters and reports instead of using `AbstractAddress:: regionText` field and relation to region dictionary table separate.
*	Chart bundle changed:
	*	Add `ChartOptionsBuilder` class. It help build chart options. We moved this logic from `getChartOptions` method  in `OroReportBundle:Report` entity.
*	Cron bundle changed:
	*	Remove `RaiseExceptionLogger` - not used anywhere 
	*	Move `OutputLogger` to Oro Log Component 
	*	Move dump logic to `TranslationPackDumper` from `TranslationDump` command
*	Data Audit bundle changed:
	*	Modify `change_history_block` placeholder `audit_entity_class` not need to be define anymore “Change History” link will be shown if entity is auditable.
*	Data Grid bundle changed:
	*	Add `GroupConcat`  custom DQL function. Used for concatenation contact groups.
*	Distribution bundle changed:
	*	Add php v.5.3 changed for `OroKernel` class for correct run install page with php v.5.3 installed
	*	Add check php version before boot in `OroKernel` to prevent boot application page with php v.5.3 installed.
*	Email bundle changed:
	*	Make email entity extend
	*	Add `EmailHolderHelper` class. It help receive email address  from object.
	*	Add `oro_get_email` twig function. It gets the email address of the given object.
* Entity Config bundle changed:
	* Add FieldAccessor class. Useful for access to object fields.
* Import Export bundle changed:
	* Add `EntityNameAwareInterface`. Interface used to work with entity class.
	* Add `EntityNameAwareProcessor`. Interface used to work with entity class inside processors. Aggregates ProcessorInterface and EntityNameAwareInterface
* Installer bundle changed:
	* Remove unused `'oro:platform:check-requirements'` commnd
	* Remove unused `RequirementsListener` class and `RequirementsHelper` class
	* Remove not needed `ChannelFormTwoWaySyncSubscriber`
	* Rename `ChannelDeleteProviderInterface` -> `DeleteProviderInterface`
	* Add `RestClientInterface` and `GuzzleRestClient`. Realisation of rest client based on Guzzle http client.
	* Add `AbstractRestTransport` class - base class for rest transports
	* Remove `SimpleChannelType` and SimpleTransport because it is not used.
* Navigation bundle changed:
	* Remove navigation.js. In exchange you can use events through mediator. An example, some new events: `'page:beforeChange'`, `'page:afterChange'`, `'page:request'`, etc.
* Organization bundle changed:
	* Add Organization select form type `'oro_organization_select'`.
* Requirejs bundle changed:
	* Removed `requirejs_config_extend` placeholder.
* Security bundle changed:
	* Add to AclHelper::apply possibility to not check entity relations.
* Ui bundle changed:
	* Add Chaplin 1.0.0 js librarie and use it in routing etc.
	* Add `'oro_sort_by'` twig filter. It sorts an array by specified property
* User bundle changed:
	* Remove `UserNormalizer`.
* Windows bundle changed:
	* Add default forbidden error handler for `oro.DialogWidget`.
