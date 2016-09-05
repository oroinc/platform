Recurring Events
================

Table of content
-----------------
- [Overview](#overview)
- [Recurrence Pattern](#recurrence-pattern)
- [Exceptions](#exceptions)
- [Recurrence Validation](#recurrence-validation)
- [Key Classes](#key-classes)

##Overview

This functionality allows to manage recurring events. In common case recurring event is saved as a single instance of 
event along with 'recurrence' field where recurring pattern is stored. Based on this pattern Calendar UI expands 
instance of one recurring event into a sequence of occurrences.

Currently recurring events can be managed only using API. UI exposes these events but has limitations to manage them. 
For instance only `title` and `description` fields of recurring event could be edited using UI. Also it's not possible 
to create recurring event using UI at the moment.

##Recurrence Pattern

Each calendar event has `recurrence` field. This is a dictionary containing fields related to the event recurrence. Some fields are mandatory for all recurrence patterns, and some fields are required only for some patterns. The fields are described in the following table:

<table>
<tr>
    <th rowspan="2">Field Name</th>
    <th colspan="6">Recurrence Pattern</th>
</tr>
<tr>
    <th>Daily</th>
    <th>Weekly</th>
    <th>Monthly</th>
    <th>MonthNth</th>
    <th>Yearly</th>
    <th>YearNth</th>
</tr>
<tr>
    <td>recurrenceType</td>
    <td>daily</td>
    <td>weekly</td>
    <td>monthly</td>
    <td>monthnth</td>
    <td>yearly</td>
    <td>yearnth</td>
</tr>
<tr>
    <td>interval</td>
    <td>Number of day</td>
    <td>Number of week</td>
    <td colspan="2">Number of month</td>
    <td colspan="2">Number of month (a multiple of 12, i.e. 12, 24, 36, 48 etc.</td>
</tr>
<tr>
    <td>instance</td>
    <td>Not used</td>
    <td>Not used</td>
    <td>Not used</td>
    <td>A value from 1 to 5</td>
    <td>Not used</td>
    <td>A value from 1 to 5</td>
</tr>
<tr>
    <td>dayOfWeek</td>
    <td>Not used</td>
    <td>Array of week days</td>
    <td>Not used</td>
    <td>Array containing one week day</td>
    <td>Not used</td>
    <td>Array containing one week day</td>
</tr>
<tr>
    <td>dayOfMonth</td>
    <td>Not used</td>
    <td>Not used</td>
    <td>Day of month</td>
    <td>Not used</td>
    <td>Day of month</td>
    <td>Not used</td>
</tr>
<tr>
    <td>monthOfYear</td>
    <td>Not used</td>
    <td>Not used</td>
    <td>Not used</td>
    <td>Not used</td>
    <td colspan="2">Month number from 1 to 12</td>
</tr>
<tr>
    <td>startTime</td>
    <td colspan="6">Range of recurrence - Start (mandatory)</td>
</tr>
<tr>
    <td>endTime</td>
    <td colspan="6">Range of recurrence - End (mandatory)</td>
</tr>
<tr>
    <td>occurrences</td>
    <td colspan="6">Range of recurrence - End after X occurrences (optional)</td>
</tr>
<tr>
    <td>timeZone</td>
    <td colspan="6">The time zone in which the time is specified (mandatory)</td>
</tr>
</table>

In common case for recurring event only one entity of OroCalendarBundle:CalendarEvent is created with reference to 
entity of OroCalendarBundle:Recurrence. When API request with date range will be send to server, it will dynamically 
expand each instance of recurring event into occurrences of this recurring event. Each occurrence event will have the 
same data as original recurring event, but with dynamically calculated `start` and `end` dates.

##Exceptions

Each occurrence of a recurrent event can be modified so it differs from other occurrences. Such event has it's own step
and called "exception" event. These exceptions are represented by separate event entities with additional fields. 
The standard event fields (`title`, `description`, `start`, `end`, etc.) can differ from the parent recurrence event fields.
Here is the list of additional fields which are applicable only for exception events:
- **recurringEventId** – the id of the parent recurring event.
- **originalStart** – the original start date and time of this occurrence. It may differ from the actual start date for the recurrence.
- **isCancelled** – A boolean field indicating whether the occurrence was cancelled (removed from the user’s calendar).

##Recurrence Validation

To make sure that Recurrence pattern has all needed data it can be validated with recurrence model:
```php
use Oro\Bundle\CalendarBundle\Model\Recurrence as RecurrenceModel;
        
...

/** @var \Symfony\Component\Validator\Validator\ValidatorInterface $validator */
/** @var \Oro\Bundle\CalendarBundle\Entity\Recurrence $recurrence */
$model = new RecurrenceModel($this->validator);
$model->validateRecurrence($recurrence);

```

##Key Classes

Here is a list of key classes:

**Entities**
- [Entity/Recurrence](../../Entity/Recurrence.php) - Entity that contains all recurrence pattern data and has 
'one to one' relation with [CalendarEvent](../../Entity/CalendarEvent.php).

**Model**
- [Model/Recurrence](../../Model/Recurrence.php) - Model represents domain logic related to recurrence. Recurrence 
entity is passed to model in most cases to fulfill it's responsibilities. Can be used by client code in the application.

**Model / Strategies**
Strategies implements different types of recurrence patterns. Model uses strategies to delegate responsibilities related
to different recurrence patterns. Strategies should not be used directly in application client code.
- [Model/Recurrence/AbstractStrategy](../../Model/Recurrence/AbstractStrategy.php) - The base class for recurrence patterns. It contains all basic methods that can be reused in child classes.
- [Model/Recurrence/DailyStrategy](../../Model/Recurrence/DailyStrategy.php) - The Daily recurrence pattern strategy implementation.
- [Model/Recurrence/DelegateStrategy](../../Model/Recurrence/DelegateStrategy.php) - The class that determines what recurrence pattern strategy must be used according recurrence data.
- [Model/Recurrence/MonthlyStrategy](../../Model/Recurrence/MonthlyStrategy.php) - The Monthly recurrence pattern strategy implementation.
- [Model/Recurrence/MonthNthStrategy](../../Model/Recurrence/MonthNthStrategy.php) - The MonthNth recurrence pattern strategy implementation.
- [Model/Recurrence/StrategyInterface](../../Model/Recurrence/StrategyInterface.php) - An interface of recurrence pattern strategies.
- [Model/Recurrence/WeeklyStrategy](../../Model/Recurrence/WeeklyStrategy.php) - The Weekly recurrence pattern strategy implementation.
- [Model/Recurrence/YearlyStrategy](../../Model/Recurrence/YearlyStrategy.php) - The Yearly recurrence pattern strategy implementation.
- [Model/Recurrence/YearNthStrategy](../../Model/Recurrence/YearNthStrategy.php) - The YearNth recurrence pattern strategy implementation.
