<?php

namespace OroCRM\Bundle\B2CMockBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\MagentoBundle\Entity\CartStatus;

class LoadShoppingCartStatusData extends AbstractFixture
{
    /**
     * @var array
     */
    protected $data = array(
        'open'                     => 'Open',
        'lost'                     => 'Lost',
        'converted'                => 'Converted',
        'converted_to_opportunity' => 'Converted to opportunity',
    );

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $name => $label) {
            $method = new CartStatus($name);
            $method->setLabel($label);
            $manager->persist($method);
        }

        $manager->flush();
    }
}
