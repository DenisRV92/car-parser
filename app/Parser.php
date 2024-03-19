<?php

namespace App;

use DOMDocument;
use DOMXPath;

class Parser implements ParserInterface
{
    public static $headers = [
        'Condition',
        'google_product_category',
        'store_code',
        'vehicle_fulfillment(option:store_code)',
        'Brand',
        'Model',
        'Year',
        'Color',
        'Mileage',
        'VIN',
        'Price',
        'image_link',
        'link_template'
    ];

    public $info = [
        'Make:',
        'Model:',
        'Year:',
        'Color:',
        'Mileage:',
        'VIN:'
    ];
    public int $maxPage = 0;
    const CONDITION = 'Used';
    const GOOGLE_PRODUCT_CATEGORY = '123';
    const STORE_CODE = 'xpremium';
    const VEHICLE_FULFILMENT = 'in_store:premium';

    public $arrConst = [
        self::CONDITION,
        self::GOOGLE_PRODUCT_CATEGORY,
        self::STORE_CODE,
        self::VEHICLE_FULFILMENT
    ];

    /**
     * @param string $url
     * @return void
     */
    public function run(string $url = 'https://premiumcarsfl.com/listing-list-full/')
    {
        file_put_contents('log.txt', $url . PHP_EOL, FILE_APPEND);

        $mainPage = $this->fetch($url);
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($mainPage);


        $xpath = new DOMXpath($dom);
        //общее кол-во страниц
        $countPages = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'page-numbers') and not(contains(concat(' ', normalize-space(@class), ' '), 'next'))]");
        //получаем все автомобили с href на главной страницы
        $listFull = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'listing-image')]");

        if (count($countPages) > 1) {
            $this->maxPage = (int)$countPages[count($countPages) - 1]->nodeValue;
            $currentPage = $url === 'https://premiumcarsfl.com/listing-list-full/' ? 1 : $this->getCurrentPage($url);
            if ($currentPage <= $this->maxPage) {
                foreach ($listFull as $element) {
                    $url = $element->getAttribute('href');
                    $this->getDetailsInfo($url);
                }
                if ($currentPage == $this->maxPage) {
                    file_put_contents('log.txt', date('Y-m-d H:i:s') . ' - Парсинг завершен' . PHP_EOL, FILE_APPEND);
                    die();
                }
                $this->run('https://premiumcarsfl.com/listing-list-full/page/' . $currentPage + 1 . '/');
            }
        } else {
            file_put_contents('log.txt', date('Y-m-d H:i:s') . ' - Страница не найдена' . PHP_EOL, FILE_APPEND);
            die();
        }
    }

    /**
     * @param string $url
     * @return string
     */
    public function fetch(string $url): string
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.77 Safari/537.36');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    /**
     * Заходить на котнкретный автомобиль
     * @param string $url
     * @return void
     */
    public function getDetailsInfo(string $url)
    {

        $detailPage = $this->fetch($url);
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($detailPage);


        $xpath = new DOMXpath($dom);
        $listKeyInfo = $xpath->query("//li//div[contains(concat(' ', normalize-space(@class), ' '), 'text')]");
        $listValueInfo = $xpath->query("//li//div[contains(concat(' ', normalize-space(@class), ' '), 'value')]");
        $csvFile = fopen('cars_data.csv', 'a');
        $result = [];
        foreach ($this->info as $item) {
            $value = '';
            foreach ($listKeyInfo as $key => $keyInfo) {
                if ($keyInfo->nodeValue === $item) {
                    $value = $listValueInfo[$key]->nodeValue;
                    break;
                }
            }
            if ($item === 'Mileage:') {
                $result[] = $value . ' miles';
            } else {
                $result[] = $value;
            }
        }
        //получаем цену
        $price = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'price-text')]")->item(0);
        $result[] = $price->nodeValue;
        //находим главную картинку и затем получаем url
        $imageLink = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), 'size-voiture-gallery-large')]")->item(0);
        $result[] = $imageLink->getAttribute('src');
        $result[] = $url . '?store=xpremium';
        $result = array_merge($this->arrConst, $result);

        fputcsv($csvFile, $result);
    }

    /**
     * Получаем текущую странциу
     * @param string $url
     * @return int
     */
    public function getCurrentPage(string $url): int
    {
        $url = rtrim($url, '/');
        $parts = explode('/', $url);
        return (int)end($parts);
    }
}
