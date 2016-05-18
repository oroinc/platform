Recurring Events
================

Table of content
-----------------
- [Overview](#overview)
- [Recurrence Pattern](#recurrence-pattern)
- [Pattern Exceptions](#pattern-exceptions)
- [Recurrence Validation](#recurrence-validation)
- [Key Classes](#key-classes)

##Overview

On creating an event in Calendar, you can make it repeat on certain days. Currently recurring events can be created only with API requests(there are no any UI forms on frontend) and according to such limitation only Title and Description of Calendar event can be edited.  

##Recurrence Pattern

Each calendar event has "recurrence" field. This is a dictionary containing fields related to the event recurrence. Some fields are mandatory for all recurrence patterns, and some fields are required only for some patterns. The fields are described in the following table:
![Recurrence pattern fields table](./recurrence_pattern.png)
In common it creates only one record of Calendar event entity and during API requests it can dynamically create a lot of new Calendar event items according to its recurrence data. The newly created items will have the same data as original Calendar event, but with dynamically calculated Start and End dates.   

##Pattern Exceptions

Each occurrence of a recurrent event can be modified so it differs from other occurrences. These exceptions are represented by separate event entities with additional fields. The standard event fields (title, description, start, end etc) can differ from the parent recurrence event fields.
The additional fields are as follows:
- **recurringEventId** – the id of the parent recurring event.
- **originalStart** – the original start date and time of this occurrence. It may differ from the actual start date for the recurrence. 
- **isCancelled** – A boolean field indicating whether the occurrence was cancelled (removed from the user’s calendar).

##Recurrence Validation

To make sure that Recurrence pattern has all needed data it can be validated with recurrence strategy helper:
```php
use Oro\Bundle\CalendarBundle\Model\Recurrence\Helper\StrategyHelper;
        
...

/** @var \Symfony\Component\Validator\Validator\ValidatorInterface $validator */
/** @var \Oro\Bundle\CalendarBundle\Entity\Recurrence $recurrence */
$helper = new StrategyHelper($this->validator);
$helper->validateRecurrence($recurrence);

```

##Key Classes

Here is a list of key classes:

- [AbstractStrategy](../../Model/Recurrence/AbstractStrategy.php) - The base class for recurrence patterns. It contains all basic methods that can be reused in child classes.
- [DailyStrategy](../../Model/Recurrence/DailyStrategy.php) - The Daily recurrence pattern strategy implementation. 
- [DelegateStrategy](../../Model/Recurrence/DelegateStrategy.php) - The class that determines what recurrence pattern strategy must be used according reccurence data.
- [MonthlyStrategy](../../Model/Recurrence/MonthlyStrategy.php) - The Monthly recurrence pattern strategy implementation.
- [MonthNthStrategy](../../Model/Recurrence/MonthNthStrategy.php) - The MonthNth recurrence pattern strategy implementation.
- [StrategyInterface](../../Model/Recurrence/StrategyInterface.php) - An interface of recurrence patter strategies.
- [WeeklyStrategy](../../Model/Recurrence/WeeklyStrategy.php) - The Weekly recurrence pattern strategy implementation.
- [YearlyStrategy](../../Model/Recurrence/YearlyStrategy.php) - The Yearly recurrence pattern strategy implementation.
- [YearNthStrategy](../../Model/Recurrence/YearNthStrategy.php) - The YearNth recurrence pattern strategy implementation.
- [Recurrence](../../Entity/Recurrence.php) - Entity that contains all recurrence pattern data and has 'one to one' relation with - [Recurrence](../../Entity/Recurrence.php) - Entity that contains all recurrence pattern data and has 'one to one' relation with [CalendarEvent](../../Entity/CalendarEvent.php).
