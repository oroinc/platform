# ActivityListBundle Extends OroSegmentBundle with a New "Activity" Filter Type 


* This filter can be used to filter records if they:

    * have an activity with a value in the field
        (e.g. Contact who has an activity "Email" where subject of the email contains text "Re:")
    * do not have an activity with a value in the field
        (e.g. Contact who does not have activity "Email" where subject of the email contains text "Meeting")

* If you select only one activity type in the filter, you can filter based on any field of the activity
* If you select more than one activity type in the filter, you can filter based on fields "updatedAt", "createdAt" of the selected activities
