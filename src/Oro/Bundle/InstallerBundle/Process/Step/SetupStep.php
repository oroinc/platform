<?php

namespace Oro\Bundle\InstallerBundle\Process\Step;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;

class SetupStep extends AbstractStep
{
    public function displayAction(ProcessContextInterface $context)
    {
        $form = $this->createForm('oro_installer_setup');

        /** @var ConfigManager $configManager */
        $configManager = $this->get('oro_config.global');

        $form->get('organization_name')->setData($configManager->get('oro_ui.organization_name'));
        $form->get('application_url')->setData($_SERVER['HTTP_HOST']);

        return $this->render(
            'OroInstallerBundle:Process/Step:setup.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }

    public function forwardAction(ProcessContextInterface $context)
    {
        $adminUser = $this->getAdminUser();

        $form = $this->createForm('oro_installer_setup');
        $form->setData($adminUser);

        $form->handleRequest($this->get('request_stack')->getCurrentRequest());

        if ($form->isValid()) {
            // pass "load demo fixtures" flag to the next step
            $context->getStorage()->set(
                'loadFixtures',
                $form->has('loadFixtures') && $form->get('loadFixtures')->getData()
            );

            $this->get('oro_user.manager')->updateUser($adminUser);

            // Update "default" organization fixture name
            $organizationName = $form->get('organization_name')->getData();
            $organizationManager = $this->get('oro_organization.organization_manager');
            $defaultOrganization = $organizationManager->getOrganizationByName('default');
            $defaultOrganization->setName($organizationName);
            $organizationManager->updateOrganization($defaultOrganization);

            /** @var ConfigManager $configManager */
            $configManager       = $this->get('oro_config.global');
            $defaultAppURL       = $configManager->get('oro_ui.application_url');
            $applicationURL      = $form->get('application_url')->getData();
            if (!empty($applicationURL) && $applicationURL !== $defaultAppURL) {
                $configManager->set('oro_ui.application_url', $applicationURL);
                $configManager->flush();
            }

            return $this->complete();
        }

        return $this->render(
            'OroInstallerBundle:Process/Step:setup.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }

    /**
     * @return User
     * @throws \RuntimeException
     */
    private function getAdminUser()
    {
        $repository = $this->getDoctrine()->getRepository('OroUserBundle:Role');

        $adminRole = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);
        if (!$adminRole) {
            throw new \RuntimeException("Administrator role wasn't loaded in fixtures.");
        }

        $adminUser = $repository->getFirstMatchedUser($adminRole);
        if (!$adminUser) {
            throw new \RuntimeException("Admin user wasn't loaded in fixtures.");
        }

        return $adminUser;
    }
}
