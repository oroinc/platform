Attendee
=========

Main responsibility of Attendee entity is to store Calendar events guests and their connection with users (if attendee's email matches on user email).
Matching works only in organisation scope. If user does not have permission to see other users information, he (user) will not see Attendee->User relation on view.
Attendee could have email and/or displayName.


#### Fields

Attendee entity has next fields: `email`, `displayName`, `user`, `calendarEvent`, `status`, `type`, `updatedAt`, `createdAt`.

* `email` - attendee email
* `displayName` - attendee name
* `user` - relation with `Oro\Bundle\UserBundle\Entity\User`
* `calendarEvent` - attendee association with the calendar event. This filed is required. 
* `status` - attendee current status (`none`, `accepted`, `declined`, `tentative`).
* `type` - attendee current type (`organizer`, `optional`, `required`) (at this moment there is no logic around this)

`status` and `type` are enum fields. They could be changed via UI on Entity Management page.

Create or update request should contain `email` and/or `displayName`. One of these fields are required.


#### Notification logic

* ORO CRM is responsible for sending notification only if user create/update/delete calendar event on CRM side.
* for sending notification in CRM side after event deleting, should use additional parameter in url: `notifyInvitedUsers` (example: DELETE /api/rest/latest/calendarevents/458?notifyInvitedUsers=true)
* via API should send parameter `notifyInvitedUsers` to false


#### API Example:

    [
        'calendar'    => 1, # In which calendar event should be saved 
        'title'       => 'test title', # calendar event title
        'description' => 'test description', # calendar event description
        'allDay'      => false, # response on All-Day Event or not.
        'start'       => '2016-05-04T11:29:46+00:00',
        'end'         => '2016-05-04T11:29:46+00:00',
        'reminders'   => [
            ['method' => 'web_socket', 'interval' => ['number' => 15, 'unit' => 'M']], # add reminder in 5 min interval
        ],
        'isCancelled' => false, # does this calendar event is canceled
        'recurrence'  => [ # create reccurring event, if 'recurrence' is null recurring event will be converted to simple calendar event
            "recurrenceType": "weekly",
            "interval": 1,
            "dayOfWeek": [
                "friday"
            ],
            "dayOfMonth": null,
            "monthOfYear": null,
            "startTime": "2015-06-19T06:00:00+00:00",
            "endTime": "2015-06-27T06:00:00+00:00",
            "occurrences": 5,
            "instance": null,
        ],
        'attendees'   => [ # add event guests
            ['email' => 'admin@example.com', 'status' => 'none', 'type' => 'organizer'],
            ['email' => 'sales_man@user.com', 'displayName'=>'test name', 'status' => 'none'],
            ['email' => 'user@user.com', 'type' => 'required', 'status' => 'none'],
        ],
    ];
