<?php
/*
 * This file is part of the siftware/live-connect package.
 *
 * (c) 2014 Siftware <code@siftware.com>
 *
 * Author - Darren Beale @bealers
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Siftware;

use GuzzleHttp\Client;

/**
* Wrapper to GuzzleHttp\Client
*/
class LiveRequest
{
    private $endpoint;
    private $authToken;

    private $client;

    private $defaultHeaders;

    public function __construct($endpoint, $authToken = null)
    {
        $this->endpoint     = $endpoint;

        $this->authToken    = $authToken;

        $this->client       = new Client();

        $this->defaultHeaders = array(
            'User-Agent' => 'PHP - https://github.com/siftware/live-connect'
        );

    }

    // --

    public function post($payLoad = array())
    {
        $request = $this->client->createRequest("POST", $this->endpoint);

        $this->setDefaultHeaders($request);

        $postBody = $request->getBody();

        foreach ($payLoad as $key => $value) {
            $postBody->setField($key, $value);
        }

        return $this->send($request);
    }

    // --

    public function get()
    {
        $request = $this->client->createRequest("GET", $this->endpoint);

        $this->setDefaultHeaders($request);

        return $this->send($request);
    }

    /**
    * @return \GuzzleHttp\Message\ResponseInterface
    * @todo improve exception handling
    */
    private function send($request)
    {
        Logger::debug($request);

        try {
            $response = $this->client->send($request);
        } catch (\Exception $e) {
            if ($e->hasResponse()) {

                $response = $e->getResponse();
                Logger::error($response->getStatusCode() . ": " . $response->getReasonPhrase());

                // the error message from Live Connect
                $json = json_decode($response->getBody());

                if (property_exists($json, 'error_description')) {

                    Logger::debug($json->error_description);
                } elseif (property_exists($json, 'error')) {

                    Logger::debug($json->error->message);
                } else {

                    Logger::debug("Unknown error when communicating with Live Connect");
                }
            } else {

                Logger::error("Unknown error when communicating with Live Connect");
            }

            return false;
        }

        // OK this is a bit weird, the code receiving this is expecting
        // to json_decode it and json_decode($response->json()) give unexpected
        // results. @todo get to the bottom of this
        return json_encode($response->json());
    }

    /**
    * Headers we should send with every request
    */
    private function setDefaultHeaders($request)
    {
        if (isset($this->authToken)) {
            $request->setHeader('Authorization', "Bearer " . $this->authToken);
        }

        foreach ($this->defaultHeaders as $header => $value) {
            $request->setHeader($header, $value);
        }
    }

}