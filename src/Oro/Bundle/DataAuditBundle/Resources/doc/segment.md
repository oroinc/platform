This bundle extends OroSegmentBundle by new filter type "Data audit"
====================================================================

* This filter can be used to filter records based on if they
    * had field changed to value
        (e.g. Contact who changed job position to "Chef")
    * had field changed to value in period of time
        (e.g. Contact who changed job position to "Chef" during last week)

* Following conditions have to be fulfilled in order to be able to filter by specific field
    * entity has to be auditable
    * field has to be auditable
