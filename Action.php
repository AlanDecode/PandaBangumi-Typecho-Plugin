<?php 
/**
 * Action.php
 * 
 * API 获取、更新数据，处理前端 AJAX 请求
 * 
 * @author 熊猫小A
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class BangumiAPI{

    /**
     * 使用 curl 代替 file_get_contents()
     * 
     * @access public
     */
    static public function curlFileGetContents($_url){
        $myCurl = curl_init($_url);
        //不验证证书
        curl_setopt($myCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($myCurl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($myCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($myCurl,  CURLOPT_HEADER, false);
        //获取
        $content = curl_exec($myCurl);
        //关闭
        curl_close($myCurl);
        return $content;
    }

    
    /**
     * 收藏（在看） API，调用方式：$collectApiUrl/user/【ID】/collection?cat=playing&
     * 
     * @access private
     * 
     */
    static private $collectApiUrl= 'https://api.bgm.tv';
    
    /**
     * 日历 API
     * 
     * @access private
     * 
     */
    static private $calendarApiUrl='https://api.bgm.tv/calendar'; 
    
    /**
     * 获取在看数据并格式化返回
     * 
     * @return mixed
     */
    static private function __getCollectionRawData($ID){
        $apiUrl=self::$collectApiUrl.'/user/'.$ID.'/collection?cat=playing';
        $data=self::curlFileGetContents($apiUrl);
        if($data=='null') return '-1'; // 此用户尚未标记再看番剧

        $data=json_decode($data, false);

        $weekdays=array('Mon.','Tue.','Wed.','Thu','Fri','Sat','Sun');
        $collections=array();
        foreach ($data as $item) {
            $collect=array(
                'name'=>$item->subject->name,
                'name_cn'=>$item->subject->name_cn,
                'url'=>$item->subject->url,
                'status'=>$item->ep_status,
                'count'=>$item->subject->eps_count,
                'air_date'=>$item->subject->air_date,
                'air_weekday'=>$weekdays[$item->subject->air_weekday-1],
                'img'=>str_replace('http://','https://',$item->subject->images->large),
                'id'=>$item->subject->id
            );
            array_push($collections,$collect);
        }
        return $collections;
    }

    /**
     * 获取日历并格式化返回
     * 
     * @return array
     */
    static public function getCalendar(){
        // 暂去掉追番日历功能
        $data=json_decode(self::curlFileGetContents(self::$calendarApiUrl), false);
    }

    /**
     * 检查缓存是否过期
     * 
     * @access  private
     * @param   string    $FilePath           缓存路径
     * @param   int       $ValidTimeSpan      有效时间，Unix 时间戳，s
     * @return  int       0: 未过期; 1:已过期; -1：无缓存或缓存无效
     */
    private static function __isCacheExpired($FilePath,$ValidTimeSpan){
        $file=fopen($FilePath,"r");
        if(!$file) return -1;
        $content=json_decode(fread($file,filesize($FilePath)), false);
        fclose($file);
        if(!$content->time || $content->time<1) return -1;
        if(time()-$content->time > $ValidTimeSpan) return 1;
        return 0; 
    }

    /**
     * 读取与更新本地缓存，格式化返回数据
     * 
     * @access public
     * @return string
     */
    static public function updateCacheAndReturn($ID,$PageSize,$From,$ValidTimeSpan){
        $expired=self::__isCacheExpired(__DIR__.'/json/bangumi.json',$ValidTimeSpan);        
        if($expired != 0){
            $data=self::__getCollectionRawData($ID);
            $file=fopen(__DIR__.'/json/bangumi.json',"w");
            fwrite($file,json_encode(array('time'=>time(),'data'=>$data)));
            fclose($file);
            return self::updateCacheAndReturn($ID,$PageSize,$From,$ValidTimeSpan);
        }else{
            $data=json_decode(file_get_contents(__DIR__.'/json/bangumi.json'), false)->data;
            $total=count($data);
            if($From<0 || $From>$total-1) echo json_encode(array());
            else{
                $end=min($From+$PageSize,$total);
                $out=array();
                for ($index=$From; $index<$end; $index++) {
                    array_push($out,$data[$index]);
                }
                return json_encode($out);
            }
        }
    }
}

class PandaBangumi_Action extends Widget_Abstract_Contents implements Widget_Interface_Do 
{
    /**
     * 返回请求的 HTML
     * @access public
     */
    public function action(){
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }
        header("Content-type: application/json");
        $options = Helper::options();
		$ID = $options->plugin('PandaBangumi')->ID;
        $PageSize = $options->plugin('PandaBangumi')->PageSize;
        $ValidTimeSpan=$options->plugin('PandaBangumi')->ValidTimeSpan;
        $From=$_GET['from'];
        if($PageSize==-1) $PageSize=1000000;
        echo BangumiAPI::updateCacheAndReturn($ID,$PageSize,$From,$ValidTimeSpan);
    }
}