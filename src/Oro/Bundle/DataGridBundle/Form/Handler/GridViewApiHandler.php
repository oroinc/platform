<?php

namespace Oro\Bundle\DataGridBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridView;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class GridViewApiHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var RequestStack */
    protected $requestStack;

    /** @var Registry */
    protected $registry;

    /** @var GridViewManager */
    protected $gridViewManager;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param FormInterface         $form
     * @param RequestStack          $requestStack
     * @param Registry              $registry
     * @param GridViewManager       $gridViewManager
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        Registry $registry,
        GridViewManager $gridViewManager,
        TokenStorageInterface $tokenStorage
    ) {
        $this->form            = $form;
        $this->requestStack    = $requestStack;
        $this->registry        = $registry;
        $this->gridViewManager = $gridViewManager;
        $this->tokenStorage    = $tokenStorage;
    }

    /**
     * @param AbstractGridView $entity
     *
     * @return boolean
     */
    public function process(AbstractGridView $entity)
    {
        $entity->setFiltersData();
        $entity->setSortersData();
        $entity->setColumnsData();

        $this->form->setData($entity);
        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $data = $request->request->all();
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
     * @param AbstractGridView $entity
     */
    protected function onSuccess(AbstractGridView $entity)
    {
        $default = $this->form->get('is_default')->getData();
        $this->setDefaultGridView($entity, $default);

        $this->fixFilters($entity);
        $om = $this->registry->getManagerForClass('OroDataGridBundle:GridView');
        $om->persist($entity);
        $om->flush();
    }

    /**
     * @param AbstractGridView $gridView
     * @param bool $default
     */
    protected function setDefaultGridView(AbstractGridView $gridView, $default)
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
     * @param AbstractGridView $gridView
     */
    protected function fixFilters(AbstractGridView $gridView)
    {
        $filters = $gridView->getFiltersData();
        foreach ($filters as $name => $filter) {
            if (is_array($filter) && array_key_exists('type', $filter) && $filter['type'] == null) {
                $filters[$name]['type'] = '';
            }
            if (is_array($filter) && is_array($filter['value'])) {
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
