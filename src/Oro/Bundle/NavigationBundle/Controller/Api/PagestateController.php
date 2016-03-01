<?php

namespace Oro\Bundle\NavigationBundle\Controller\Api;

use Symfony\Component\HttpFoundation\Response;

use Doctrine\Common\Persistence\ObjectRepository;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\NavigationBundle\Entity\AbstractPageState;

/**
 * @NamePrefix("oro_api_")
 */
class PagestateController extends FOSRestController implements ClassResourceInterface
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
        return $this->handleView(
            $this->view(
                $this->getPageStateRepository()->findBy(
                    array('user' => $this->getUser())
                ),
                Codes::HTTP_OK
            )
        );
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
        if (!$entity = $this->getEntity($id)) {
            return $this->handleView($this->view('', Codes::HTTP_NOT_FOUND));
        }

        return $this->handleView($this->view($entity, Codes::HTTP_OK));
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
            ? $this->view($this->getState($entity), Codes::HTTP_CREATED)
            : $this->view($this->get('oro_navigation.form.pagestate'), Codes::HTTP_BAD_REQUEST);

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
        if (!$entity = $this->getEntity($id)) {
            return $this->handleView($this->view('', Codes::HTTP_NOT_FOUND));
        }

        $view = $this->get('oro_navigation.form.handler.pagestate')->process($entity)
            ? $this->view('', Codes::HTTP_NO_CONTENT)
            : $this->view($this->get('oro_navigation.form.pagestate'), Codes::HTTP_BAD_REQUEST);

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
        if (!$entity = $this->getEntity($id)) {
            return $this->handleView($this->view('', Codes::HTTP_NOT_FOUND));
        }

        $this->getManager()->remove($entity);
        $this->getManager()->flush();

        return $this->handleView($this->view('', Codes::HTTP_NO_CONTENT));
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
        $entity = $this->getPageStateRepository()
            ->findOneByPageHash(
                AbstractPageState::generateHash($this->get('request_stack')->getCurrentRequest()->get('pageId'))
            );

        return $this->handleView($this->view($this->getState($entity), Codes::HTTP_OK));
    }

    /**
     * Get entity Manager
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getManager()
    {
        return $this->getDoctrine()->getManagerForClass($this->getPageStateClass());
    }

    /**
     * @return ObjectRepository
     */
    protected function getPageStateRepository()
    {
        return $this->getManager()->getRepository($this->getPageStateClass());
    }

    /**
     * @return string
     */
    protected function getPageStateClass()
    {
        return $this->getParameter('oro_navigation.entity.page_state.class');
    }

    /**
     * Get entity by id
     *
     * @param int $id
     * @return AbstractPageState
     */
    protected function getEntity($id)
    {
        return $this->getPageStateRepository()->findOneById((int) $id);
    }

    /**
     * Get State for Backbone model
     *
     * @param  AbstractPageState $entity
     * @return array
     */
    protected function getState(AbstractPageState $entity = null)
    {
        return array(
            'id' => $entity ? $entity->getId() : null,
            'pagestate' => array(
                'data'   => $entity ? $entity->getData() : '',
                'pageId' => $entity ? $entity->getPageId() : ''
            )
        );
    }
}
