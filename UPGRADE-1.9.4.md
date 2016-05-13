UPGRADE FROM 1.9.3 to 1.9.4
===========================

## Oro\Bundle\CalendarBundle\Entity\CalendarEvent

- constants `NOT_RESPONDED`, `TENTATIVELY_ACCEPTED`, `ACCEPTED`, `DECLINED` were deprecated in favor of these with `STATUS_` prefix
- method `getInvitationStatus` was deprecated in favour of `getAttendee()->getStatus()`
- method `setInvitationStatus` was removed
- attendees of the event are now retrieved using `getAttendees` method on arbitrary event (parent/child)
- related notification templates were changed and now relates to attendees

## Oro\Bundle\CalendarBundle\Form\Type\CalendarEvent[Api]Type

- forms works with attendees instead of calendar events now
- previous listeners were moved into subscribers


## Oro\Bundle\CalendarBundle\Model\Email\EmailNotification, Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor

- works with attendee instead of calendar event

## Oro\Bundle\CalendarBundle\Provider\AbstractCalendarEventNormalizer, Oro\Bundle\CalendarBundle\Provider\PublicCalendarEventNormalizer, Oro\Bundle\CalendarBundle\Provider\SystemCalendarEventNormalizer

- requires one more argument in constructor
