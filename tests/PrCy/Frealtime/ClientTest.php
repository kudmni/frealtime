<?php

namespace PrCY\Frealtime;

use \PrCy\Frealtime\Client as FrealtimeClient;

/**
 * Class ClientTest
 * @package PrCy\Frealtime
 */
class ClientTest extends \PHPUnit_Framework_TestCase {
	/**
	 * @expectedException \PrCy\Frealtime\Exception\InvalidProtocolException
	 */
	public function testConstructBadProtocol() {
		new FrealtimeClient('foobar', []);
	}

	public function testGetGoogleSerp() {
		$query	 = 'test';
		$result	 = ['count' => -1, 'serp' => []];
		$frealtimeApiClient	 = $this->getMockBuilder('\PrCy\Frealtime\Client')
			->disableOriginalConstructor()
			->setMethods(['doRequest'])
			->getMock();
		$frealtimeApiClient->expects($this->once())
			->method('doRequest')
			->with(
				$this->equalTo('GET'),
				$this->equalTo('/google/search'),
				$this->equalTo('frealtime.api.google.search'),
				$this->equalTo(['query' => $query])
			)
			->willReturn($result);
		$this->assertEquals(
			$result,
			$frealtimeApiClient->getGoogleSerp($query)
		);
	}

	public function testGetGoogleIndex() {
		$domain	 = 'example.com';
		$result	 = ['count' => 100500, 'serp' => []];
		$frealtimeApiClient	 = $this->getMockBuilder('\PrCy\Frealtime\Client')
			->disableOriginalConstructor()
			->setMethods(['getGoogleSerp'])
			->getMock();
		$frealtimeApiClient->expects($this->once())
			->method('getGoogleSerp')
			->with($this->equalTo('site:' . $domain))
			->willReturn($result);
		$this->assertEquals(
			$result['count'],
			$frealtimeApiClient->getGoogleIndex($domain)
		);
	}

	public function testGetYandexCatalog() {
		$domain	 = 'example.com';
		$frealtimeApiClient	 = $this->getMockBuilder('\PrCy\Frealtime\Client')
			->disableOriginalConstructor()
			->setMethods(['doRequest'])
			->getMock();
		$frealtimeApiClient->expects($this->once())
			->method('doRequest')
			->with(
				$this->equalTo('GET'),
				$this->equalTo('/yandex/catalog'),
				$this->equalTo('frealtime.api.yandex.catalog'),
				$this->equalTo(['domain' => $domain])
			)
			->willReturn(false);
		$this->assertFalse($frealtimeApiClient->getYandexCatalog($domain));
	}

	public function testGetYandexSerp() {
		$query	 = 'test';
		$result	 = ['count' => -1, 'serp' => []];
		$frealtimeApiClient	 = $this->getMockBuilder('\PrCy\Frealtime\Client')
			->disableOriginalConstructor()
			->setMethods(['doRequest'])
			->getMock();
		$frealtimeApiClient->expects($this->once())
			->method('doRequest')
			->with(
				$this->equalTo('GET'),
				$this->equalTo('/yandex/search'),
				$this->equalTo('frealtime.api.yandex.search'),
				$this->equalTo(['query' => $query])
			)
			->willReturn($result);
		$this->assertEquals($result, $frealtimeApiClient->getYandexSerp($query));
	}

	public function testGetYandexIndex() {
		$domain	 = 'example.com';
		$result	 = ['count' => 100500, 'serp' => []];
		$frealtimeApiClient	 = $this->getMockBuilder('\PrCy\Frealtime\Client')
			->disableOriginalConstructor()
			->setMethods(['getYandexSerp'])
			->getMock();
		$frealtimeApiClient->expects($this->once())
			->method('getYandexSerp')
			->with($this->equalTo("host:$domain | host:www.$domain"))
			->willReturn($result);
		$this->assertEquals($result['count'], $frealtimeApiClient->getYandexIndex($domain));
	}

	public function testDoAmqpRequest() {
		$domain	 = 'example.com';

		$amqpClientMock = $this->getMockBuilder('\PrCy\RabbitMQ\Producer')
			->disableOriginalConstructor()
			->getMock();
		$amqpClientMock->expects($this->once())
			->method('addRpcMessage')
			->with(
				$this->equalTo('frealtime.api.yandex.catalog'),
				$this->equalTo(['domain' => $domain])
			)
			->willReturn(false);

		$frealtimeApiClient	 = $this->getMockBuilder('\PrCy\Frealtime\Client')
			->disableOriginalConstructor()
			->setMethods(['createAmqpClient'])
			->getMock();
		$frealtimeApiClient->expects($this->once())
			->method('createAmqpClient')
			->willReturn($amqpClientMock);

		$frealtimeApiClient->__construct(FrealtimeClient::PROTOCOL_AMQP, []);
		$this->assertFalse($frealtimeApiClient->getYandexCatalog($domain));
	}

	/**
	 * @expectedException \PrCy\Frealtime\Exception\EmptyResponseException
	 */
	public function testDoHttpRequestEmptyResponse() {
		$domain = 'example.com';

		$httpClientMock = $this->getMockBuilder('\GuzzleHttp\Client')
			->disableOriginalConstructor()
			->getMock();
		$httpClientMock->expects($this->once())
			->method('request')
			->with(
				$this->equalTo('GET'),
				$this->equalTo('/yandex/catalog'),
				$this->equalTo(['query' => ['domain' => $domain]])
			)
			->willReturn(null);

		$frealtimeApiClient = $this->getMockBuilder('\PrCy\Frealtime\Client')
			->disableOriginalConstructor()
			->setMethods(['createHttpClient'])
			->getMock();
		$frealtimeApiClient->expects($this->once())
			->method('createHttpClient')
			->willReturn($httpClientMock);

		$frealtimeApiClient->__construct(FrealtimeClient::PROTOCOL_HTTP, []);

		$frealtimeApiClient->getYandexCatalog($domain);
	}

	/**
	 * @expectedException \PrCy\Frealtime\Exception\InvalidReponseStatusCodeException
	 */
	public function testDoHttpRequestBadStatusCode() {
		$domain = 'example.com';

		$streamMock = $this->getMockBuilder('\GuzzleHttp\Psr7\Stream')
			->disableOriginalConstructor()
			->setMethods(['getContents'])
			->getMock();
		$streamMock->expects($this->any())
			->method('getContents')
			->willReturn('Internal server error');

		$responseMock = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')
			->disableOriginalConstructor()
			->getMock();
		$responseMock->expects($this->once())
			->method('getStatusCode')
			->willReturn(500);
		$responseMock->expects($this->once())
			->method('getBody')
			->willReturn($streamMock);

		$httpClientMock = $this->getMockBuilder('\GuzzleHttp\Client')
			->disableOriginalConstructor()
			->getMock();
		$httpClientMock->expects($this->once())
			->method('request')
			->with(
				$this->equalTo('GET'),
				$this->equalTo('/yandex/catalog'),
				$this->equalTo(['query' => ['domain' => $domain]])
			)
			->willReturn($responseMock);

		$frealtimeApiClient = $this->getMockBuilder('\PrCy\Frealtime\Client')
			->disableOriginalConstructor()
			->setMethods(['createHttpClient'])
			->getMock();
		$frealtimeApiClient->expects($this->once())
			->method('createHttpClient')
			->willReturn($httpClientMock);

		$frealtimeApiClient->__construct(FrealtimeClient::PROTOCOL_HTTP, []);
		$frealtimeApiClient->getYandexCatalog($domain);
	}

	/**
	 * @expectedException \PrCy\Frealtime\Exception\InvalidResponseBodyException
	 */
	public function testDoHttpRequestInvalidJson() {
		$domain = 'example.com';

		$streamMock = $this->getMockBuilder('\GuzzleHttp\Psr7\Stream')
			->disableOriginalConstructor()
			->setMethods(['getContents'])
			->getMock();
		$streamMock->expects($this->any())
			->method('getContents')
			->willReturn('Invalid json');

		$responseMock = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')
			->disableOriginalConstructor()
			->getMock();
		$responseMock->expects($this->once())
			->method('getStatusCode')
			->willReturn(200);
		$responseMock->expects($this->once())
			->method('getBody')
			->willReturn($streamMock);

		$httpClientMock = $this->getMockBuilder('\GuzzleHttp\Client')
			->disableOriginalConstructor()
			->getMock();
		$httpClientMock->expects($this->once())
			->method('request')
			->with(
				$this->equalTo('GET'),
				$this->equalTo('/yandex/catalog'),
				$this->equalTo(['query' => ['domain' => $domain]])
			)
			->willReturn($responseMock);

		$frealtimeApiClient = $this->getMockBuilder('\PrCy\Frealtime\Client')
			->disableOriginalConstructor()
			->setMethods(['createHttpClient'])
			->getMock();
		$frealtimeApiClient->expects($this->once())
			->method('createHttpClient')
			->willReturn($httpClientMock);

		$frealtimeApiClient->__construct(FrealtimeClient::PROTOCOL_HTTP, []);
		$frealtimeApiClient->getYandexCatalog($domain);
	}

	public function testDoHttpRequestNormal() {
		$domain = 'example.com';

		$streamMock = $this->getMockBuilder('\GuzzleHttp\Psr7\Stream')
			->disableOriginalConstructor()
			->setMethods(['getContents'])
			->getMock();
		$streamMock->expects($this->any())
			->method('getContents')
			->willReturn(json_encode(false));

		$responseMock = $this->getMockBuilder('\GuzzleHttp\Psr7\Response')
			->disableOriginalConstructor()
			->getMock();
		$responseMock->expects($this->once())
			->method('getStatusCode')
			->willReturn(200);
		$responseMock->expects($this->once())
			->method('getBody')
			->willReturn($streamMock);

		$httpClientMock = $this->getMockBuilder('\GuzzleHttp\Client')
			->disableOriginalConstructor()
			->getMock();
		$httpClientMock->expects($this->once())
			->method('request')
			->with(
				$this->equalTo('GET'),
				$this->equalTo('/yandex/catalog'),
				$this->equalTo(['query' => ['domain' => $domain]])
			)
			->willReturn($responseMock);

		$frealtimeApiClient = $this->getMockBuilder('\PrCy\Frealtime\Client')
			->disableOriginalConstructor()
			->setMethods(['createHttpClient'])
			->getMock();
		$frealtimeApiClient->expects($this->once())
			->method('createHttpClient')
			->willReturn($httpClientMock);

		$frealtimeApiClient->__construct(FrealtimeClient::PROTOCOL_HTTP, []);
		$this->assertFalse($frealtimeApiClient->getYandexCatalog($domain));
	}
}
