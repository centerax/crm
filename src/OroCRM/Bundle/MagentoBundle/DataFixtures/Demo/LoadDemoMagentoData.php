<?php

namespace OroCRM\Bundle\MagentoBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;
use OroCRM\Bundle\MagentoBundle\Entity\CustomerGroup;
use OroCRM\Bundle\MagentoBundle\Entity\MagentoSoapTransport;
use OroCRM\Bundle\MagentoBundle\Entity\Store;
use OroCRM\Bundle\MagentoBundle\Entity\Website;

class LoadDemoMagentoData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $om)
    {
        $website = new Website();
        $website->setCode('admin')
            ->setName('Admin');

        $om->persist($website);

        $store = new Store();
        $store->setCode('admin')
            ->setName('Admin')
            ->setWebsite($website);

        $om->persist($website);

        $group = new CustomerGroup();
        $group->setName('General');

        $om->persist($group);

        $transport = new MagentoSoapTransport();
        $transport->setApiUser('api_user');
        $transport->setApiKey('api_key');
        $transport->setWsdlUrl('http://magento.domain');
        $om->persist($transport);

        $channel = new Channel();
        $channel->setType('magento');
        $channel->setConnectors(['customer']);
        $channel->setName('Magento');

        $channel->setTransport($transport);

        $om->persist($channel);

        $this->persistDemoCustomers($om, $website, $store, $group, $channel);
        $om->flush();
    }

    /**
     * @param ObjectManager                                     $om
     * @param Website                                           $website
     * @param Store                                             $store
     * @param CustomerGroup $group
     * @param Channel $channel
     */
    protected function persistDemoCustomers(
        ObjectManager $om,
        Website $website,
        Store $store,
        CustomerGroup $group,
        Channel $channel
    ) {
        for ($i = 0; $i < 50; ++$i) {
            $firstName = $this->generateFirstName();
            $lastName  = $this->generateLastName();
            $birthday  = $this->generateBirthday();
            $email     = $this->generateEmail($firstName, $lastName);
            $vat       = rand(pow(10, 14 - 1), pow(10, 14) - 1);

            $customer = new Customer();
            $customer->setWebsite($website)
                ->setChannel($channel)
                ->setStore($store)
                ->setFirstName($firstName)
                ->setLastName($lastName)
                ->setEmail($email)
                ->setBirthday($birthday)
                ->setVat($vat)
                ->setGroup($group)
                ->setCreatedAt(new \DateTime('now'))
                ->setUpdatedAt(new \DateTime('now'))
                ->setOriginId($i + 1);
            $om->persist($customer);
        }
    }

    /**
     * Generates an email
     *
     * @param  string $firstName
     * @param  string $lastName
     *
     * @return string
     */
    private function generateEmail($firstName, $lastName)
    {
        $uniqueString = substr(uniqid(rand()), -5, 5);
        $domains      = array('yahoo.com', 'gmail.com', 'example.com', 'hotmail.com', 'aol.com', 'msn.com');
        $randomIndex  = rand(0, count($domains) - 1);
        $domain       = $domains[$randomIndex];

        return sprintf("%s.%s_%s@%s", strtolower($firstName), strtolower($lastName), $uniqueString, $domain);
    }

    /**
     * Generate a first name
     *
     * @return string
     */
    private function generateFirstName()
    {
        $firstNamesDictionary = $this->loadDictionary('first_names.txt');
        $randomIndex          = rand(0, count($firstNamesDictionary) - 1);

        return trim($firstNamesDictionary[$randomIndex]);
    }

    /**
     * Loads dictionary from file by name
     *
     * @param  string $name
     *
     * @return array
     */
    private function loadDictionary($name)
    {
        static $dictionaries = array();

        if (!isset($dictionaries[$name])) {
            $dictionary = array();
            $fileName   = __DIR__ . DIRECTORY_SEPARATOR . '../../../DemoDataBundle/DataFixtures/Demo/dictionaries' .
                DIRECTORY_SEPARATOR . $name;
            foreach (file($fileName) as $item) {
                $dictionary[] = trim($item);
            }
            $dictionaries[$name] = $dictionary;
        }

        return $dictionaries[$name];
    }

    /**
     * Generates a last name
     *
     * @return string
     */
    private function generateLastName()
    {
        $lastNamesDictionary = $this->loadDictionary('last_names.txt');
        $randomIndex         = rand(0, count($lastNamesDictionary) - 1);

        return trim($lastNamesDictionary[$randomIndex]);
    }

    /**
     * Generates a date of birth
     *
     * @return \DateTime
     */
    private function generateBirthday()
    {
        // Convert to timetamps
        $min = strtotime('1950-01-01');
        $max = strtotime('2000-01-01');

        // Generate random number using above bounds
        $val = rand($min, $max);

        // Convert back to desired date format
        return new \DateTime(date('Y-m-d', $val), new \DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 300;
    }
}
