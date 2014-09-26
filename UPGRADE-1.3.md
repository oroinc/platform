UPGRADE FROM 1.2 to 1.3
=======================

### General
*	Activity bundle has been added.
*	Attachment bundle has been added.
*	Note bundle has been added.
*	Tracking bundle has been added.
*	Address bundle has been modified:
	*	Country and Region form types now have `random_id` options set to true by default.
	*	Normalizers were removed because they are no longer needed.
	*	`region_name` virtual field has been added for `AbstractAddress` entity. The purpose is to use `region_name` field only in filters and reports instead of simultaneous use of `AbstractAddress:: regionText` field and relation to region dictionary table.
*	Chart bundle has been modified:
	*	`ChartOptionsBuilder` class has been added to help building chart options. Its logic was moved from `getChartOptions` method in `OroReportBundle:Report` entity.
*	Cron bundle has been modified:
	*	Unused `RaiseExceptionLogger` has been removed.
	*	`OutputLogger` has been moved to Oro Log Component.
	*	Dump logic has been moved to `TranslationPackDumper` from `TranslationDump` command.
*	Data Audit bundle has been modified:
	*	`change_history_block` placeholder has been modified so it is no longer needed to define `audit_entity_class`. Change History link will appear on auditable entities.
*	Data Grid bundle has been modified:
	*	`GroupConcat` custom DQL function has been added to allow concatenation of contact groups.
*	 Distribution bundle has been modified:
	*	Support of php v.5.3 has been added to `OroKernel` class for correct run of application install with php v.5.3.
	*	Pre-boot check of php version has been added to `OroKernel` in order to prevent application start with php v.5.3 installed.
*	Email bundle has been modified:
	*	`Email` entity is now extended.
	*	`EmailHolderHelper` class has been added to help getting email address from object.
	*	`oro_get_email` twig function has been added to gets the email address of the given object.
*	Entity Config bundle has been modified:
	*	`FieldAccessor` class has been added to ease access to object fields.
*	Import Export bundle has been modified:
	*	`EntityNameAwareInterface` interface has been added to work with entity class.
	*	`EntityNameAwareProcessor` interface has been added to work with entity class inside processors. It aggregates `ProcessorInterface` and `EntityNameAwareInterface`.
* Installer bundle has been modified:
	* Unused `oro:platform:check-requirements` command has been removed
	* Unused `RequirementsListener` and `RequirementsHelper` classes have been removed
	* `ChannelFormTwoWaySyncSubscriber` has been removed because it is no longer needed
	* `ChannelDeleteProviderInterface` was renamed to `DeleteProviderInterface`
	* `RestClientInterface` and `GuzzleRestClient` have been added. Realization of REST client is based on Guzzle http client.
	* `AbstractRestTransport` base class for REST transports has been added.
	* Unused `SimpleChannelType` and `SimpleTransport` have been removed.
*	Navigation bundle has been modified:
	*	navigation.js has been removed. Instead, you may use events through mediator: `page:beforeChange`, `page:afterChange`, `page:request`, etc.
*	Organization bundle has been modified:
	*	Organization select form type `oro_organization_select` has been added.
*	Requirejs bundle has been modified:
	*	`requirejs_config_extend` placeholder has been removed.
*	Security bundle has been modified:
	*	It is now possible to omit checking entity relations in `AclHelper::apply`.
*	Ui bundle has been modified:
	*	Chaplin 1.0.0 js library has been introduced.
	*	`oro_sort_by`' twig filter has been added to handle array sorting by specified property
*	User bundle has been modified:
	*	`UserNormalizer` has been removed.
*	Windows bundle has been modified:
	* Default forbidden error handler has been added for `oro.DialogWidget`.

