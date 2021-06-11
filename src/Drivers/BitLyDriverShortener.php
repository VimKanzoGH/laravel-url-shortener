<?php

namespace CodeOfDigital\LaravelUrlShortener\Drivers;

use CodeOfDigital\LaravelUrlShortener\Exceptions\BadRequestException;
use CodeOfDigital\LaravelUrlShortener\Exceptions\InvalidApiTokenException;
use CodeOfDigital\LaravelUrlShortener\Exceptions\InvalidDataException;
use CodeOfDigital\LaravelUrlShortener\Exceptions\InvalidResponseException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

class BitLyDriverShortener extends DriverShortener
{
    protected $client;
    protected $object;

    public function __construct(ClientInterface $client, $token, $domain)
    {
        $this->client = $client;
        $this->object = [
            'allow_redirects' => false,
            'base_uri' => 'https://api-ssl.bitly.com',
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$token}",
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'domain' => $domain
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    public function shortenAsync($url, array $options = [])
    {
        $options = array_merge_recursive(Arr::add($this->object, 'json.long_url', $url), ['json' => $options]);
        $request = new Request('POST', '/v4/shorten');

        return $this->client->sendAsync($request, $options)->then(
            function (ResponseInterface $response) {
                return str_replace('http://', 'https://', json_decode($response->getBody()->getContents())->link);
            },
            function (RequestException $e) {
                $this->getErrorMessage($e->getCode(), $e->getMessage());
            }
        );
    }

    protected function getErrorMessage($code, $message = null)
    {
        switch ($code) {
            case 400: throw new BadRequestException($message);
            case 403: throw new InvalidApiTokenException($message);
            case 422: throw new InvalidDataException($message);
            default: throw new InvalidResponseException($message);
        }
    }
}
