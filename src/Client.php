<?php

namespace PrCy\Frealtime;

use \PrCy\RabbitMQ\Producer;

/**
 * Class Client
 * @package PrCy\Frealtime
 */
class Client extends BaseClient
{
    /**
     * Возвращает результат поиска в Google
     *
     * @param string $query
     * @param string $lang
     * @param string $geo
     * @param string $searchType
     * @param integer $page
     * @param integer $numdoc
     * @param integer $priority
     * @return array
     */
    public function getGoogleSerp($query, $lang = null, $geo = null, $searchType = null, $page = null, $numdoc = null, $priority = Producer::PRIORITY_NORMAL)
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
            $params,
            $priority
        );
    }

    /**
     * Возвращает индексацию в Google или false
     *
     * @param string $domain
     * @param integer $priority
     * @return mixed
     */
    public function getGoogleIndex($domain, $priority = Producer::PRIORITY_NORMAL)
    {
        $serp = $this->getGoogleSerp("site:$domain", null, null, null, null, null, $priority);
        $result = false;
        if (is_array($serp) && array_key_exists('count', $serp)) {
            $result = (int) $serp['count'];
        }
        return $result;
    }

    /**
     * Возвращает информацию о домене в Google или false
     *
     * @param string $domain
     * @param integer $priority
     * @return mixed
     */
    public function getGoogleInfo($domain, $priority = Producer::PRIORITY_NORMAL)
    {
        $serp = $this->getGoogleSerp("info:$domain", null, null, null, null, null, $priority);
        return !empty($serp['serp'][0]) ? $serp['serp'][0] : false;
    }

    /**
     * Возвращает информацию о наличии сайта в GoogleNews
     *
     * @param string $domain
     * @param integer $priority
     *
     * @return mixed
     */
    public function getGoogleNews($domain, $priority = Producer::PRIORITY_NORMAL)
    {
        $serp = $this->doRequest(
            'GET',
            '/google/search',
            'frealtime.api.google.search',
            [
                'query' => 'site:' . $domain,
                'tbm'   => 'nws',
            ],
            $priority
        );
        return !empty($serp['count']) ? $serp['count'] : null;
    }

    /**
     * Получает данные из Яндекс.Каталога или false
     *
     * @param string $domain
     * @param integer $priority
     * @return mixed
     */
    public function getYandexCatalog($domain, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/yandex/catalog',
            'frealtime.api.yandex.catalog',
            ['domain' => $domain],
            $priority
        );
    }

    /**
     * Возвращает результат поиска в Яндекс
     *
     * @param string $query
     * @param string $region
     * @param string $tld
     * @param integer $priority
     * @return array
     */
    public function getYandexSerp($query, $region = null, $tld = null, $priority = Producer::PRIORITY_NORMAL)
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
            '/yandex_xml/search',
            'frealtime.api.yandex_xml.search',
            $params,
            $priority
        );
    }

    /**
     * Возвращает индексацию в Яндексе или false
     *
     * @param string $domain
     * @param integer $priority
     * @return mixed
     */
    public function getYandexIndex($domain, $priority = Producer::PRIORITY_NORMAL)
    {
        $serp = $this->getYandexSerp("host:$domain | host:www.$domain", null, null, $priority);
        $result = false;
        if (is_array($serp) && array_key_exists('count', $serp)) {
            $result = (int) $serp['count'];
        }
        return $result;
    }

    /**
     * Получает данные тИЦ из Яндекс.Вебмастера
     *
     * @param string $domain
     * @param integer $priority
     * @return array
     */
    public function getYandexTic($domain, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/yandex/tic',
            'frealtime.api.yandex.tic',
            ['domain' => $domain],
            $priority
        );
    }

    /**
     * Получает данные тИЦ из архива
     *
     * @param string $domain
     * @param integer $priority
     * @return array
     */
    public function getYandexLastTic($domain, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/yandex/last_tic',
            'frealtime.api.yandex.last_tic',
            ['domain' => $domain],
            $priority
        );
    }

    /**
     * Получает данные ИКС из Яндекс.Вебмастера
     *
     * @param string $domain
     * @param integer $priority
     * @return array
     */
    public function getYandexSqi($domain, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/yandex/sqi',
            'frealtime.api.yandex.sqi',
            ['domain' => $domain],
            $priority
        );
    }

    /**
     * Получает данные ИКС из счетчика
     *
     * @param string $domain
     * @param integer $priority
     * @return array
     */
    public function getYandexCycounter($domain, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/yandex/cycounter',
            'frealtime.api.yandex.cycounter',
            ['domain' => $domain],
            $priority
        );
    }

    /**
     * Получает данные ИКС из Яндекс.Вебмастера для списка доменов
     * (максимум 100 доменов)
     *
     * @param array $domains
     * @param integer $priority
     * @return array
     */
    public function getYandexSqiBatch(array $domains, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/yandex/sqi_batch',
            'frealtime.api.yandex.sqi_batch',
            ['domains' => json_encode($domains)],
            $priority
        );
    }

    /**
     * Получает данные о достижениях сайта из Яндекс.Вебмастера
     *
     * @param string $domain
     * @param integer $priority
     * @return array
     */
    public function getYandexAchievements($domain, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/yandex/achievements',
            'frealtime.api.yandex.achievements',
            ['domain' => $domain],
            $priority
        );
    }

    /**
     * Получает леммы для указанного текста
     *
     * @param string $text
     * @param integer $priority
     * @return mixed
     */
    public function getLemmas($text, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/ca/lemmas',
            'frealtime.api.ca.lemmas',
            ['text' => $text],
            $priority
        );
    }

    /**
     * Получает леммы для указанного текста построчно
     *
     * @param string $text
     * @param integer $priority
     * @return mixed
     */
    public function getLemmasPerLine($text, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/ca/lemmas_per_line',
            'frealtime.api.ca.lemmas_per_line',
            ['text' => $text],
            $priority
        );
    }


    /**
     * Получает оценку расстояния для каждой из фраз указанного текста
     *
     * @param string $phrases
     * @param string $text
     * @param integer $priority
     * @return mixed
     */
    public function getDistanceMeasure($phrases, $text, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/ca/distance_measure',
            'frealtime.api.ca.distance_measure',
            ['phrases' => $phrases, 'text' => $text],
            $priority
        );
    }


    /**
     * Получает tf-idf для лемм (и ключевых слов) по указанному url
     *
     * @param string $url
     * @param string $keywords
     * @param integer $priority
     * @return mixed
     */
    public function getTfIdfByUrl($url, $keywords = '', $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/ca/tfidf_by_url',
            'frealtime.api.ca.tfidf_by_url',
            ['url' => $url, 'keywords' => $keywords],
            $priority
        );
    }

    /**
     * Получает tf-idf для лемм (и ключевых слов) по указанному тексту
     *
     * @param string $text
     * @param string $keywords
     * @param integer $priority
     * @return mixed
     */
    public function getTfIdfByText($text, $keywords = '', $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/ca/tfidf_by_text',
            'frealtime.api.ca.tfidf_by_text',
            ['text' => $text, 'keywords' => $keywords],
            $priority
        );
    }

    /**
     * Получает контент страницы из каспера
     *
     * @param string $url
     * @param string $user_agent
     * @param integer $timeout
     * @param integer $priority
     *
     * @return array
     */
    public function getBrowserData($url, $user_agent = '', $referer = '', $timeout = 30, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/ca/browser_data',
            'frealtime.api.ca.browser_data',
            [
                'url'        => $url,
                'timeout'    => $timeout,
                'user_agent' => $user_agent,
                'referer'    => $referer
            ],
            $priority
        );
    }

    /**
     * Получает контент страницы из каспера + разбивает текст на леммы
     *
     * @param string $url
     * @param string $keywords
     * @param string $user_agent
     * @param integer $timeout
     * @param integer $priority
     *
     * @return array
     */
    public function getBrowserDataWithLemmas($url, $keywords = '', $user_agent = '', $referer = '', $timeout = 30, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/ca/browser_data_with_lemmas',
            'frealtime.api.ca.browser_data_with_lemmas',
            [
                'url'        => $url,
                'keywords'   => $keywords,
                'timeout'    => $timeout,
                'user_agent' => $user_agent,
                'referer'    => $referer
            ],
            $priority
        );
    }

    /**
     * Получает данные по домену из SimilarWeb
     *
     * @param string $domain
     * @param string $user_agent
     * @param integer $timeout
     * @param integer $priority
     *
     * @return array
     */
    public function getSimilarWebData($domain, $user_agent = '', $referer = '', $timeout = 30, $priority = Producer::PRIORITY_NORMAL)
    {
        return $this->doRequest(
            'GET',
            '/sw/parse_sw_with_salt',
            'frealtime.api.sw.parse_sw_with_salt',
            [
                'domain'     => $domain,
                'timeout'    => $timeout,
                'user_agent' => $user_agent,
                'referer'    => $referer
            ],
            $priority
        );
    }
}
