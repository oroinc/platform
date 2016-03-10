Workflow Action
===============

Create Calendar Event Action
----------------------------

**Class:** Oro\Bundle\CalendarBundle\Workflow\Action\CreateCalendarEventAction

**Alias:** create_calendar_event

**Description:** Create calendar event with reminders

**Parameters:**

- title - calendar event title (required);
- description - calendar event description
- initiator - User that initiate event (required);
- guests - list of guests, array of object User;
- start - DateTime start of event (required);
- end - DateTime end of event (default +1 hour);
- duration - event duration e.g. "30 minutes" or "1 hour" (default +1 hour)
- attribute - attribute that will contain entity instance;
- reminders - array of Reminders for CalendarEvent:
    - method - email|web_socket - see services with "oro_reminder.send_processor" tag and implement SendProcessorInterface
    - interval_number - number of interval units
    - interval_unit - interval unit, can be "M" - minutes, "H" - hours, "D" - days, "W" - weeks

**Configuration Example**

```yml
- @create_calendar_event:
    title: 'Interview with Brenda'
    description: 'Interview on HR position'
    initiator: $currentUser
    guests: [$reviewer]
    start: $dateTime
    end: $dateTime
    attribute: $interview
    reminders:
        - method: email
          interval_number: 1
          interval_unit: H
        - method: web_socket
          interval_number: 10
          interval_unit: M

```
