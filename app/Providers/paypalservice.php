<?php

namespace App\Services;

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PayPalService
{
    private $apiContext;

    /**
     * Constructor to initialize the PayPal API context.
     */
    public function __construct()
    {
        $this->initializeApiContext();
    }

    /**
     * Initializes the PayPal API context with credentials and configuration.
     */
    private function initializeApiContext()
    {
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential(
                $this->getClientId(),
                $this->getClientSecret()
            )
        );

        $this->setApiContextConfig();
    }

    /**
     * Retrieves the PayPal client ID from configuration.
     *
     * @return string
     */
    private function getClientId()
    {
        return config('paypal.client_id');
    }

    /**
     * Retrieves the PayPal client secret from configuration.
     *
     * @return string
     */
    private function getClientSecret()
    {
        return config('paypal.secret');
    }

    /**
     * Sets the configuration for the PayPal API context.
     */
    private function setApiContextConfig()
    {
        $this->apiContext->setConfig([
            'mode' => $this->getApiMode(), // sandbox or live
            'http.ConnectionTimeOut' => 30,
            'log.LogEnabled' => true,
            'log.FileName' => $this->getLogFilePath(),
            'log.LogLevel' => 'ERROR', // Available options: DEBUG, INFO, WARNING, ERROR
        ]);
    }

    /**
     * Retrieves the PayPal API mode (sandbox or live) from configuration.
     *
     * @return string
     */
    private function getApiMode()
    {
        return config('paypal.mode');
    }

    /**
     * Retrieves the path to the PayPal log file.
     *
     * @return string
     */
    private function getLogFilePath()
    {
        return storage_path('logs/paypal.log');
    }

    /**
     * Returns the PayPal API context.
     *
     * @return ApiContext
     */
    public function getApiContext()
    {
        return $this->apiContext;
    }
}
