<?php

/*
 * Base class for USPS Web API Tools
 * originally under MIT License
 * https://packagist.org/packages/binarydata/usps-php-api
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Vincent Gabriel
 * @author    stephen waite <stephen.waite@cmsvt.com>
 * @copyright Copyright (c) 2012 Vincent Gabriel
 * @copyright Copyright (c) 2022 stephen waite <stephen.waite@cmsvt.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\USPS;

use LaLit\Array2XML;
use LaLit\XML2Array;

/**
 * USPS Base class
 * used to perform the actual api calls
 * @since 1.0
 * @author Vincent Gabriel
 */

 
class USPSBase
{
    // v3 production and test endpoints
    const LIVE_API_URL = 'https://apis.usps.com/addresses/v3';
    const TEST_API_URL = 'https://apis-tem.usps.com/addresses/v3';

    /**
     * @var string - OAuth2 Bearer Token for USPS API v3
     */
    protected $accessToken = '';

    /**
     * @var array - stores last API response
     */
    protected $response = [];

    /**
     * @var int - stores last HTTP status code
     */
    protected $httpStatus = 0;

    /**
     * @var string - error message
     */
    protected $errorMessage = '';

    /**
     * @var bool - use test endpoint
     */
    public static $testMode = false;

    /**
     * @var array - last cURL headers
     */
    protected $headers = [];

    /**
     * Constructor
     * @param string $accessToken OAuth2 Bearer token for API authentication
     */
    public function __construct($accessToken = '')
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Set the USPS OAuth2 Bearer token
     * @param string $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Core HTTP request method for v3 REST endpoints
     * @param string $path API endpoint path (e.g., '/address')
     * @param array $query Query parameters (for GET requests)
     * @param string $method 'GET' or 'POST'
     * @param array $headers Additional headers (optional)
     * @return array Decoded JSON response
     */
    protected function apiRequest($path, $query = [], $method = 'GET', $headers = [])
    {
        $baseUrl = self::$testMode ? self::TEST_API_URL : self::LIVE_API_URL;
        $url = $baseUrl . $path;

        if (!empty($query) && $method === 'GET') {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init($url);
        $defaultHeaders = [
            "Authorization: Bearer {$this->accessToken}",
            "Accept: application/json",
        ];
        if ($method === 'POST') {
            $defaultHeaders[] = "Content-Type: application/json";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($method === 'POST' && !empty($query)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));
        }

        $result = curl_exec($ch);
        $this->httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->headers = curl_getinfo($ch);
        if ($result === false) {
            $this->errorMessage = curl_error($ch);
            curl_close($ch);
            return [];
        }

        $decoded = json_decode($result, true);
        $this->response = $decoded ?? [];
        curl_close($ch);
        return $this->response;
    }

    /**
     * Validate and standardize a US address using /address
     * @param array $params (see OpenAPI: streetAddress, state, city or ZIPCode, etc.)
     * @return array API response
     */
    public function validateAddress(array $params)
    {
        // Requires at least streetAddress, state, and either city or ZIPCode
        return $this->apiRequest('/address', $params, 'GET');
    }

    /**
     * Look up city and state by ZIP code using /city-state
     * @param string $zipCode
     * @return array API response
     */
    public function getCityStateByZip($zipCode)
    {
        return $this->apiRequest('/city-state', ['ZIPCode' => $zipCode], 'GET');
    }

    /**
     * Look up ZIP Code by address using /zipcode
     * @param array $params (see OpenAPI: streetAddress, city, state, etc.)
     * @return array API response
     */
    public function getZipCodeByAddress(array $params)
    {
        // Requires streetAddress, city, state
        return $this->apiRequest('/zipcode', $params, 'GET');
    }

    /**
     * Get last API response as array
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Get last HTTP status code
     */
    public function getStatus()
    {
        return $this->httpStatus;
    }

    /**
     * Get last error message
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Get last response headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}

      // Nothing matched
        return null;
    }
}
