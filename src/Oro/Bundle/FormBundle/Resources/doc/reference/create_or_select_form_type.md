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
* **create_entity_form_options** - additional options for create entity form type, data_class option will be
overridden by "class" option of main form type;
* **grid_name** (required) - name of grid used to render list of existing entities in grid mode, grid must contain
entity identifier and data required to render widgets in view mode, row click action of this grid will be disabled
and replaced with form type row click action;
* **existing_entity_grid_id** - grid column name that contains existing entity identifier, used to select
existing entity, default value is "id";
* **view_widgets** (required) - array that contains configuration for entity widgets in view mode, each element
must be array with following keys:
    * *route_name* (required) - name of the route used to render widget;
    * *route_parameters* - array of parameters for route, key is parameter name, value is either static value or
    PropertyPath used to extract value from entity, default value is array('id' => new PropertyPath('id'));
    * *grid_row_to_route* - array used to map grid row values to route parameters, key is grid column name,
    value is route parameter name, default value is array('id' => 'id');
    * *widget_alias* - alias of created widget, default value is generated automatically based on form type name
    and route name;
* **mode** - default mode used to render form type at first time, default mode is create mode.

### Form Type Templates
