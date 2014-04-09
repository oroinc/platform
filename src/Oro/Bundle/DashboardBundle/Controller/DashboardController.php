<?php

namespace Oro\Bundle\DashboardBundle\Controller;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DashboardBundle\Entity\ActiveDashboard;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DashboardController extends Controller
{
    /**
     * @Route(
     *      "/index/{id}",
     *      name="oro_dashboard_index",
     *      defaults={"id" = ""}
     * )
     */
    public function indexAction($id = null)
    {
        /** @var Manager $manager */
        $manager = $this->get('oro_dashboard.manager');

        $changeActive = $this->get('request')->get('change_dashboard', false);

        /**
         * @var User $user
         */
        $user = $this->getUser();
        if (!$user || ($changeActive && !$id)) {
            throw new NotFoundHttpException();
        }

        $dashboards = $manager->getDashboards();
        $currentDashboard = null;

        if ($changeActive) {
            $currentDashboard = $this->findCurrentDashboard($id, $dashboards);
            if (!$currentDashboard) {
                throw new NotFoundHttpException();
            }
            $this->setActiveDashboard($user, $currentDashboard);
        } elseif ($id) {
            $currentDashboard = $this->findCurrentDashboard($id, $dashboards);
        } else {
            $activeDashboard = $this->findUserActiveDashboard($user);
            $currentDashboard = $this->findCurrentDashboard($activeDashboard->getDashboard()->getId(), $dashboards);
        }

        if (!$currentDashboard) {
            $currentDashboard = $manager->findDefaultDashboard($dashboards);
        }

        $config = $currentDashboard->getConfig();

        $template  = isset($config['twig']) ? $config['twig'] : 'OroDashboardBundle:Index:default.html.twig';

        return $this->render($template, array('dashboards' => $dashboards, 'dashboard' => $currentDashboard));
    }

    /**
     * @Route(
     *      "/widget/{widget}/{bundle}/{name}",
     *      name="oro_dashboard_widget",
     *      requirements={"widget"="[\w-]+", "bundle"="\w+", "name"="[\w-]+"}
     * )
     */
    public function widgetAction($widget, $bundle, $name)
    {
        return $this->render(
            sprintf('%s:Dashboard:%s.html.twig', $bundle, $name),
            $this->get('oro_dashboard.manager')->getWidgetAttributesForTwig($widget)
        );
    }

    /**
     * @Route(
     *      "/itemized_widget/{widget}/{bundle}/{name}",
     *      name="oro_dashboard_itemized_widget",
     *      requirements={"widget"="[\w-]+", "bundle"="\w+", "name"="[\w-]+"}
     * )
     */
    public function itemizedWidgetAction($widget, $bundle, $name)
    {
        /** @var Manager $manager */
        $manager = $this->get('oro_dashboard.manager');

        $params = array_merge(
            [
                'items' => $manager->getWidgetItems($widget)
            ],
            $manager->getWidgetAttributesForTwig($widget)
        );

        return $this->render(
            sprintf('%s:Dashboard:%s.html.twig', $bundle, $name),
            $params
        );
    }

    protected function findUserActiveDashboard(User $user)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');

        /**
         * @var ActiveDashboard $currentActive
         */
        return $em->getRepository('OroDashboardBundle:ActiveDashboard')->findOneBy(array('id' => $user->getId()));
    }

    /**
     * @param $id
     * @param DashboardModel[] $dashboards
     * @return mixed
     */
    protected function findCurrentDashboard($id, array $dashboards)
    {
        foreach ($dashboards as $dashboard) {
            if ($dashboard->getDashboard()->getId() == $id) {
                return $dashboard;
            }
        }

        return null;
    }

    /**
     * @param User $user
     * @param DashboardModel $currentDashboard
     */
    protected function setActiveDashboard(User $user, DashboardModel $currentDashboard)
    {
        /** @var EntityManager $em */
        $em = $this->get('doctrine.orm.entity_manager');
        $activeDashboard = $this->findUserActiveDashboard($user);

        if (!$activeDashboard) {
            $activeDashboard = new ActiveDashboard();
            $activeDashboard->setUser($user);
        }

        $activeDashboard->setDashboard($currentDashboard->getDashboard());

        $em->persist($activeDashboard);
        $em->flush();
    }
}
