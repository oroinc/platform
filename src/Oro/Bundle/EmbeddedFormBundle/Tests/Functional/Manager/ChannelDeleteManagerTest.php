<?php

namespace OroCRM\Bundle\EmbeddedFormBundle\Tests\Functional\Manager;

use Oro\Bundle\EmbeddedFormBundle\Entity\EmbeddedForm;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Tests\Functional\AbstractChannelDataDeleteTest;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class ChannelDeleteManagerTest extends AbstractChannelDataDeleteTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->entityClassName = 'OroEmbeddedFormBundle:EmbeddedForm';
    }

    /**
     * {@inheritdoc}
     */
    protected function createRelatedEntity(Channel $channel)
    {
        $embeddedForm = new EmbeddedForm();
        $embeddedForm->setTitle('test');
        $embeddedForm->setCss('');
        $embeddedForm->setFormType('test');
        $embeddedForm->setSuccessMessage('test');
        $embeddedForm->setChannel($channel);
        $this->em->persist($embeddedForm);
    }
}
