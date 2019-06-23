<?php
/**
 * Action.php
 *
 * API 获取、更新数据，处理前端 AJAX 请求
 *
 * @author 熊猫小A
 */
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

class BangumiAPI
{
    /**
     * 使用 curl 代替 file_get_contents()
     *
     * @access public
     */
    public static function curlFileGetContents($_url)
    {
        $myCurl = curl_init($_url);
        //不验证证书
        curl_setopt($myCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($myCurl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($myCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($myCurl, CURLOPT_HEADER, false);
        curl_setopt($myCurl, CURLOPT_REFERER, 'https://bgm.tv/');
        $content = curl_exec($myCurl);
        //关闭
        curl_close($myCurl);
        return $content;
    }

    /**
     * 获取在看数据并格式化返回
     *
     * @return mixed
     */
    private static function __getCollectionRawData($ID)
    {
        $apiUrl = 'https://api.bgm.tv/user/' . $ID . '/collection?cat=playing';
        $data = self::curlFileGetContents($apiUrl);
        if ($data == 'null') {
            return '-1'; // 没有标记数据
        }

        $data = json_decode($data, true);

        $weekdays = array('Mon.', 'Tue.', 'Wed.', 'Thu', 'Fri', 'Sat', 'Sun');
        $collections = array();
        foreach ($data as $item) {
            $collect = array(
                'name' => $item['subject']['name'],
                'name_cn' => $item['subject']['name_cn'],
                'url' => $item['subject']['url'],
                'status' => $item['ep_status'],
                'count' => $item['subject']['eps_count'],
                'air_date' => $item['subject']['air_date'],
                'air_weekday' => $weekdays[$item['subject']['air_weekday'] - 1],
                'img' => str_replace('http://', 'https://', $item['subject']['images']['large']),
                'id' => $item['subject']['id'],
            );
            array_push($collections, $collect);
        }
        return $collections;
    }

    /**
     * 检查缓存是否过期
     *
     * @access  private
     * @param   string    $FilePath           缓存路径
     * @param   int       $ValidTimeSpan      有效时间，Unix 时间戳，s
     * @return  mixed     正常数据: 未过期; 1:已过期; -1：无缓存或缓存无效
     */
    private static function __isCacheExpired($FilePath, $ValidTimeSpan)
    {
        if (!file_exists($FilePath)) {
            return -1;
        }

        $content = json_decode(file_get_contents($FilePath), true);
        if (!array_key_exists('time', $content) || $content['time'] < 1) {
            return -1;
        }

        if (time() - $content['time'] > $ValidTimeSpan) {
            return 1;
        }

        return $content;
    }

    /**
     * 读取与更新本地缓存，格式化返回数据
     *
     * @access public
     * @return string
     */
    public static function updateCacheAndReturn($ID, $PageSize, $From, $ValidTimeSpan)
    {
        $cache = self::__isCacheExpired(__DIR__ . '/json/bangumi.json', $ValidTimeSpan);

        if ($cache == -1 || $cache == 1) {
            // 缓存无效，重新请求，数据写入
            $raw = self::__getCollectionRawData($ID);
            if ($raw == -1 || !count($raw)) {
                // 请求数据为空
                $cache = array('time' => 1, 'data' => array());
            } else {
                $cache = array('time' => time(), 'data' => $raw);
            }
            file_put_contents(__DIR__ . '/json/bangumi.json', json_encode($cache));
        } 

        $data = $cache['data'];
        $total = count($data);
        
        if ($total == 0) {
            // 当前没有数据，把缓存时间重置为 1，下次请求自动刷新
            $cache['time'] = 1;
            file_put_contents(__DIR__ . '/json/bangumi.json', json_encode($cache));
            return json_encode(array());
        }

        if ($From < 0 || $From > $total - 1) {
            echo json_encode(array());
        } else {
            $end = min($From + $PageSize, $total);
            $out = array();
            for ($index = $From; $index < $end; $index++) {
                array_push($out, $data[$index]);
            }
            return json_encode($out);
        }
    }
}

class PandaBangumi_Action extends Widget_Abstract_Contents implements Widget_Interface_Do
{
    /**
     * 返回请求的 HTML
     * @access public
     */
    public function action()
    {
        header("Content-type: application/json");
        $options = Helper::options();
        $ID = $options->plugin('PandaBangumi')->ID;
        $PageSize = $options->plugin('PandaBangumi')->PageSize;
        $ValidTimeSpan = $options->plugin('PandaBangumi')->ValidTimeSpan;
        $From = $_GET['from'];
        if ($PageSize == -1) {
            $PageSize = 1000000;
        }
        echo BangumiAPI::updateCacheAndReturn($ID, $PageSize, $From, $ValidTimeSpan);
    }
}