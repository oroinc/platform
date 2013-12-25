<?php

namespace Oro\Bundle\InstallerBundle\Process\Step;

use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class SetupStep extends AbstractStep
{
    public function displayAction(ProcessContextInterface $context)
    {
        $form = $this->createForm('oro_installer_setup');

        /** @var ConfigManager $configManager */
        $configManager = $this->get('oro_config.global');
        $form->get('company_name')->setData($configManager->get('oro_ui.application_name'));
        $form->get('company_title')->setData($configManager->get('oro_ui.application_title'));

        return $this->render(
            'OroInstallerBundle:Process/Step:setup.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }

    public function forwardAction(ProcessContextInterface $context)
    {
        $form = $this->createForm('oro_installer_setup');

        $form->handleRequest($this->getRequest());

        if ($form->isValid()) {
            // pass "load demo fixtures" flag to the next step
            $context->getStorage()->set(
                'loadFixtures',
                $form->has('loadFixtures') && $form->get('loadFixtures')->getData()
            );

            $user = $form->getData();
            $role = $this
                ->getDoctrine()
                ->getRepository('OroUserBundle:Role')
                ->findOneBy(array('role' => 'ROLE_ADMINISTRATOR'));

            $businessUnit = $this
                ->getDoctrine()
                ->getRepository('OroOrganizationBundle:BusinessUnit')
                ->findOneBy(array('name' => 'Main'));

            $user
                ->setEnabled(true)
                ->setOwner($businessUnit)
                ->addBusinessUnit($businessUnit)
                ->addRole($role);

            $this->get('oro_user.manager')->updateUser($user);

            // update company name and title if specified
            /** @var ConfigManager $configManager */
            $configManager       = $this->get('oro_config.global');
            $defaultCompanyName  = $configManager->get('oro_ui.application_name');
            $defaultCompanyTitle = $configManager->get('oro_ui.application_title');
            $companyName         = $form->get('company_name')->getData();
            $companyTitle        = $form->get('company_title')->getData();
            if (!empty($companyName) && $companyName !== $defaultCompanyName) {
                $configManager->set('oro_ui.application_name', $companyName);
            }
            if (!empty($companyTitle) && $companyTitle !== $defaultCompanyTitle) {
                $configManager->set('oro_ui.application_title', $companyTitle);
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
