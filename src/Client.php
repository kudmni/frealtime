<?php

namespace PrCy\Frealtime;

use \PrCy\Frealtime\Exception\InvalidProtocolException;
use \PrCy\Frealtime\Exception\EmptyResponseException;
use \PrCy\Frealtime\Exception\InvalidReponseStatusCodeException;
use \PrCy\Frealtime\Exception\InvalidResponseBodyException;

/**
 * Class Client
 * @package PrCy\Frealtime
 */
class Client
{
    const PROTOCOL_AMQP = 'amqp';
    const PROTOCOL_HTTP = 'http';

    protected $protocol;
    protected $httpClient;
    protected $amqpClient;

    /**
     * Конструктор класса
     *
     * @param type $protocol
     * @param type $httpBaseUrl
     * @param type $amqpHost
     * @param type $amqpPort
     * @param type $amqpUser
     * @param type $amqpPassword
     * @throws InvalidProtocolException
     */
    public function __construct($protocol, $options)
    {
        if ($protocol == self::PROTOCOL_HTTP) {
            $this->httpClient = $this->createHttpClient($options);
        } else if ($protocol == self::PROTOCOL_AMQP) {
            $this->amqpClient = $this->createAmqpClient($options);
        } else {
            throw new InvalidProtocolException('Некорректный протокол взаимодействия с Future Realtime API: ' . $protocol);
        }
        $this->protocol = $protocol;
    }

    /**
     * Создаёт экземпляр http-клиента
     *
     * @param array $options
     * @return \GuzzleHttp\Client
     * @codeCoverageIgnore
     */
    protected function createHttpClient($options)
    {
        $baseUrl = empty($options['base_uri']) ? 'http://localhost' : $options['base_uri'];
        return new \GuzzleHttp\Client([
            'base_uri'       => $baseUrl,
            'http_errors'    => false,
            'timeout'        => 60,
            'verify'         => false
        ]);
    }

    /**
     * Создаёт экземпляр amqp-клиента
     *
     * @param type $options
     * @return \PrCy\RabbitMQ\Producer
     * @codeCoverageIgnore
     */
    protected function createAmqpClient($options)
    {
        return new \PrCy\RabbitMQ\Producer(
            empty($options['host'])      ? 'localhost'   : $options['host'],
            empty($options['port'])      ? 5672          : $options['port'],
            empty($options['user'])      ? 'guest'       : $options['user'],
            empty($options['password'])  ? 'guest'       : $options['password'],
            empty($options['prefix'])    ? ''            : $options['prefix']
        );
    }

    /**
     * Возвращает результат поиска в Google
     *
     * @param string $query
     * @param string $lang
     * @param string $geo
     * @param string $searchType
     * @param integer $page
     * @param integer $numdoc
     * @return array
     */
    public function getGoogleSerp($query, $lang = null, $geo = null, $searchType = null, $page = null, $numdoc = null)
    {
        // Оставим только заданные параметры
        $params = array_filter(
            [
                'query'      => $query,
                'lang'       => $lang,
                'geo'        => $geo,
                'searchType' => $searchType,
                'page'       => $page,
                'numdoc'     => $numdoc
            ],
            function ($value) {
                return isset($value);
            }
        );
        return $this->doRequest(
            'GET',
            '/google/search',
            'frealtime.api.google.search',
            $params
        );
    }

    /**
     * Возвращает индексацию в Google или false
     *
     * @param string $domain
     * @return mixed
     */
    public function getGoogleIndex($domain)
    {
        $serp = $this->getGoogleSerp("site:$domain");
        $result = false;
        if (is_array($serp) && array_key_exists('count', $serp)) {
            $result = (int) $serp['count'];
        }
        return $result;
    }

    /**
     * Получает данные из Яндекс.Каталога или false
     *
     * @param string $domain
     * @return mixed
     */
    public function getYandexCatalog($domain)
    {
        return $this->doRequest(
            'GET',
            '/yandex/catalog',
            'frealtime.api.yandex.catalog',
            ['domain' => $domain]
        );
    }

    /**
     * Возвращает результат поиска в Яндекс
     *
     * @param string $query
     * @param string $region
     * @param string $tld
     * @return array
     */
    public function getYandexSerp($query, $region = null, $tld = null)
    {
        // Оставим только заданные параметры
        $params = array_filter(
            ['query' => $query, 'region' => $region, 'tld' => $tld],
            function ($value) {
                return isset($value);
            }
        );
        return $this->doRequest(
            'GET',
            '/yandex/search',
            'frealtime.api.yandex.search',
            $params
        );
    }

    /**
     * Возвращает индексацию в Яндексе или false
     *
     * @param string $domain
     * @return mixed
     */
    public function getYandexIndex($domain)
    {
        $serp = $this->getYandexSerp("host:$domain | host:www.$domain");
        $result = false;
        if (is_array($serp) && array_key_exists('count', $serp)) {
            $result = (int) $serp['count'];
        }
        return $result;
    }

    /**
     * Принимает все паарметры для выполнения запроса
     * и выбирает, по какому протоколу его выполнить
     *
     * @param string $httpMethod
     * @param string $httpPath
     * @param string $amqpRoutingKey
     * @param array $params
     * @return mixed
     */
    protected function doRequest($httpMethod, $httpPath, $amqpRoutingKey, $params = [])
    {
        if ($this->protocol == self::PROTOCOL_HTTP) {
            return $this->doHttpRequest($httpMethod, $httpPath, $params);
        } else {
            return $this->doAmqpRequest($amqpRoutingKey, $params);
        }
    }

    /**
     * Выполняет запрос к API по amqp протоколу
     *
     * @param string $routingKey
     * @param array $params
     * @return mixed
     */
    protected function doAmqpRequest($routingKey, $params = [])
    {
        return $this->amqpClient->addRpcMessage($routingKey, $params);
    }

    /**
     * Выполняет запрос к API по http протоколу
     *
     * @param string $method GET или POST
     * @param string $path
     * @param array $params
     * @return mixed
     * @throws EmptyResponseException
     * @throws InvalidReponseStatusCodeException
     * @throws InvalidResponseBodyException
     */
    protected function doHttpRequest($method, $path, $params = [])
    {
        $method      = strtoupper($method);
        $dataMode    = ($method == 'GET') ? 'query' : 'form_params';
        $response    = $this->httpClient->request($method, $path, [$dataMode => $params]);
        if (empty($response)) {
            throw new EmptyResponseException("Future Realtime API empty response from path: $path");
        }
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            throw new InvalidReponseStatusCodeException(
                "Future Realtime API bad response status code.\n"
                . "Path: $path\n"
                . "Status code: $statusCode\n"
                . "Response body: " . $response->getBody()->getContents()
            );
        }
        $result = json_decode($response->getBody()->getContents(), true);
        if ($result === null) {
            throw new InvalidResponseBodyException("Future Realtime API invalid json body from path: $path");
        }
        return $result;
    }
}
