<?php

namespace PrCY\Frealtime;

use \PrCy\Frealtime\Client as FrealtimeClient;

/**
 * Class ClientTest
 * @package PrCy\Frealtime
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \PrCy\Frealtime\Exception\InvalidProtocolException
     */
    public function testConstructBadProtocol()
    {
        new FrealtimeClient('foobar', []);
    }

    public function testGetGoogleSerp()
    {
        $query   = 'test';
        $result  = ['count' => -1, 'serp' => []];
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
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

    public function testGetGoogleIndex()
    {
        $domain  = 'example.com';
        $result  = ['count' => 100500, 'serp' => []];
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
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

    public function testGetGoogleInfo()
    {
        $domain  = 'example.com';
        $result  = [
            'url' => "http://example.com/",
            "position" => 1,
            "host" => "example.com",
            "title" => "description"
        ];
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
            ->disableOriginalConstructor()
            ->setMethods(['getGoogleSerp'])
            ->getMock();

        $apiResponse = [
            "count" => 1,
            "serp" => [
                [
                    "url" => "http://example.com/",
                    "position" => 1,
                    "host" => "example.com",
                    "title" => "description"
                ]
            ]
        ];
        $frealtimeApiClient->expects($this->once())
            ->method('getGoogleSerp')
            ->with($this->equalTo('info:' . $domain))
            ->willReturn($apiResponse);
        $this->assertEquals(
            $result,
            $frealtimeApiClient->getGoogleInfo($domain)
        );
    }

    public function testGetGoogleNews()
    {
        $query   = 'site:example.com';
        $result  = ['count' => 100500];
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
            ->disableOriginalConstructor()
            ->setMethods(['doRequest'])
            ->getMock();
        $frealtimeApiClient->expects($this->once())
            ->method('doRequest')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('/google/search'),
                $this->equalTo('frealtime.api.google.search'),
                $this->equalTo([
                    'query' => $query,
                    'tbm'   => 'nws',
                ])
            )
            ->willReturn($result);
        $this->assertEquals(
            100500,
            $frealtimeApiClient->getGoogleNews('example.com')
        );
    }

    public function testGetYandexCatalog()
    {
        $domain  = 'example.com';
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
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

    public function testGetYandexSerp()
    {
        $query   = 'test';
        $result  = ['count' => -1, 'serp' => []];
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
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

    public function testGetYandexIndex()
    {
        $domain  = 'example.com';
        $result  = ['count' => 100500, 'serp' => []];
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
            ->disableOriginalConstructor()
            ->setMethods(['getYandexSerp'])
            ->getMock();
        $frealtimeApiClient->expects($this->once())
            ->method('getYandexSerp')
            ->with($this->equalTo("host:$domain | host:www.$domain"))
            ->willReturn($result);
        $this->assertEquals($result['count'], $frealtimeApiClient->getYandexIndex($domain));
    }


    public function testGetYandexTic()
    {
        $domain  = 'example.com';
        $tic     = 100500;
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
            ->disableOriginalConstructor()
            ->setMethods(['doRequest'])
            ->getMock();
        $frealtimeApiClient->expects($this->once())
            ->method('doRequest')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('/yandex/tic'),
                $this->equalTo('frealtime.api.yandex.tic'),
                $this->equalTo(['domain' => $domain])
            )
            ->willReturn(['tic' => $tic]);
        $result = $frealtimeApiClient->getYandexTic($domain);
        $this->assertEquals($tic, $result['tic']);
    }

    public function testDoAmqpRequest()
    {
        $domain  = 'example.com';

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

        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
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
    public function testDoHttpRequestEmptyResponse()
    {
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
    public function testDoHttpRequestBadStatusCode()
    {
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
    public function testDoHttpRequestInvalidJson()
    {
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

    public function testDoHttpRequestNormal()
    {
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

    public function testGetLemmas()
    {
        $text  = 'мама мыла раму';
        $result  = ["мама", "мыть", "рама"];
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
            ->disableOriginalConstructor()
            ->setMethods(['doRequest'])
            ->getMock();

        $keywords = [];
        $frealtimeApiClient->expects($this->once())
            ->method('doRequest')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('/ca/lemmas'),
                $this->equalTo('frealtime.api.ca.lemmas'),
                $this->equalTo(['text' => $text])
            )
            ->willReturn($result);
        $frealtimeApiClient->getLemmas($text);
    }

    public function testGetTfIdfByUrl()
    {
        $result  = [
            "keywordsLemmasCount" => 0,
            "keywordsLemmas"      => [],
            "lemmasCount" => 1,
            "lemmas"      => [
                [
                    "docsCount"  => 34765,
                    "tf"         => 0.017543859649122806,
                    "termsCount" => 6,
                    "idf"        => 1.620057708962932,
                    "lemma"      => "ученый",
                    "tfidf"      => 0.02842206506952512
                ]
            ]
        ];
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
            ->disableOriginalConstructor()
            ->setMethods(['doRequest'])
            ->getMock();

        $url = 'http://example.com';
        $keywords = '';
        $frealtimeApiClient->expects($this->once())
            ->method('doRequest')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('/ca/tfidf_by_url'),
                $this->equalTo('frealtime.api.ca.tfidf_by_url'),
                $this->equalTo(['url' => $url, 'keywords' => $keywords])
            )
            ->willReturn($result);

        $frealtimeApiClient->getTfIdfByUrl($url, $keywords);
    }

    public function testGetTfIdfByText()
    {
        $result  = [
            "keywordsLemmasCount" => 0,
            "keywordsLemmas"      => [],
            "lemmasCount" => 1,
            "lemmas"      => [
                [
                    "docsCount"  => 34765,
                    "tf"         => 0.017543859649122806,
                    "termsCount" => 6,
                    "idf"        => 1.620057708962932,
                    "lemma"      => "ученый",
                    "tfidf"      => 0.02842206506952512
                ]
            ]
        ];
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
            ->disableOriginalConstructor()
            ->setMethods(['doRequest'])
            ->getMock();

        $text  = 'мама мыла раму';
        $keywords = '';
        $frealtimeApiClient->expects($this->once())
            ->method('doRequest')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('/ca/tfidf_by_text'),
                $this->equalTo('frealtime.api.ca.tfidf_by_text'),
                $this->equalTo(['text' => $text, 'keywords' => $keywords])
            )
            ->willReturn($result);
        $frealtimeApiClient->getTfIdfByText($text);
    }

    public function testGetBrowserData()
    {
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
            ->disableOriginalConstructor()
            ->setMethods(['doRequest'])
            ->getMock();

        $url = 'http://example.com';
        $frealtimeApiClient->expects($this->once())
            ->method('doRequest')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('/ca/browser_data'),
                $this->equalTo('frealtime.api.ca.browser_data'),
                $this->equalTo(['url' => $url, 'user_agent' => '', 'timeout' => 30, 'referer' => ''])
            );
        $frealtimeApiClient->getBrowserData($url);
    }

    public function testGetBrowserDataWithLemmas()
    {
        $frealtimeApiClient  = $this->getMockBuilder('\PrCy\Frealtime\Client')
            ->disableOriginalConstructor()
            ->setMethods(['doRequest'])
            ->getMock();

        $url = 'http://example.com';
        $frealtimeApiClient->expects($this->once())
            ->method('doRequest')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo('/ca/browser_data_with_lemmas'),
                $this->equalTo('frealtime.api.ca.browser_data_with_lemmas'),
                $this->equalTo(['url' => $url, 'user_agent' => '', 'timeout' => 30, 'referer' => ''])
            );
        $frealtimeApiClient->getBrowserDataWithLemmas($url);
    }

}
