<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\NavigationBundle\Entity\AbstractPageState;
use Oro\Bundle\NavigationBundle\Entity\PageState;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for PageState entity.
 */
class PagestateController extends AbstractFOSRestController
{
    /**
     * Get list of user's page states
     *
     * @ApiDoc(
     *  description="Get list of user's page states",
     *  resource=true
     * )
     */
    public function cgetAction()
    {
        $entities = $this->getPageStateRepository()->findBy(['user' => $this->getUser()]);

        $serializedEntities = [];
        foreach ($entities as $entity) {
            $serializedEntities[] = $this->serializeEntity($entity);
        }

        return $this->handleView($this->view($serializedEntities, Response::HTTP_OK));
    }

    /**
     * Get page state
     *
     * @param int $id Page state id
     *
     * @ApiDoc(
     *  description="Get page state",
     *  resource=true,
     *  requirements={
     *      {"name"="id", "dataType"="integer"},
     *  }
     * )
     *
     * @return Response
     */
    public function getAction($id)
    {
        $entity = $this->getEntity($id);
        if (null === $entity) {
            return $this->handleNotFound();
        }

        return $this->handleView($this->view($this->serializeEntity($entity), Response::HTTP_OK));
    }

    /**
     * Create new page state
     *
     * @ApiDoc(
     *  description="Create new page state",
     *  resource=true
     * )
     *
     * @return Response
     */
    public function postAction()
    {
        $pageStateClass = $this->getPageStateClass();

        /** @var AbstractPageState $entity */
        $entity = new $pageStateClass();

        $view = $this->get('oro_navigation.form.handler.pagestate')->process($entity)
            ? $this->view($this->getState($entity), Response::HTTP_CREATED)
            : $this->view($this->get('oro_navigation.form.pagestate'), Response::HTTP_BAD_REQUEST);

        return $this->handleView($view);
    }

    /**
     * Update existing page state
     *
     * @param int $id Page state id
     *
     * @ApiDoc(
     *  description="Update existing page state",
     *  resource=true,
     *  requirements={
     *      {"name"="id", "dataType"="integer"},
     *  }
     * )
     *
     * @return Response
     */
    public function putAction($id)
    {
        $entity = $this->getEntity($id);
        if (!$entity) {
            return $this->handleNotFound();
        }

        $view = $this->get('oro_navigation.form.handler.pagestate')->process($entity)
            ? $this->view('', Response::HTTP_NO_CONTENT)
            : $this->view($this->get('oro_navigation.form.pagestate'), Response::HTTP_BAD_REQUEST);

        return $this->handleView($view);
    }

    /**
     * Remove page state
     *
     * @param int $id
     *
     * @ApiDoc(
     *  description="Remove page state",
     *  resource=true,
     *  requirements={
     *      {"name"="id", "dataType"="integer"},
     *  }
     * )
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        $entity = $this->getEntity($id);
        if (!$entity) {
            return $this->handleNotFound();
        }

        $this->getManager()->remove($entity);
        $this->getManager()->flush();

        return $this->handleView($this->view('', Response::HTTP_NO_CONTENT));
    }

    /**
     * Check if page id already exists
     *
     * @QueryParam(name="pageId", nullable=false, description="Unique page id")
     *
     * @ApiDoc(
     *  description="Check if page id already exists",
     *  resource=true
     * )
     */
    public function getCheckidAction()
    {
        $entity = $this->getPageStateRepository()->findOneByPageHash(
            AbstractPageState::generateHash($this->get('request_stack')->getCurrentRequest()->get('pageId'))
        );

        return $this->handleView($this->view($this->getState($entity), Response::HTTP_OK));
    }

    protected function getManager(): EntityManagerInterface
    {
        return $this->getDoctrine()->getManagerForClass($this->getPageStateClass());
    }

    protected function getPageStateRepository(): EntityRepository
    {
        return $this->getManager()->getRepository($this->getPageStateClass());
    }

    protected function getPageStateClass(): string
    {
        return PageState::class;
    }

    protected function getEntity(int $id): ?AbstractPageState
    {
        return $this->getPageStateRepository()->findOneBy(['id' => $id, 'user' => $this->getUser()]);
    }

    protected function serializeEntity(AbstractPageState $entity): array
    {
        return [
            'id'         => $entity->getId(),
            'page_id'    => $entity->getPageId(),
            'page_hash'  => $entity->getPageHash(),
            'data'       => $entity->getData(),
            'created_at' => $entity->getCreatedAt(),
            'updated_at' => $entity->getUpdatedAt()
        ];
    }

    /**
     * Get State for Backbone model
     */
    protected function getState(?AbstractPageState $entity): array
    {
        return [
            'id' => $entity ? $entity->getId() : null,
            'pagestate' => [
                'data'   => $entity ? $entity->getData() : '',
                'pageId' => $entity ? $entity->getPageId() : ''
            ]
        ];
    }

    private function handleNotFound(): Response
    {
        return $this->handleView($this->view('', Response::HTTP_NOT_FOUND));
    }
}
