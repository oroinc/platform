<?php

namespace Oro\Bundle\FormBundle\Controller;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Autocomplete\Security;
use Oro\Bundle\FormBundle\Model\AutocompleteRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Autocomplete search controller.
 */
#[Route(path: '/autocomplete')]
class AutocompleteController extends AbstractController
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @throws HttpException|AccessDeniedHttpException
     */
    #[Route(path: '/search', name: 'oro_form_autocomplete_search')]
    public function searchAction(Request $request)
    {
        $autocompleteRequest = new AutocompleteRequest($request);
        $validator           = $this->container->get(ValidatorInterface::class);
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

        if (!$this->container->get(Security::class)->isAutocompleteGranted($autocompleteRequest->getName())) {
            $result['errors'][] = 'Access denied.';
        }

        if (!empty($result['errors'])) {
            if ($isXmlHttpRequest) {
                return new JsonResponse($result, $code);
            }

            throw new HttpException($code, implode(', ', $result['errors']));
        }

        /** @var SearchHandlerInterface $searchHandler */
        $searchHandler = $this->container
            ->get(SearchRegistry::class)
            ->getSearchHandler($autocompleteRequest->getName());

        return new JsonResponse(
            $searchHandler->search(
                $autocompleteRequest->getQuery(),
                $autocompleteRequest->getPage(),
                $autocompleteRequest->getPerPage(),
                $autocompleteRequest->isSearchById()
            )
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                ValidatorInterface::class,
                Security::class,
                SearchRegistry::class,
            ]
        );
    }
}
