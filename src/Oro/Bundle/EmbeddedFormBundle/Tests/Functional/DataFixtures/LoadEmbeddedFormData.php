<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;
use Oro\Bundle\EmbeddedFormBundle\Tests\Functional\Stubs\EmbeddedFormStub;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadEmbeddedFormData extends AbstractFixture implements DependentFixtureInterface
{
    public const EMBEDDED_FORM = 'embedded_form';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $embeddedForm = new EmbeddedForm();
        $embeddedForm->setFormType(EmbeddedFormStub::class);
        $embeddedForm->setCss('input { color: red; }');
        $embeddedForm->setSuccessMessage('Form has been submitted successfully');
        $embeddedForm->setTitle('Send Feedback');
        $embeddedForm->setOwner($this->getReference('organization'));

        $this->addReference(self::EMBEDDED_FORM, $embeddedForm);

        $manager->persist($embeddedForm);
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadOrganization::class];
    }
}
