<?php
/**
 * @author Mapp Digital
 * @copyright Copyright (c) 2022 Mapp Digital US, LLC (https://www.mapp.com)
 * @package MappDigital_Cloud
 */
namespace MappDigital\Cloud\Model\Connect;

use Closure;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Utils;
use GuzzleHttp\Client as GuzzleHttpClient;
use Magento\Framework\Exception\LocalizedException;
use MappDigital\Cloud\Helper\ConnectHelper;
use Firebase\JWT\JWT;
use Psr\Http\Message\RequestInterface;

class Client
{
    protected ConnectHelper $connectHelper;
    protected string $integrationId = '';
    protected string $secret = '';
    protected string $baseUrl = '';
    protected ?GuzzleHttpClient $client = null;

    const USERAGENT = 'MappConnectClientPHP/0.1.0';

    public function __construct(
        ConnectHelper $connectHelper
    ) {
        $this->connectHelper = $connectHelper;
    }

    /**
     * @param array $options
     * @return GuzzleHttpClient
     * @throws LocalizedException
     */
    public function getClient(array $options = []): GuzzleHttpClient
    {
        if (is_null($this->client)) {
            $handlerStack = HandlerStack::create(Utils::chooseHandler());
            $handlerStack->push($this->handleAuthorizationHeader());

            $this->client = new GuzzleHttpClient([
                'base_uri' => $this->getBaseUrl(),
                'headers'  => [
                    'User-Agent' => self::USERAGENT,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'handler' => $handlerStack,
                'timeout'  => $options['timeout'] ?? 10.0
            ]);
        }

        return $this->client;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getBaseUrl(): string
    {
        if (!$this->baseUrl) {
            $this->baseUrl = $this->connectHelper->getBaseURL();
        }

        return $this->baseUrl;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getIntegrationId(): string
    {
        if (!$this->integrationId) {
            $this->integrationId = $this->connectHelper->getConfigValue('integration', 'integration_id');
        }

        return $this->integrationId;
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getSecret(): string
    {
        if (!$this->secret) {
            $this->secret = $this->connectHelper->getConfigValue('integration', 'integration_secret');
        }

        return $this->secret;
    }

    /**
     * @return bool
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function ping(): bool
    {
        $pong = $this->get('integration/' . $this->getIntegrationId() . '/ping');

        if (!$pong || !$pong['pong']) {
            return false;
        }

        return true;
    }

    /**
     * @param $config
     * @return mixed
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function connect($config)
    {
        return $this->post('integration/' . $this->getIntegrationId() . '/connect', json_encode($config));
    }

    /**
     * @return mixed
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function getMessages()
    {
        return $this->get('integration/' . $this->getIntegrationId() . '/message');
    }

    /**
     * @return mixed
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function getGroups()
    {
        return $this->get('integration/' . $this->getIntegrationId() . '/group');
    }

    /**
     * @param $url
     * @param $query
     * @return mixed
     * @throws GuzzleException|LocalizedException
     */
    public function get($url, $query = null)
    {
        $response = $this->getClient()->get($url, [
            'headers' => [
                'User-Agent' => self::USERAGENT,
                'Accept'     => 'application/json'
            ],
            'query' => $query
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * @param $subtype
     * @param $data
     * @return mixed
     * @throws GuzzleException
     * @throws LocalizedException
     */
    public function event($subtype, $data)
    {
        return $this->post(
            'integration/' . $this->getIntegrationId() . '/event?subtype=' . urlencode($subtype),
            json_encode($data)
        );
    }

    /**
     * @param $url
     * @param $data
     * @return mixed
     * @throws GuzzleException|LocalizedException
     */
    public function put($url, $data = NULL)
    {
        $req = $this->getClient()->request('PUT', $url, [
            'headers' => [
                'User-Agent' => self::USERAGENT,
                'Accept' => 'application/json',
            ],
            'body' => $data
        ]);

        return json_decode($req->getBody(), true);
    }

    /**
     * @param $url
     * @param $data
     * @return mixed
     * @throws GuzzleException|LocalizedException
     */
    public function post($url, $data = NULL)
    {
        $req = $this->getClient()->request('POST', $url, [
            'headers' => [
                'User-Agent' => self::USERAGENT,
                'Accept' => 'application/json'
            ],
            'body' => $data
        ]);

        return json_decode($req->getBody(), true);
    }

    /**
     * @param RequestInterface $request
     * @return string
     * @throws LocalizedException
     */
    public function getToken(RequestInterface $request): string
    {
        $token = [
            "request-hash" => $this->getRequestHash(
                $request->getUri()->getPath(),
                $request->getBody(),
                $request->getUri()->getQuery()),
            "exp" => time() + 3600
        ];

        return JWT::encode($token, $this->getSecret(), 'HS256');
    }

    /**
     * @return Closure
     */
    private function handleAuthorizationHeader(): Closure
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                if ($this->getSecret()) {
                    $request = $request->withHeader('auth-token', $this->getToken($request));
                }
                return $handler($request, $options);
            };
        };
    }

    /**
     * @param String $url
     * @param String|NULL $body
     * @param String|NULL $queryString
     * @return string
     */
    public function getRequestHash(string $url, string $body = null, string $queryString = null): string
    {
        $url = preg_replace('/^.*\/api\/v/', '/api/v', $url);

        if (!empty($body)) {
            $url .= "|" . $body;
        }

        if (!empty($queryString)) {
            $url .= "|" . $queryString;
        }

        return sha1($url);
    }
}
