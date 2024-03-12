<?php

namespace Oro\Bundle\TranslationBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Handler\Context;
use Oro\Bundle\SoapBundle\Handler\IncludeHandlerInterface;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for translations.
 */
class TranslationController extends AbstractFOSRestController
{
    /**
     * Get translations.
     *
     * @ApiDoc(
     *      description="Get translations",
     *      resource=true
     * )
     */
    #[QueryParam(
        name: 'page',
        requirements: '\d+',
        description: 'Page number, starting from 1. Defaults to 1.',
        nullable: true
    )]
    #[QueryParam(
        name: 'limit',
        requirements: '\d+',
        description: 'Number of items per page. Defaults to 10.',
        nullable: true
    )]
    #[QueryParam(
        name: 'domain',
        requirements: '.+',
        description: "The translation domain. Defaults to 'messages'.",
        nullable: true
    )]
    #[QueryParam(name: 'locale', requirements: '.+', description: 'The translation locale.', nullable: true)]
    public function cgetAction(Request $request): Response
    {
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', RestGetController::ITEMS_PER_PAGE);
        $domain = $request->get('domain', 'messages');

        $result = $this->container->get('translator')->getTranslations([$domain]);

        $data = [];
        $count = 0;
        if (isset($result[$domain]) && \is_array($result[$domain])) {
            $slice = \array_slice($result[$domain], $page > 0 ? ($page - 1) * $limit : 0, $limit);
            foreach ($slice as $key => $val) {
                $data[] = ['key' => $key, 'value' => $val];
            }
            $count = count($result[$domain]);
        }

        $response = $this->handleView($this->view($data, Response::HTTP_OK));
        /** @var IncludeHandlerInterface $includeHandler */
        $includeHandler = $this->container->get('oro_soap.handler.include');
        $includeHandler->handle(new Context(
            $this,
            $request,
            $response,
            RestGetController::ACTION_LIST,
            [
                'result'     => $data,
                'totalCount' => function () use ($count) {
                    return $count;
                },
            ]
        ));

        return $response;
    }

    #[AclAncestor('oro_translation_language_translate')]
    public function patchAction(Request $request, string $locale, string $domain, string $key): Response
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $value = $data['value'];
        if (Translator::DEFAULT_LOCALE !== $locale && '' === $value) {
            $value = null;
        }

        /** @var TranslationManager $translationManager */
        $translationManager = $this->container->get('oro_translation.manager.translation');
        $translation = $translationManager->saveTranslation($key, $value, $locale, $domain, Translation::SCOPE_UI);
        $translationManager->flush();

        $translator = $this->container->get('translator');
        $translated = null !== $translation;
        $response = [
            'status' => $translated,
            'id'     => $translated ? $translation->getId() : null,
            'value'  => $translated ? $translation->getValue() : null,
            'fields' => [
            ]
        ];
        // this is required to auto-update "English Translation" column of the translation datagrid
        if (Translator::DEFAULT_LOCALE === $locale) {
            $response['fields']['englishValue'] = $translator->trans($key, [], $domain, Translator::DEFAULT_LOCALE);
        }

        $view = $this->view($response, Response::HTTP_OK);
        $view->getContext()->setSerializeNull(true);

        return $this->handleView($view);
    }
}
