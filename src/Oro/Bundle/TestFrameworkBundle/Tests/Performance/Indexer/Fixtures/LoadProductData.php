<?php
namespace Oro\Bundle\FlexibleEntityBundle\Tests\Performance;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Entity\Product;

/**
* Load products
*
* Execute with "php app/console doctrine:fixtures:load"
*
*/
class LoadProductData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{

    const DEFAULT_COUNTER_VALUE = 90;
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Entities Counter
     * @var integer
     */
    protected $counter;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        if (isset($container->counter)) {
            $this->counter = $container->counter;
        } else {
            $this->counter = self::DEFAULT_COUNTER_VALUE;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadProducts($manager);
    }

    /**
     * Load products
     *
     * @param ObjectManager $manager
     *
     * @return array
     */
    public function loadProducts(ObjectManager $manager)
    {
        $product = new Product();
        $messages = array();

        $indSku = 1;
        for ($ind= 1; $ind <= $this->counter; $ind++) {
            list($msec, $sec) = explode(" ", microtime());
            $start=$sec + $msec;
            $prodSku = 'perf-sku-' . $indSku;
            $product->setName($prodSku);
            $manager->persist($product);
            $messages[]= "Product ".$prodSku." has been created";
            $indSku++;
            if (!($ind % 100)) {
                list($msec, $sec) = explode(" ", microtime());
                $stop=$sec + $msec;
                echo "\nGenerated {$ind} entities " . round($stop - $start, 4) . " sec";
            }
        }
        list($msec, $sec) = explode(" ", microtime());
        $start=$sec + $msec;

        echo "\nFlushing";
        $manager->flush();
        list($msec, $sec) = explode(" ", microtime());
        $stop=$sec + $msec;

        echo "\nFlushed " . round($stop - $start, 4) . " sec";

        return $messages;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 3;
    }
}
