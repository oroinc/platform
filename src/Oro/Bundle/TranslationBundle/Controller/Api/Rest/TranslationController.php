<?php

namespace Oro\Bundle\TranslationBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Handler\Context;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteResource("translation")
 * @NamePrefix("oro_api_")
 */
class TranslationController extends FOSRestController
{
    /**
     * Get translations.
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to 10."
     * )
     * @QueryParam(
     *      name="domain",
     *      requirements=".+",
     *      nullable=true,
     *      description="The translation domain. Defaults to 'messages'."
     * )
     * @QueryParam(
     *      name="locale",
     *      requirements=".+",
     *      nullable=true,
     *      description="The translation locale."
     * )
     * @ApiDoc(
     *      description="Get translations",
     *      resource=true
     * )
     *
     * @param Request $request
     * @return Response
     */
    public function cgetAction(Request $request)
    {
        $page   = (int)$request->get('page', 1);
        $limit  = (int)$request->get('limit', RestGetController::ITEMS_PER_PAGE);
        $domain = $request->get('domain', 'messages');

        $result = $this->get('translator')->getTranslations([$domain]);

        $data = [];
        if (isset($result[$domain]) && is_array($result[$domain])) {
            $slice = array_slice(
                $result[$domain],
                $page > 0 ? ($page - 1) * $limit : 0,
                $limit
            );
            foreach ($slice as $key => $val) {
                $data[] = ['key' => $key, 'value' => $val];
            }
        }

        $view     = $this->view($data, Codes::HTTP_OK);
        $response = parent::handleView($view);

        $responseContext = [
            'result'     => $data,
            'totalCount' => function () use ($result, $domain) {
                return isset($result[$domain]) && is_array($result[$domain])
                    ? count($result[$domain])
                    : 0;
            },
        ];

        $includeHandler = $this->get('oro_soap.handler.include');
        $includeHandler->handle(
            new Context(
                $this,
                $request,
                $response,
                RestGetController::ACTION_LIST,
                $responseContext
            )
        );

        return $response;
    }

    /**
     * @param string $locale
     * @param string $domain
     * @param string $key
     *
     * @return Response
     *
     * @Patch("translations/{locale}/{domain}/{key}/patch")
     * @AclAncestor("oro_translation_language_translate")
     */
    public function patchAction($locale, $domain, $key)
    {
        $data = json_decode($this->get('request_stack')->getCurrentRequest()->getContent(), true);

        /* @var $translationManager TranslationManager */
        $translationManager = $this->get('oro_translation.manager.translation');

        $translation = $translationManager->saveTranslation(
            $key,
            $data['value'],
            $locale,
            $domain,
            Translation::SCOPE_UI
        );
        $translationManager->flush();

        $translated = null !== $translation;

        $response = [
            'status' => $translated,
            'id' => $translated ? $translation->getId() : '',
            'value' => $translated ? $translation->getValue() : '',
        ];

        return parent::handleView($this->view($response, Codes::HTTP_OK));
    }
}
