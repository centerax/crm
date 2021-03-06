<?php

namespace OroCRM\Bundle\MagentoBundle\Provider;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\IntegrationBundle\Provider\SOAPTransport as BaseSOAPTransport;
use Oro\Bundle\IntegrationBundle\Provider\TransportTypeInterface;

/**
 * Magento SOAP transport
 * used to fetch and pull data to/from Magento instance
 * with sessionId param using SOAP requests
 *
 * @package OroCRM\Bundle\MagentoBundle
 */
class SoapTransport extends BaseSOAPTransport implements TransportTypeInterface
{
    /** @var string */
    protected $sessionId;

    /** @var Mcrypt */
    protected $encoder;

    public function __construct(Mcrypt $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * {@inheritdoc}
     */
    public function init(ParameterBag $settings)
    {
        $apiUser = $settings->get('api_user', false);
        $apiKey  = $settings->get('api_key', false);
        $apiKey  = $this->encoder->decryptData($apiKey);

        if (!$apiUser || !$apiKey) {
            throw new InvalidConfigurationException(
                "Magento SOAP transport require 'api_key' and 'api_user' settings to be defined."
            );
        }

        parent::init($settings);

        /** @var string sessionId returned by Magento API login method */
        $this->sessionId = $this->client->login($apiUser, $apiKey);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function call($action, array $params = [])
    {
        return parent::call($action, array_merge([$this->sessionId], $params));
    }

    /**
     * Return true if oro extension available on remote instance
     *
     * @return bool
     */
    public function isExtensionAvailable()
    {
        try {
            $isExtensionInstalled = $this->call(StoreConnector::ACTION_PING);
            $isExtensionInstalled = !empty($isExtensionInstalled->version);
        } catch (\Exception $e) {
            $isExtensionInstalled = false;
        }

        return $isExtensionInstalled;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrm.magento.transport.soap.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return 'orocrm_magento_soap_transport_setting_form_type';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return 'OroCRM\\Bundle\\MagentoBundle\\Entity\\MagentoSoapTransport';
    }
}
