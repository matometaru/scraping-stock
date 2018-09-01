<?php
/** 
 * $dataset = array('code' => '1301', 'date' => '2018-08-22' ...);
 * $datasets = array(
 *     [0] => array
 *     (
 *         [0] => array('code' => '1301', 'date' => '2018-08-22' ...),
 *         [1] => array('code' => '1302', 'date' => '2018-08-22' ...),
 *     ),
 *     [1] => array
 *     (
 *         [0] => array('code' => '1301', 'date' => '2018-08-23' ...),
 *         [1] => array('code' => '1302', 'date' => '2018-08-23' ...),
 *     ),
 * );
 */

class Scraper
{

    public $download_filename;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->regexp_v1 = [
            'total'  => '/【株式】<\/span>\d{1,2}\/\d{1,2}.*?(?P<total>(?:\\d|,|.)+)千株/',
            'asset'  => '/<th>自己資本<\/th><td.*?>(?P<asset>.*)<\/td>/',
            'profit' => '/<\/th>[\s\S]*<td>(?P<sales>[\d|.|,]+)<\/td><td>(?P<profit>[\d|.|,]+)<\/td><td>(?P<expense>[\d|.|,]+)<\/td><td>(?P<income>[\d|.|,]+)<\/td><td>(?P<eps>[\d|.|,]+)<\/td><td>(?P<diviend>[\d|.|,]+)<\/td>[\s\S]*<tr class="yoso/',
            'open'   => '/opn_p.*?>(?P<open>(?:\\d|,|.)+)</',
            'high'   => '/hig_p.*?>(?P<high>(?:\\d|,|.)+)</',
            'low'    => '/low_p.*?>(?P<low>(?:\\d|,|.)+)</',
            'close'  => '/cls_p.*?>(?P<close>(?:\\d|,|.)+)</',
            'volume' => '/出来高<\/th>[\s\S]*<span.*?>(?P<volume>(?:\\d|,|.)+)</'
        ];

        $this->download_filename = dirname(__FILE__) . '/../_download/';
    }

    /**
     * 開始日から終了日までのスクレイピングしたデータセットを返す
     *
     * @param string $start Start date
     * @param string $end End date
     *
     * @return array $datasets
     */
    public function getDateRangeDataset($start, $end, int $version = 1)
    {
        // $start_dateから$end_dateの日付を配列化
        $period = $this->getDatesFromRange($start, $end);
        $datasets = [];
        foreach ($period as $key => $date) {
            $file = "{$this->download_filename}{$date}/";
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($file,
                    FilesystemIterator::CURRENT_AS_FILEINFO |
                    FilesystemIterator::KEY_AS_PATHNAME |
                    FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach($files as $file) {
                if($file->getBasename() === '.DS_Store') continue;
                $datasets[$key][] = $this->getDataset($file, $version);
            }
        }
        return $datasets;
    }

    /**
     * Generate an array of string dates between 2 dates
     *
     * @param string $start Start date
     * @param string $end End date
     * @param string $format Output format (Default: Y-m-d)
     *
     * @return array
     */
    public function getDatesFromRange($start, $end, $format = 'Y-m-d')
    {
        $array = array();
        $interval = new DateInterval('P1D');

        $realEnd = new DateTime($end);
        $realEnd->add($interval);

        $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

        foreach($period as $date) { 
            $array[] = $date->format($format); 
        }

        return $array;
    }

    /**
     * 指定したファイルのDatasetを取得する
     *
     * @param string $file    /hoge/hoge/YYYY-mm-dd/1301/index.html
     * @param int    $version DOM version
     *
     * @return array $dataset 指定日の株式データ
     */
    public function getDataset(string $file, int $version = 1)
    {
        $code = basename(dirname($file));
        $date = basename(dirname(dirname($file)));
        $exist = file_exists($file);
        if ($exist) {
            $html = file_get_contents($file);
            $dataset = array(
                'code' => $code,
                'date' => $date,
                'total' => 0,
                'asset' => 0,
                'open' => 0,
                'low' => 0,
                'high' => 0,
                'close' => 0,
                'volume' => 0,
            );
            switch ($version) {
                case 1: 
                    // echo $file;
                    if (preg_match($this->regexp_v1['total'], $html, $matches)) {
                        $dataset['total'] = $this->numberUnFormat($matches['total']);
                    }
                    if (preg_match($this->regexp_v1['asset'], $html, $matches)) {
                        $dataset['asset'] = $this->numberUnFormat($matches['asset']);
                    }
                    if (preg_match($this->regexp_v1['open'], $html, $matches)) {
                        $dataset['open'] = $this->numberUnFormat($matches['open']);
                    }
                    if (preg_match($this->regexp_v1['low'], $html, $matches)) {
                        $dataset['low'] = $this->numberUnFormat($matches['low']);
                    }
                    if (preg_match($this->regexp_v1['high'], $html, $matches)) {
                        $dataset['high'] = $this->numberUnFormat($matches['high']);
                    }
                    if (preg_match($this->regexp_v1['close'], $html, $matches)) {
                        $dataset['close'] = $this->numberUnFormat($matches['close']);
                    }
                    if (preg_match($this->regexp_v1['volume'], $html, $matches)) {
                        $dataset['volume'] = $this->numberUnFormat($matches['volume']);
                    }
                    break;
                case 2:
                    // DOM構造が変わった場合の処理
                    break;
                default:
                    $datesets = [];
                    break;
            }
            return $dataset;
        }
        return false;
    }

    /**
     * number_formtの逆
     */
    private function numberUnFormat($number, $force_number = true, $dec_point = '.', $thousands_sep = ',')
    {
        if ($force_number) {
            $number = preg_replace('/^[^\d]+/', '', $number);
        } else if (preg_match('/^[^\d]+/', $number)) {
            return false;
        }
        $type = (strpos($number, $dec_point) === false) ? 'int' : 'float';
        $number = str_replace(array($dec_point, $thousands_sep), array('.', ''), $number);
        settype($number, $type);
        return $number;
    }

    /**
     * データセット配列をDBに保存する
     *
     * @param array $datasets 
     */
    public function saveDataset($datasets)
    {
        // 保存
        $db_manager = new DbManager();
        $db_manager->connect('master',array(
            'dsn' => DB_DSN,
            'user' => DB_USERNAME,
            'password' => DB_PASSWORD,
        ));
        $pdo = $db_manager->getConnection('master');
        $sql_duplicate = 'SELECT COUNT(*) FROM datasets WHERE code = :code AND date = :date';
        $sql = 'INSERT INTO datasets (code, date, total, asset, open, high, low, close, volume) VALUES (:code, :date, :total, :asset, :open, :high, :low, :close, :volume)';
        $stmt2 = $pdo->prepare($sql_duplicate);
        $stmt = $pdo->prepare($sql);

        foreach ($datasets as $date_datasets) {
            foreach ($date_datasets as $dataset) {
                // 銘柄番号と日付の重複がある場合、1件もINSERTしない
                if ($stmt2->execute(array(':code' => $dataset['code'],':date' => $dataset['date']))) {
                    if((int)$stmt2->fetchColumn() !== 0) {
                        break;
                    }
                }
                $stmt->execute(array(
                    ':code' => $dataset['code'],
                    ':date' => $dataset['date'],
                    ':total' => $dataset['total'],
                    ':asset' => $dataset['asset'],
                    ':open' => $dataset['open'],
                    ':high' => $dataset['high'],
                    ':low' => $dataset['low'],
                    ':close' => $dataset['close'],
                    ':volume' => $dataset['volume']
                ));
            }
        }
    }
}
