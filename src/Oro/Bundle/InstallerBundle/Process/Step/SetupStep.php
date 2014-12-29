<?php

namespace Oro\Bundle\InstallerBundle\Process\Step;

use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;

use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;

class SetupStep extends AbstractStep
{
    public function displayAction(ProcessContextInterface $context)
    {
        $form = $this->createForm('oro_installer_setup');

        /** @var ConfigManager $configManager */
        $configManager = $this->get('oro_config.global');

        $form->get('organization_name')->setData($configManager->get('oro_ui.organization_name'));
        $form->get('application_url')->setData($configManager->get('oro_ui.application_url'));

        return $this->render(
            'OroInstallerBundle:Process/Step:setup.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }

    public function forwardAction(ProcessContextInterface $context)
    {
        $adminUser = $this
            ->getDoctrine()
            ->getRepository('OroUserBundle:User')
            ->findOneBy(array('username' => LoadAdminUserData::DEFAULT_ADMIN_USERNAME));

        if (!$adminUser) {
            throw new \RuntimeException("Admin user wasn't loaded in fixtures.");
        }

        $form = $this->createForm('oro_installer_setup');
        $form->setData($adminUser);

        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {
            // pass "load demo fixtures" flag to the next step
            $context->getStorage()->set(
                'loadFixtures',
                $form->has('loadFixtures') && $form->get('loadFixtures')->getData()
            );

            $this->get('oro_user.manager')->updateUser($adminUser);

            /** @var ConfigManager $configManager */
            $configManager           = $this->get('oro_config.global');
            $defaultOrganizationName = $configManager->get('oro_ui.organization_name');
            $organizationName        = $form->get('organization_name')->getData();
            if (!empty($organizationName) && $organizationName !== $defaultOrganizationName) {
                $organizationManager = $this->get('oro_organization.organization_manager');
                $organization        = $organizationManager->getOrganizationByName('default');
                $organization->setName($organizationName);

                $organizationManager->updateOrganization($organization);
            }

            $defaultAppURL       = $configManager->get('oro_ui.application_url');
            $applicationURL      = $form->get('application_url')->getData();
            if (!empty($applicationURL) && $applicationURL !== $defaultAppURL) {
                $configManager->set('oro_ui.application_url', $applicationURL);
            }
            $configManager->flush();

            return $this->complete();
        }

        return $this->render(
            'OroInstallerBundle:Process/Step:setup.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }
}
