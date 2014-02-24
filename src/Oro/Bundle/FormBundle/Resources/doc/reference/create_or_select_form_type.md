Entity Create or Select Form Type
---------------------------------

### Overview

Entity create or select form type provides functionality to create new entity or select existing one.

Form type has 3 modes - create (default), grid and select.

**Create mode** shows entity form and allows user to enter all required data to create new entity. This form
includes frontend and backend validation. In this mode user can click button "Select Existing"
that will redirect user to grid mode.

**Grid mode** shows grid with existing entities and allows user select any of it by clicking on appropriate row.
This mode has two button - "Cancel" that returns user back to previous mode (create or view), and "Create new"
that redirects user to create mode.

**View mode** shows information about selected existing entity (one or several widgets). In this mode there are
two buttons - "Select another" that redirects user to grid mode, and "Create new" that shows create entity form
in the create mode.

As a result form type can return new not persisted entity (create mode), existing entity instance (view mode) or
null (grid mode).


### Form Type Configuration

Entity create or select form type allows user to configure rendering information in each mode. Existing options are:

* **class** (required) - fully-qualified class name used both in create and view mode to return instance of this class;
* **create_entity_form_type** (required) - form type used to create new form in create mode,
it's usually name of existing already configured form type for specific entity;
* **create_entity_form_options** - additional options for create entity form type;
* **grid_name** (required) - name of grid used to render list of existing entities in grid mode, grid must contain
entity identifier and data required to render widgets in view mode;
* **existing_entity_grid_id** - grid column name that contains existing entity identifier, used to select
existing entity, default value is "id";
* **view_widgets** (required) - array that contains configuration for entity widgets in view mode, each element
must be array with following keys:
    * *route_name* (required) - name of the route used to render widget;
    * *route_parameters* - array of parameters for route, key is parameter name, value is either static value or
    PropertyPath used to extract value from entity, default value is array('id' => new PropertyPath('id'));
    * *grid_row_to_route* - array used to map grid row values to route parameters, key is grid column name,
    value is route parameter name, default value is array('id' => 'id');
    * *widget_alias* - alias of created widget, default value is generated automatically based on form name
    and route name;
* **mode** - default mode used to render form type at first time, default mode is create mode.


### Backend Implementation

Entity create or select form type is compound form type that consists of three fields:

* **new_entity** - field built based on create_entity_form_type and create_entity_form_options and used to return
new entity instance, data_class option will be overridden by "class" option of main form type;
* **existing_entity** - field of form type "oro_entity_identifier", on frontend rendered as a hidden field that
receives value when user clicks on row in existing entity grid, returns instance of existing entity;
* **mode** - hidden field that contains current mode.

To convert data from complex three field representation to one entity or null value form type uses custom data
transformer EntityCreateOrSelectTransformer. It defines current mode based in input value
(create for not existing entity, view for existing entity, default mode from "mode" option for null value)
and returns appropriate entity based on specified mode (new_entity for create, existing_entity for view, null for grid).

Also form type has preSubmit listener that disable validation of create new entity field for grid and view modes.


### Frontend Implementation

Entity create or select form type rendered using Twig templates for custom form type.
Form type uses following blocks:
* **oro_entity_create_or_select_row** - contains blocks to render errors, label and widget, and includes JavaScript
handler (create-select-type-handler.js) that process changing of current mode;
* **oro_entity_create_or_select_label** - contains field label and buttons used to switch between modes;
* **oro_entity_create_or_select_widget** - contains widgets for existing_entity and mode fields, and blocks for modes:
    * *create* - renders new_entity field widget;
    * *grid* - renders datagrid with existing entities, row click action of this grid will be disabled
    and replaced with row click action that selects entity switches to view mode;
    * *view* - renders all widget specified in "view_widgets" option.

JavaScript handler create-select-type-handler.js processes switching between modes, enabling/disabling
of frontend validation for create entity form and rendering of view widgets for selected entity according
to specified view widget options.
