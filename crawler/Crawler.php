<?php
class Crawler
{
    private $codes;
    private $url;
    private $login_url;
    private $params;
    private $download_path;
    private $date;

    public $headers;

    /**
     * __construct
     */
    public function __construct($url, $download_path)
    {
        date_default_timezone_set('Asia/Tokyo');
        $this->setUrl($url);
        $this->setDate();
        $this->download_path = $download_path;
        $this->headers = array(
            "User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36"
        );
    }

    /**
     * URL取得
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * URL設定
     */
    public function setUrl($url)
    {
        $this->url = htmlspecialchars_decode($url);
    }

    /**
     * 日付取得
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * 日付設定
     */
    public function setDate($date = null)
    {
        // 日付を指定する場合
        if ($date) {
            $date = new DateTime($date);
            $this->date = $date->format('Y-m-d');
            return;
        }

        // 自動で設定
        $hour = intval(date('H'));
        $date = new DateTime();
        if (0 <= $hour && $hour <= 4) {
            $this->date = $date->modify('-1 days')->format('Y-m-d');
        } elseif(18 <= $hour && $hour <= 24) {
            $this->date = $date->format('Y-m-d');
        } else {
            exit('この時間帯にクローラーを実行しないでください。');
        }
    }

    /**
     * ログインURL取得
     */
    public function getUrlLogin()
    {
        return $this->login_url;
    }

    /**
     * ログインURL設定
     */
    public function setUrlLogin($url)
    {
        $this->login_url = htmlspecialchars_decode($url);
    }

    /**
     * パラメーター設定
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * ログイン、クッキーファイル生成
     *
     * @return boolean
     */
    public function login()
    {
        $ch = curl_init(htmlspecialchars_decode($this->login_url));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . "/tmp/cookie.txt");
        curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($info['http_code'] !== 200) {
            return false;
        }
        return true;
    }

    /**
     * 現在の銘柄一覧を返す。
     *
     * @return boolean
     */
    public function getStockCode()
    {
        return $this->codes;
    }

    /**
     * 日本株の銘柄コードを全て取得する。jsonの取得に失敗した場合falseを返す。
     * エラーで中断してしまったときは、$start_codeからの銘柄コードを取得する。
     *
     * @param string json_file 
     * @param int    start_code
     *
     * @return boolean
     */
    public function setStockCode($json_file, $start_code = 0)
    {
        $json = file_get_contents($json_file);
        if ($json) {
            $array = json_decode($json , true);
            $stocks = array_map(function ($v) { return $v['code']; }, $array);
            if ($start_code) {
                $offset = array_search($start_code, $stocks);
                $stocks = array_slice($stocks, $offset);
            }
            $this->codes = $stocks;
        }
        return false;
    }

    /**
     * 全銘柄のファイルを保存する
     * 
     * @param int $interval
     */
    public function downloadAllFile($interval = 2)
    {
        foreach ($this->codes as $code) {
            $data = $this->downloadFile($code);
            if ($data) {
                $this->saveFile($data, $code);
            }else {
                exit();
            }
            sleep($interval);
        }
    }

    /**
     * curlで特定ページのダウンロード
     *
     * @return string/boolean $data html file
     */
    public function downloadFile($code)
    {
        $url = $this->url . $code;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . "/tmp/cookie.txt");
        $data = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_errno($ch);

        if ($error !== CURLE_OK) {
            error_log("Error: {$error}", 1, ADMIN_MAIL);
            return false;
        }

        if ($info['http_code'] !== 200) {
            error_log("Error: {$info['http_code']}", 1, ADMIN_MAIL);
            return false;
        }

        curl_close($ch);
        return $data;
    }

    /**
     * 取得したファイルを指定フォルダに保存
     *
     * @return boolean
     */
    public function saveFile($data, $code)
    {
        $dir = "{$this->download_path}{$this->date}/{$code}/";
        $this->makeDir($dir);
        $data = file_put_contents("{$dir}/index.html", $data);
        return $data ? true : false;
    }

    /**
     * ディレクトリがない場合作成
     */
    private function makeDir($dir)
    {
        $parts = explode('/', $dir);
        $dir = '';
        foreach($parts as $part) {
            if(!is_dir($dir .= "/$part")) {
                mkdir($dir);
            }
        }
    }
}
