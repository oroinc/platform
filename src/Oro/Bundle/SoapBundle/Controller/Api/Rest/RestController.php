<?php

namespace Oro\Bundle\SoapBundle\Controller\Api\Rest;

use Doctrine\Common\Persistence\ObjectManager;

use FOS\Rest\Util\Codes;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\SoapBundle\Controller\Api\FormAwareInterface;
use Oro\Bundle\SoapBundle\Controller\Api\FormHandlerAwareInterface;

abstract class RestController extends RestGetController implements
    FormAwareInterface,
    FormHandlerAwareInterface,
    RestApiCrudInterface
{
    /**
     * Edit entity
     *
     * @param  mixed    $id
     * @return Response
     */
    public function handleUpdateRequest($id)
    {
        $entity = $this->getManager()->find($id);
        if (!$entity) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        if ($this->processForm($entity)) {
            $view = $this->view(null, Codes::HTTP_NO_CONTENT);
        } else {
            $view = $this->view($this->getForm(), Codes::HTTP_BAD_REQUEST);
        }

        return $this->handleView($view);
    }

    /**
     * Create new
     *
     * @return Response
     */
    public function handleCreateRequest()
    {
        $entity = $this->getManager()->createEntity();
        $isProcessed = $this->processForm($entity);

        if ($isProcessed) {
            $entityClass = ClassUtils::getRealClass($entity);
            $classMetadata = $this->getManager()->getObjectManager()->getClassMetadata($entityClass);
            $view = $this->view($classMetadata->getIdentifierValues($entity), Codes::HTTP_CREATED);
        } else {
            $view = $this->view($this->getForm(), Codes::HTTP_BAD_REQUEST);
        }

        return $this->handleView($view);
    }

    /**
     * Delete entity
     *
     * @param  mixed    $id
     * @return Response
     */
    public function handleDeleteRequest($id)
    {
        $entity = $this->getManager()->find($id);
        if (!$entity) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        if (!$this->get('oro_security.security_facade')->isGranted('DELETE', $entity)) {
            return $this->handleView($this->view(null, Codes::HTTP_FORBIDDEN));
        }

        $em = $this->getManager()->getObjectManager();
        $this->handleDelete($entity, $em);
        $em->flush();

        return $this->handleView($this->view(null, Codes::HTTP_NO_CONTENT));
    }

    /**
     * Process form.
     *
     * @param  mixed $entity
     * @return bool
     */
    protected function processForm($entity)
    {
        $this->fixRequestAttributes($entity);
        return $this->getFormHandler()->process($entity);
    }

    /**
     * Convert REST request to format applicable for form.
     *
     * @param object $entity
     */
    protected function fixRequestAttributes($entity)
    {
        $request = $this->container->get('request');
        $formName = $this->getForm()->getName();
        $data = empty($formName)
            ? $request->request->all()
            : $request->request->get($formName);

        if (is_array($data) && $this->fixFormData($data, $entity)) {
            if (empty($formName)) {
                // save fixed values for unnamed form
                foreach ($request->request->keys() as $key) {
                    if (array_key_exists($key, $data)) {
                        $request->request->set($key, $data[$key]);
                    } else {
                        $request->request->remove($key);
                    }
                }
                foreach ($data as $key => $val) {
                    if (!$request->request->has($key)) {
                        $request->request->set($key, $data[$key]);
                    }
                }
            } else {
                // save fixed values for named form
                $request->request->set($this->getForm()->getName(), $data);
            }
        }
    }

    /**
     * Fixes form data
     *
     * @param array $data
     * @param mixed $entity
     * @return bool true if any changes in $data array was made; otherwise, false.
     */
    protected function fixFormData(array &$data, $entity)
    {
        return false;
    }

    /**
     * Handle delete entity object.
     *
     * @param object        $entity
     * @param ObjectManager $em
     */
    protected function handleDelete($entity, ObjectManager $em)
    {
        $em->remove($entity);
    }
}
