<?php
require_once dirname(__FILE__) . "/Crawler.php";

Class CrawlerTest extends PHPUnit_Framework_TestCase
{

    public function __construct()
    {
        date_default_timezone_set('Asia/Tokyo');
        $this->url = 'http://localhost:8000/_download/2018-08-30/';
        $this->download_path = dirname(__FILE__) . '/../_tests/_download/';
        $this->json_file = dirname(__FILE__) . '/../crawler/stocks/stocklist.json';
    }

    /**
     * @test jsonから配列取得テスト
     */
    public function _test_getStockCode()
    {
        $crawler = new Crawler($this->url, $this->download_path);
        $crawler->setStockCode($this->json_file);
        $codes = $crawler->getStockCode();
        $this->assertEquals(count($codes), 3802);
        $crawler->setStockCode($this->json_file, 1332);
        $codes = $crawler->getStockCode();
        $this->assertEquals(count($codes), 3801);
    }

    /**
     * @test ダウンロード成功/失敗のテスト
     */
    public function _test_downloadFile()
    {
        $crawler = new Crawler($this->url, $this->download_path);
        $crawler->setStockCode($this->json_file);
        $data = $crawler->downloadFile('3798');
        if ($data) {
            $regexp = '/<title>(?P<title>.+)</';
            preg_match($regexp, $data, $matches);
            $title = '3798ＵＬＳグループ(ＵＬＳＧ) | 四季報：株価・ニュース・業績 | 会社四季報オンライン';
            $this->assertEquals($matches['title'], $title);
        } else {
            // ダウンロード失敗
            $this->assertEquals($data, false);
        }
    }

    /**
     * @test ファイル保存成功/失敗のテスト
     */
    public function _test_saveFile()
    {
        $crawler = new Crawler($this->url, $this->download_path);
        $crawler->setStockCode($this->json_file);
        $data = $crawler->downloadFile('1301/');
        $save = $crawler->saveFile($data, '1301');
        $this->assertEquals($save, true);
    }

    /**
     * @test 日付の設定テスト
     */
    public function _test_setDate()
    {
        $date = new DateTime();
        $specified_date = new DateTime('2018-07-01');
        $specified_date = $specified_date->format('Y-m-d');
        $current_date = $date->format('Y-m-d');
        $before_date = $date->modify('-1 days')->format('Y-m-d');
        // 自動取得日
        $hour = intval(date('H'));
        $crawler = new Crawler($this->url, $this->download_path);
        $crawler->setDate();
        $date = $crawler->getDate();
        if (0 <= $hour && $hour <= 4) {
            $this->assertEquals($date, $before_date);
        } elseif(18 <= $hour && $hour <= 24) {
            $this->assertEquals($date, $current_date);
        }
        // 指定日
        $crawler->setDate('2018-07-01');
        $date = $crawler->getDate();
        $this->assertEquals($date, $specified_date);
    }
}