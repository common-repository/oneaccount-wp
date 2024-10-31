<?php

include "vendor/autoload.php";

include "vendor/guzzlehttp/guzzle/src/Client.php";

use GuzzleHttp\Client;

final class OneaccountLibrary
{
    private $client;
    /**
     * @var OneaccountEngineInterface
     */
    private $engine;
    /**
     * @var array
     */
    private $options;

    private $verifyURL = "https://api.oneaccount.app/widget/verify";

    public function __construct(OneaccountEngineInterface $engine)
    {
        $this->client = new Client();
        $this->engine = $engine;
    }


    public function auth($body, $token = null)
    {
        if (!$body['uuid']) {
            throw new InvalidArgumentException("the uuid field is required");
        }
        if (null === $token) {
            $this->engine->set($body['uuid'], $body);

            return false;
        }

        if (!$this->verify($token, $body['uuid'])) {
            throw new RuntimeException("incorrect token");
        }
        $data = $this->engine->get($body['uuid']);

        if (isset($data['externalId'])) {
            unset($data['externalId']);
        }
        if (isset($data['uuid'])) {
            unset($data['uuid']);
        }
        return $data;
    }

    public function verify($token, $uuid)
    {
        try {
            $response = $this->client->post(
                $this->verifyURL,
                [
                    'headers' => ['Authorization' => $token],
                    'json' => ['uuid' => $uuid]
                ]
            );
        } catch (Throwable $e) {
            return false;
        }

        return $response->getStatusCode() === 200;
    }
}
