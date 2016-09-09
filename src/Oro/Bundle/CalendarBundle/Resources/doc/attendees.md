# Attendee

Attendee entity represents guest of `OroCalendarEvent:Event` and association with with related entity.
The only supported related entity at the moment is `OroUserBundle:User`. Attendee is associated with `OroUserBundle:User` based on 
matching logic. On UI attendees of event are shown with label `Guests`.

#### Fields

Attendee entity has next fields:

* `email` - String. Email of attendee. Cannot be blank in API request if `displayName` is empty.
* `displayName` - String. Name of attendee used to display it on the view. Cannot be blank in API request if `email` is empty.
* `user` - Relation with `OroUserBundle:User`. Not available in API create/update requests.
* `calendarEvent` - Relation with `OroCalendarEvent:Event`. Required in persistence and not available in API. 
* `status` - Enum. Default values are: `none`, `accepted`, `declined`, `tentative`. Besides API this value could be changed by user from view page of `OroCalendarEvent:Event`.
* `type` - Enum. Default values are: `organizer`, `optional`, `required`. At the moment there is no business logic related to this field.

#### Matching Logic

This logic uses email of `OroCalendarEvent:Attendee` to find `OroUserBundle:User` with the same email and same Organization.

#### Notification logic

When event is updated in UI user is asked to confirm notification of attendees.
 
In API POST or PUT request it's possible to pass property `notifyInvitedUsers`. For example:

```
PUT /api/rest/latest/calendarevents/1
{
    "title" : "Test Event",
    "notifyInvitedUsers" : false
}
```

In API DELETE request it's possible to pass parameter `notifyInvitedUsers`. For example:

```
DELETE /api/rest/latest/calendarevents/1?notifyInvitedUsers=true
```

#### AttendeeRelationManager

Class `Oro\Bundle\CalendarBundle\Manager\AttendeeManager` is responsible to maintain relation of entity with other entities like `OroUserBundle:User`.

##API Example:

In API PUT/POST requests next fields are supported for Attendee: `email`, `status`,  `type`, `displayName`.
In GET responses next fields are exposed additionally: `user_id`, `createdAt`, `updatedAt`.
 
##### GET query example

For getting calendar events with attendees from server you should send GET request to `/api/rest/latest/calendarevents/{id}`.

In GET response attendees are exposed in property `attendees` which contain an array. Each element of array contains a JSON object 
with supported fields. 

For example:

```
GET /api/rest/latest/calendarevents/1
{
    "id": 1,
    ...
    "attendees": [
        {
            "displayName": "John Doe",
            "email": "john.doe@example.com",
            "userId": 1,
            "createdAt": "2016-06-29T01:16:40+00:00",
            "updatedAt": "2016-06-29T01:16:40+00:00",
            "status": "accepted",
            "type": "organizer"
        },
        {
            "displayName": "Jack Smith",
            "email": "jack.smith@example.com",
            "userId": null,
            "createdAt": "2016-06-29T01:16:40+00:00",
            "updatedAt": "2016-06-29T01:16:40+00:00",
            "status": "none",
            "type": "required"
        }
    ],
    "invitedUsers": [
        1
    ]
}
```

Note in this example first attendee `John Doe` has property `user_id`. It means this instance of `OroCalendarEvent:Attendee` bound to `OroUserBundle:User` in the application.
In the meantime second attendee `Jack Smith` is not bound to any user in the application.

Note, property `invitedUsers` is deprecated and will be removed. 


##### POST query example 

POST request should be send to `/api/rest/latest/calendarevents` in JSON format. For example:

```
POST /api/rest/latest/calendarevents
{
    "start": "2016-05-04T11:29:46+00:00",
    "end": "2016-05-04T11:29:46+00:00",
    "calendar": 1,
    "title":" Test Event",
    "attendees": [
        {
            "displayName": "John Doe",
            "email":"admin@example.com",
            "status": "none",
            "type": "organizer"
        },
        {
            "email": "sales_man@user.com",
            "displayName": "test name", 
            "status": "none"
        },
        {
            "email": "user@user.com",
            "displayName": "test name", 
            "type": "required",
            "status": "none"
        }
    ]
}
```

Response on this will be json: `{"id": 1}` where `1` is a calendar event id that was created.

Note, there is no `user_id` property for attendee in this request. Instead property `email` is used to matched existing user in same organization.
So in this case server tries to find users for emails `admin@example.com`, `sales_man@user.com`, `user@user.com` and associate them
with corresponding attendees using `user` property. 

If user was matched additional instance of `OroCalendarEvent:Event` is created in calendar of matched user.

##### PUT query example

PUT request should be send to `/api/rest/latest/calendarevents/{id}` in JSON format where `{id}` is id of calendar event to update.
For example:

```
PUT /api/rest/latest/calendarevents/1
{
    "title": "Test Event",
    "attendees": [
        {
            "displayName": "Jack Smith", 
            "status": "tentative"
        }
    ]   
}
```

This request will remove all previous attendees, if they were existed before. As result event will have only one attendee `Jack Smith`.

Response for this request has no content and response code is `204` for success. 


##### DELETE query example 

To remove Attendees from event you should send PUT request see [PUT query example](#put-query-example). For example:

```
PUT /api/rest/latest/calendarevents/1
{
    "attendees": []
}
```
Otherwise it's possible to remove calendar event of attendee user. For example:

```
DELETE to `/api/rest/latest/calendarevents/{id}`
```
