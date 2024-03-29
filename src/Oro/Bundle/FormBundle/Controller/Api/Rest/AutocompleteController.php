<?php

namespace Oro\Bundle\FormBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Model\AutocompleteRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * REST API controller for autocomplete functionality.
 */
class AutocompleteController extends AbstractFOSRestController
{
    /**
     * @ApiDoc(
     *  description="Get autocomplete search result",
     *  resource=true,
     *  filters={
     *      {"name"="name", "dataType"="string"},
     *      {"name"="per_page", "dataType"="integer"},
     *      {"name"="page", "dataType"="integer"},
     *      {"name"="query", "dataType"="string"}
     *  }
     * )
     */
    public function searchAction(Request $request)
    {
        $autocompleteRequest = new AutocompleteRequest($request);
        $validator           = $this->container->get('validator');
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

        if (!$this->container->get('oro_form.autocomplete.security')
                ->isAutocompleteGranted($autocompleteRequest->getName())
        ) {
            $result['errors'][] = 'Access denied.';
        }

        if (!empty($result['errors'])) {
            throw new HttpException(500, implode(', ', $result['errors']));
        }

        /** @var SearchHandlerInterface $searchHandler */
        $searchHandler = $this->container
            ->get('oro_form.autocomplete.search_registry')
            ->getSearchHandler($autocompleteRequest->getName());

        return $this->handleView(
            $this->view(
                $searchHandler->search(
                    $autocompleteRequest->getQuery(),
                    $autocompleteRequest->getPage(),
                    $autocompleteRequest->getPerPage(),
                    $autocompleteRequest->isSearchById()
                ),
                Response::HTTP_OK
            )
        );
    }
}
