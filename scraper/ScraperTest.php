<?php
require_once dirname(__FILE__) . "/Scraper.php";

Class ScraperTest extends PHPUnit_Framework_TestCase
{
    public function __construct()
    {
        $this->download_filename = dirname(__FILE__) . '/../_download/';
    }

    /**
     * @test
     */
    public function _test_getDatesFromRange()
    {
        $scraper = new Scraper();
        $period = $scraper->getDatesFromRange('2018-08-22','2018-08-24');
        $this->assertEquals($period, ['2018-08-22','2018-08-23','2018-08-24']);
    }

    /**
     * @test
     */
    public function _test_getDateset()
    {
        $scraper = new Scraper();
        $file = $this->download_filename . '2018-08-22/1301/index.html';
        $dataset = $scraper->getDataset($file);
        $dataset_1301 = array(
            'code' => '1301',
            'date' => '2018-08-22',
            'total' => 10928,
            'asset' => 28888,
            'open' => 3245,
            'low' => 3245,
            'high' => 3295,
            'close' => 3290,
            'volume' => 3300
        );
        $this->assertEquals($dataset, $dataset_1301);
    }
}