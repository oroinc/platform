<?php

namespace Oro\Bundle\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\FormBundle\Model\AutocompleteRequest;
use Oro\Bundle\CalendarBundle\Autocomplete\AttendeeSearchHandler;

/**
 * @Route("/calendarevents/autocomplete")
 */
class AutocompleteController extends Controller
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws HttpException
     *
     * @Route("/attendees", name="oro_calendarevent_autocomplete_attendees")
     */
    public function autocompleteAttendeesAction(Request $request)
    {
        $autocompleteRequest = new AutocompleteRequest($request);
        $validator           = $this->get('validator');
        $isXmlHttpRequest    = $request->isXmlHttpRequest();
        $code                = 200;
        $result              = [
            'results' => [],
            'hasMore' => false,
            'errors'  => []
        ];

        if ($violations = $validator->validate($autocompleteRequest)) {
            /** @var ConstraintViolation $violation */
            foreach ($violations as $violation) {
                $result['errors'][] = $violation->getMessage();
            }
        }

        if (!empty($result['errors'])) {
            if ($isXmlHttpRequest) {
                return new JsonResponse($result, $code);
            }

            throw new HttpException($code, implode(', ', $result['errors']));
        }

        /** @var AttendeeSearchHandler $searchHandler */
        $searchHandler = $this->get('oro_calendar.autocomplete.attendee_search_handler');

        return new JsonResponse($searchHandler->search(
            $autocompleteRequest->getQuery(),
            $autocompleteRequest->getPage(),
            $autocompleteRequest->getPerPage(),
            $autocompleteRequest->isSearchById()
        ));
    }
}
