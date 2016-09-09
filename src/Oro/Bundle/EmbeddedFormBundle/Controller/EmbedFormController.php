<?php

namespace Oro\Bundle\EmbeddedFormBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbedFormLayoutManager;
use Oro\Bundle\EmbeddedFormBundle\Event\EmbeddedFormSubmitAfterEvent;
use Oro\Bundle\EmbeddedFormBundle\Event\EmbeddedFormSubmitBeforeEvent;

class EmbedFormController extends Controller
{
    /**
     * @Route("/submit/{id}", name="oro_embedded_form_submit", requirements={"id"="[-\d\w]+"})
     */
    public function formAction(EmbeddedForm $formEntity, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setEtag($formEntity->getId() . $formEntity->getUpdatedAt()->format(\DateTime::ISO8601));
        $this->setCorsHeaders($formEntity, $request, $response);

        if ($response->isNotModified($request)) {
            return $response;
        }

        $isInline = $request->query->getBoolean('inline');

        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        /** @var EmbeddedFormManager $formManager */
        $formManager = $this->get('oro_embedded_form.manager');
        $form        = $formManager->createForm($formEntity->getFormType());

        if (in_array($request->getMethod(), ['POST', 'PUT'])) {
            $dataClass = $form->getConfig()->getOption('data_class');
            if (isset($dataClass) && class_exists($dataClass)) {
                $ref         = new \ReflectionClass($dataClass);
                $constructor = $ref->getConstructor();
                $data        = $constructor && $constructor->getNumberOfRequiredParameters()
                    ? $ref->newInstanceWithoutConstructor()
                    : $ref->newInstance();

                $form->setData($data);
            } else {
                $data = [];
            }
            $event = new EmbeddedFormSubmitBeforeEvent($data, $formEntity);
            $eventDispatcher = $this->get('event_dispatcher');
            $eventDispatcher->dispatch(EmbeddedFormSubmitBeforeEvent::EVENT_NAME, $event);
            $form->submit($request);

            $event = new EmbeddedFormSubmitAfterEvent($data, $formEntity, $form);
            $eventDispatcher->dispatch(EmbeddedFormSubmitAfterEvent::EVENT_NAME, $event);
        }

        if ($form->isValid()) {
            $entity = $form->getData();

            /**
             * Set owner ID (current organization) to concrete form entity
             */
            $entityClass      = ClassUtils::getClass($entity);
            $config           = $this->get('oro_entity_config.provider.ownership');
            $entityConfig     = $config->getConfig($entityClass);
            $formEntityConfig = $config->getConfig($formEntity);

            if ($entityConfig->get('owner_type') === OwnershipType::OWNER_TYPE_ORGANIZATION) {
                $accessor = PropertyAccess::createPropertyAccessor();
                $accessor->setValue(
                    $entity,
                    $entityConfig->get('owner_field_name'),
                    $accessor->getValue($formEntity, $formEntityConfig->get('owner_field_name'))
                );
            }
            $em->persist($entity);
            $em->flush();

            $redirectUrl = $this->generateUrl('oro_embedded_form_success', [
                'id' => $formEntity->getId(),
                'inline' => $isInline
            ]);

            $redirectResponse = new RedirectResponse($redirectUrl);
            $this->setCorsHeaders($formEntity, $request, $redirectResponse);

            return $redirectResponse;
        }

        /** @var EmbedFormLayoutManager $layoutManager */
        $layoutManager = $this->get('oro_embedded_form.embed_form_layout_manager');

        $layoutManager->setInline($isInline);

        $response->setContent($layoutManager->getLayout($formEntity, $form)->render());

        return $response;
    }

    /**
     * @Route("/success/{id}", name="oro_embedded_form_success", requirements={"id"="[-\d\w]+"})
     */
    public function formSuccessAction(EmbeddedForm $formEntity, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setEtag(
            $formEntity->getId() . $formEntity->getUpdatedAt()->format(\DateTime::ISO8601)
        );
        $this->setCorsHeaders($formEntity, $request, $response);

        /** @var EmbedFormLayoutManager $layoutManager */
        $layoutManager = $this->get('oro_embedded_form.embed_form_layout_manager');

        $layoutManager->setInline($request->query->getBoolean('inline'));

        $response->setContent($layoutManager->getLayout($formEntity)->render());

        return $response;
    }

    /**
     * Checks if Origin request header match any of the allowed domains
     * and set Access-Control-Allow-Origin
     *
     * @param EmbeddedForm $formEntity
     * @param Request $request
     * @param Response $response
     */
    protected function setCorsHeaders(EmbeddedForm $formEntity, Request $request, Response $response)
    {
        // skip if not a CORS request
        if (!$request->headers->has('Origin')
            || $request->headers->get('Origin') == $request->getSchemeAndHttpHost()
        ) {
            return;
        }

        // skip if no allowed domains
        $allowedDomains = $formEntity->getAllowedDomains();
        if (empty($allowedDomains)) {
            return;
        }

        $allowedDomains = explode("\n", $allowedDomains);
        $origin = $request->headers->get('Origin');

        foreach ($allowedDomains as $allowedDomain) {
            $regexp = '#^https?:\/\/' . str_replace('\*', '.*', preg_quote($allowedDomain, '#')) . '$#i';
            if ('*' === $allowedDomain || preg_match($regexp, $origin)) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                break;
            }
        }
    }
}
