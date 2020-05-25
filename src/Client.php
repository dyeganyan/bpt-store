<?php

namespace Kialex\BptStore;

use GuzzleHttp;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Client
 * @package kialex\BptStore
 * @see https://dev-api.bpt-store.com/api-docs/
 */
class Client
{
    /**
     * API Version Number
     */
    const API_VERSION = 1;

    /**
     * Default connectrion attempts to BPT Storage
     */
    const DEFAULT_CONNECTION_ATTEMPTS = 3;

    /**
     * DEV API URI
     */
    const DEV_URl = 'https://dev-api.bpt-store.com/api/v{apiVersionNumber}/';

    /**
     * PROD API URI
     */
    const PROD_URl = 'https://api.bpt-store.com/api/v{apiVersionNumber}/';

    /**
     * @var array list of necessary options
     */
    protected $options;

    /**
     * @var GuzzleClient
     */
    protected $httpClient;

    /**
     * @var string
     */
    private $userUuid;

    /**
     * @var
     */
    private $authToken;

    /**
     * Client constructor.
     * @param $options request options:
     * ```
     * [
     *      'login' => 'login', // Required
     *      'password' => 'password', // Required
     *      'sandbox' => false, // Default `false`
     *      'maxAttempts' => '5', // Default `3`
     *      'url' => 'https://api.bpt-store.com', // Default: `DEV_URL` or `PROD_URL`
     *      'versionNumber' => '1', // Default: `1`
     * ]
     * ```
     */
    public function __construct(array $options)
    {
        $this->processOptions($options);
        $this->httpClient = new GuzzleClient(['base_uri' => $this->options['url']]);
    }

    /**
     * @param array $options
     */
    protected function processOptions(array $options): void
    {
        if (!isset($options['login'])) {
            throw new \InvalidArgumentException('Login is required!');
        }
        if (!isset($options['password'])) {
            throw new \InvalidArgumentException('Password is required!');
        }
        $options['sandbox'] = $options['sandbox'] ?? false;
        $options['url'] = str_replace(
            '{apiVersionNumber}',
            $this->options['versionNumber'] ?? self::API_VERSION,
            $options['sandbox'] ? self::DEV_URl : self::PROD_URl
        );
        $options['maxAttempts'] = $options['maxAttempts'] ?? self::DEFAULT_CONNECTION_ATTEMPTS;

        $this->options = $options;
    }

    /**
     * Resolve aliases such as `baseUrl`, `userUuid`, `authToken`
     * @param string $string example: `{baseUrl}/users/{userUiid}/files`
     * @return string
     */
    public function resolveAliases(string $string): string
    {
        $replacements = [
            '/{baseUrl}/' => $this->options['url'],
            '/{userUuid}/' => $this->userUuid,
            '/{authToken}/' => $this->authToken,
        ];

        return preg_replace(array_keys($replacements), array_values($replacements), $string);
    }

    /**
     * Make request to the BPT Store
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        static $attempt = 0;
        $this->ensureAuth();
        $defaultOptions = ['headers' => ['Authorization' => $this->authToken]];

        try {
            $response = $this->httpClient->request(
                $method,
                $this->resolveAliases($uri),
                array_merge_recursive($defaultOptions, $options)
            );
        } catch (RequestException $exception) {
            if ($attempt < $this->options['maxAttempts'] && $exception->getCode() === 401) {
                ++$attempt;
                $this->ensureAuth(true);
                return $this->request($method, $uri, $options);
            }

            throw $exception;
        }

        return $response;
    }

    /**
     * Make sure the auth token recieved
     * @param $force bool
     * @return void
     */
    public function ensureAuth($force = false): void
    {
        if (!$force && $this->authToken && $this->userUuid) {
            return;
        }

        $resp = $this->httpClient->request('POST', 'login', [
            RequestOptions::JSON => [
                'login' => $this->options['login'],
                'password' => $this->options['password']
            ]
        ]);

        $data = GuzzleHttp\json_decode($resp->getBody(), true);
        $this->authToken = $data['authToken'];
        $this->userUuid = $data['userUuid'];
    }
}
