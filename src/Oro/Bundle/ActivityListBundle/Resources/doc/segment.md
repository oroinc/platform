This bundle extends OroSegmentBundle by new filter type "Activity"
==================================================================

* This filter can be used to filter records based on if they
    * have activity with field having value
        (e.g. Contact who have activity "Email" where subject of the email contains text "Re:")
    * not have activity with field having value
        (e.g. Contact who does not have activity "Email" where subject of the email contains text "Meeting")

* If you select just an activity type in filter, you can filter based on any field of the activity
* If you select more than one activity type in filter, you can filter based on fields "updatedAt", "createdAt"
