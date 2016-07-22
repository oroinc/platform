<?php

namespace Oro\Bundle\DataGridBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;

class GridViewApiHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var Registry */
    protected $registry;

    /** @var GridViewManager */
    protected $gridViewManager;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param FormInterface         $form
     * @param Request               $request
     * @param Registry              $registry
     * @param GridViewManager       $gridViewManager
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        Registry $registry,
        GridViewManager $gridViewManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->form            = $form;
        $this->request         = $request;
        $this->registry        = $registry;
        $this->gridViewManager = $gridViewManager;
        $this->tokenStorage    = $tokenStorage;
    }

    /**
     * @param GridView $entity
     *
     * @return boolean
     */
    public function process(GridView $entity)
    {
        $entity->setFiltersData();
        $entity->setSortersData();
        $entity->setColumnsData();

        $this->form->setData($entity);
        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $data = $this->request->request->all();
            unset($data['name']);
            if ($this->form->has('owner')) {
                $data['owner'] = $entity->getOwner();
            }
            $this->form->submit($data);

            if ($this->form->isValid()) {
                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    /**
     * @param GridView $entity
     */
    protected function onSuccess(GridView $entity)
    {
        $default = $this->form->get('is_default')->getData();
        $this->setDefaultGridView($entity, $default);

        $this->fixFilters($entity);
        $om = $this->registry->getManagerForClass('OroDataGridBundle:GridView');
        $om->persist($entity);
        $om->flush();
    }

    /**
     * @param GridView $gridView
     * @param bool     $default
     */
    protected function setDefaultGridView(GridView $gridView, $default)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $this->gridViewManager->setDefaultGridView($user, $gridView, $default);
    }

    /**
     * @todo Remove once https://github.com/symfony/symfony/issues/5906 is fixed.
     *       After removing this method PLEASE CHECK saving filters in grid view
     *       look in CollectionFiltersManager._onChangeFilterSelect()
     *       Added fix for dictionary filters also.
     *
     * @param GridView $gridView
     */
    protected function fixFilters(GridView $gridView)
    {
        $filters = $gridView->getFiltersData();
        foreach ($filters as $name => $filter) {
            if (is_array($filter) && array_key_exists('type', $filter) && $filter['type'] == null) {
                $filters[$name]['type'] = '';
            }
            if (is_array($filter['value'])) {
                foreach ($filter['value'] as $k => $value) {
                    if (is_array($value)) {
                        $filters[$name]['value'][$k] = $value['value'];
                    }
                }
            }
        }

        $gridView->setFiltersData($filters);
    }
}
