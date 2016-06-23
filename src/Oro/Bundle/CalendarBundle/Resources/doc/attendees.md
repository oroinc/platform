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

API Example:
------------

Via API for Attendee you could use next fields: `email`, `status`,  `type`, `displayName`.
Bellow examples in json and array format. 

##### GET query example

For getting calendar events with attendees form server you should send GET request ot `/api/rest/latest/calendarevents/{id}` 


##### POST query example 

POST request you should send to `/api/rest/latest/calendarevents` in json format. Below written option as an array:

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
        ],d
        'attendees'   => [ # add event guests
            ['email' => 'admin@example.com', 'status' => 'none', 'type' => 'organizer'],
            ['email' => 'sales_man@user.com', 'displayName'=>'test name', 'status' => 'none'],
            ['email' => 'user@user.com', 'type' => 'required', 'status' => 'none'],
        ],
    ];

Response on this will be json: `{"id":1}` where `1` is a calendar event id that was created


##### PUT query example {#put_query_example}

PUT request you should send to `/api/rest/latest/calendarevents/{id}` in json format where `{id}` is calendar event id that you want to update. Below written option as an array:

    [
        'title'=>'test title',
        'description'=>'test description',
        'attendees' => [ # this will remove all previous attendees, if they exist. And in result you will have only this one attendee
            ['displayName'=>'test name', 'status'=>'tentative'],
        ]
    ];

If this query successfully send you will not receive any body but response status will have `204` 


##### DELETE query example 

If you want to remove Attendees from event you should send PUT request see [PUT query example](#put_query_example)

    [
        'attendees' => []
    ];

Or you could delete calendar event. For deleting calendar event you should send DELETE to `/api/rest/latest/calendarevents/{id}`
