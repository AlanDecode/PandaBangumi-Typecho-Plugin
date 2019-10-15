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

require_once 'simple_html_dom.php';

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
        curl_setopt($myCurl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36');
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
     * @return array
     */
    private static function __getWatchedCollectionRawDataHelper($url)
    {
        $data = self::curlFileGetContents($url);
        if ($data == 'null') {
            return array(); // 没有标记数据
        }

        $data = json_decode($data, true)[0];

        $result = array();
        foreach ($data['collects'] as $collect) {
            // 只处理已看
            if ($collect['status']['id'] != 2) continue;

            foreach ($collect['list'] as $item) {
                array_push($result, array(
                    'name' => $item['subject']['name'],
                    'name_cn' => $item['subject']['name_cn'],
                    'url' => $item['subject']['url'],
                    'img' => str_replace('http://', 'https://', $item['subject']['images']['large']),
                    'id' => $item['subject']['id'],
                ));
            }
        }

        return $result;
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

    private static function __parseFromDoc($doc) {
        $result = array();
        $bgmBase = 'https://bgm.tv';
        foreach ($doc->find('#browserItemList li.item') as $item) {
            $name_cn = $item->find('h3 a', 0)->text();
            $name = $name_cn;
            if ($item->find('h3 small', 0) != null)
                $name = $item->find('h3 small', 0)->text();

            $res = array(
                'name_cn' => $name_cn,
                'name' => $name,
                'url' => $bgmBase.$item->find('h3 a', 0)->href,
                'img' => str_replace('cover/s/', 'cover/l/',$item->find('img.cover', 0)->src),
                'id' => str_replace('item_', '', $item->id)
            );

            if (empty($res['img']))
                $res['img'] = str_replace('cover/s/', 'cover/l/', 
                    $item->find('img.cover', 0)->getAttribute('data-cfsrc'));

            array_push($result, $res);
        }
        return $result;
    }

    /**
     * 通过网页解析在看列表
     * 
     * @access public
     * @param  string $Type 获取类型：anime, real
     * @param  string $ID Bangumi ID
     * @return array
     */
    public static function __getWatchedCollectionRawDataByWebHelper($ID, $Type)
    {
        // 初始 URL
        $bgmBase = 'https://bgm.tv';
        $url = "https://bgm.tv/{$Type}/list/{$ID}/collect";
        $html = self::curlFileGetContents($url);
        if ($html == 'null') {
            return array(); // 没有标记数据
        }

        $doc = str_get_html($html);

        // 解析页面链接
        $urls = array();
        $pagerEls = $doc->find('#multipage a.p');
        foreach ($pagerEls as $pagerEl) {
            $urls[] = $bgmBase.$pagerEl->href;
        }
        $urls = array_unique($urls);

        $result = array();
        $Limit = Helper::options()->plugin('PandaBangumi')->Limit;
        
        // 保存第一页
        $result = array_merge($result, self::__parseFromDoc($doc));

        // 若不够
        while (count($result) < $Limit && count($urls)) {
            $url = array_shift($urls);
            $html = self::curlFileGetContents($url);
            if ($html == 'null') break;
            $doc = str_get_html($html);

            $result = array_merge($result, self::__parseFromDoc($doc));
        }

        return $result;
    }

    /**
     * 读取与更新本地已看缓存，格式化返回已看数据
     * 
     * @access public
     * @return string
     */
    public static function updateWatchedCacheAndReturn($ID, $PageSize, $From, $ValidTimeSpan)
    {
        $cache = self::__isCacheExpired(__DIR__ . '/json/watched.json', $ValidTimeSpan);

        // 缓存过期或缓存无效
        if ($cache == -1 || $cache == 1) {
            // 缓存无效，重新请求，数据写入

            $appId = 'bgm25a91b0a9bfd7a';

            $method = Helper::options()->plugin('PandaBangumi')->ParseMethod;

            $watchedAnime = array();
            $watchedReal = array();
            if ($method == 'webpage') {
                $watchedAnime = self::__getWatchedCollectionRawDataByWebHelper($ID, 'anime');
                $watchedReal = self::__getWatchedCollectionRawDataByWebHelper($ID, 'real');
            } else {
                $watchedAnime = self::__getWatchedCollectionRawDataHelper(
                    'https://api.bgm.tv/user/' . $ID . '/collections/anime?app_id=' . $appId . '&max_results=25'
                );
                $watchedReal = self::__getWatchedCollectionRawDataHelper(
                    'https://api.bgm.tv/user/' . $ID . '/collections/real?app_id=' . $appId . '&max_results=25'
                );
            }

            $cache = array('time' => time(), 'data' => array(
                        'anime' => $watchedAnime,
                        'real' => $watchedReal)
                    );
            // 若全空，很可能是请求失败，则下次强制刷新
            if (!count($watchedAnime) && !count($watchedReal)) {
                $cache['time'] = 1;
            }

            file_put_contents(__DIR__ . '/json/watched.json', json_encode($cache));
        }

        $cate = array_key_exists('cate', $_GET) ? $_GET['cate'] : 'anime';
        if (!array_key_exists($cate, $cache['data'])) 
            return json_encode(array());

        $data = $cache['data'][$cate];
        $total = count($data);

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

    /**
     * 读取与更新本地缓存，格式化返回数据
     *
     * @access public
     * @return string
     */
    public static function updateCacheAndReturn($ID, $PageSize, $From, $ValidTimeSpan)
    {
        $cache = self::__isCacheExpired(__DIR__ . '/json/watching.json', $ValidTimeSpan);

        if ($cache == -1 || $cache == 1) {
            // 缓存无效，重新请求，数据写入
            $raw = self::__getCollectionRawData($ID);
            if ($raw == -1 || count($raw) == 0) {
                // 请求数据为空
                $cache = array('time' => 1, 'data' => array());
            } else {
                $cache = array('time' => time(), 'data' => $raw);
            }
            file_put_contents(__DIR__ . '/json/watching.json', json_encode($cache));
        } 

        $data = $cache['data'];
        $total = count($data);
        
        if ($total == 0) {
            // 当前没有数据，把缓存时间重置为 1，下次请求自动刷新
            $cache['time'] = 1;
            file_put_contents(__DIR__ . '/json/watching.json', json_encode($cache));
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
        if (!array_key_exists('type', $_GET)) {
            echo json_encode(array());
            exit;
        }

        $options = Helper::options();
        $ID = $options->plugin('PandaBangumi')->ID;
        $PageSize = $options->plugin('PandaBangumi')->PageSize;
        $ValidTimeSpan = $options->plugin('PandaBangumi')->ValidTimeSpan;
        $From = $_GET['from'];
        if ($PageSize == -1) {
            $PageSize = 1000000;
        }

        if (strtolower($_GET['type']) == 'watching')
            echo BangumiAPI::updateCacheAndReturn($ID, $PageSize, $From, $ValidTimeSpan);
        elseif (strtolower($_GET['type']) == 'watched')
            echo BangumiAPI::updateWatchedCacheAndReturn($ID, $PageSize, $From, $ValidTimeSpan);
    }
}