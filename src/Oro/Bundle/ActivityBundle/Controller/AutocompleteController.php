<?php

namespace Oro\Bundle\ActivityBundle\Controller;

use Oro\Bundle\ActivityBundle\Autocomplete\ContextSearchHandler;
use Oro\Bundle\FormBundle\Model\AutocompleteRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Autocomplete search controller for Activities.
 *
 * @Route("/activities")
 */
class AutocompleteController extends Controller
{
    /**
     * @param Request $request
     * @param string $activity The type of the activity entity.
     *
     * @return JsonResponse
     * @throws HttpException|AccessDeniedHttpException
     *
     * @Route("/{activity}/search/autocomplete", name="oro_activity_form_autocomplete_search")
     */
    public function autocompleteAction(Request $request, $activity)
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

        if (!$this->get('oro_form.autocomplete.security')->isAutocompleteGranted($autocompleteRequest->getName())) {
            $result['errors'][] = 'Access denied.';
        }

        if (!empty($result['errors'])) {
            if ($isXmlHttpRequest) {
                return new JsonResponse($result, $code);
            }

            throw new HttpException($code, implode(', ', $result['errors']));
        }

        /** @var ContextSearchHandler $searchHandler */
        $searchHandler = $this->get('oro_activity.form.handler.autocomplete');
        $searchHandler->setClass($activity);

        return new JsonResponse($searchHandler->search(
            $autocompleteRequest->getQuery(),
            $autocompleteRequest->getPage(),
            $autocompleteRequest->getPerPage(),
            $autocompleteRequest->isSearchById()
        ));
    }
}
