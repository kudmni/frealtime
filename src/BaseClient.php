<?php

namespace PrCy\Frealtime;

use \PrCy\Frealtime\Exception\InvalidProtocolException;
use \PrCy\Frealtime\Exception\EmptyResponseException;
use \PrCy\Frealtime\Exception\InvalidReponseStatusCodeException;
use \PrCy\Frealtime\Exception\InvalidResponseBodyException;
use \PrCy\RabbitMQ\Producer;

abstract class BaseClient
{
    const PROTOCOL_AMQP = 'amqp';
    const PROTOCOL_HTTP = 'http';

    public $timeout = 60; // Таймаут выполнения запроса к API, сек

    protected $protocol;
    protected $httpClient;
    protected $amqpClient;

    /**
     * Конструктор класса
     *
     * @param string $protocol
     * @param array $options (смотри createHttpClient и createAmqpClient)
     * @throws InvalidProtocolException
     */
    public function __construct($protocol, $options)
    {
        if ($protocol == self::PROTOCOL_HTTP) {
            $this->httpClient = $this->createHttpClient($options);
        } else if ($protocol == self::PROTOCOL_AMQP) {
            $this->amqpClient = $this->createAmqpClient($options);
        } else {
            throw new InvalidProtocolException('Некорректный протокол взаимодействия с API: ' . $protocol);
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
            'timeout'        => $this->timeout,
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
        return new Producer(
            empty($options['host'])             ? 'localhost'   : $options['host'],
            empty($options['port'])             ? 5672          : $options['port'],
            empty($options['user'])             ? 'guest'       : $options['user'],
            empty($options['password'])         ? 'guest'       : $options['password'],
            empty($options['messagePrefix'])    ? ''            : $options['messagePrefix'],
            empty($options['appPrefix'])        ? ''            : $options['appPrefix']
        );
    }

    /**
     * Принимает все паарметры для выполнения запроса
     * и выбирает, по какому протоколу его выполнить
     *
     * @param string $httpMethod
     * @param string $httpPath
     * @param string $amqpRoutingKey
     * @param array $params
     * @param integer $priority
     * @return mixed
     */
    protected function doRequest($httpMethod, $httpPath, $amqpRoutingKey, $params = [], $priority = Producer::PRIORITY_NORMAL)
    {
        if ($this->protocol == self::PROTOCOL_HTTP) {
            return $this->doHttpRequest($httpMethod, $httpPath, $params);
        } else {
            return $this->doAmqpRequest($amqpRoutingKey, $params, $priority);
        }
    }

    /**
     * Выполняет запрос к API по amqp протоколу
     *
     * @param string $routingKey
     * @param array $params
     * @return mixed
     */
    protected function doAmqpRequest($routingKey, $params = [], $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->amqpClient->addRpcMessage($routingKey, $params, $priority, $this->timeout);
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
            throw new EmptyResponseException("                                                                 API empty response from path: $path");
        }
        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            throw new InvalidReponseStatusCodeException(
                "API bad response status code.\n"
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
