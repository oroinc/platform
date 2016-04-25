<?php

namespace OroCRM\Bundle\EmbeddedForm\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class UpdateEmbeddedFormAllowedDomains extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $forms = $manager->getRepository('OroEmbeddedFormBundle:EmbeddedForm')->findAll();

        foreach ($forms as $form) {
            $form->setAllowedDomains('*');
        }

        $manager->flush();
    }
}
