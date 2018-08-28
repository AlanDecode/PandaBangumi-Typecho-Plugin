
<?php
class BangumiAPI
{
    private static $bangumiAPI = null;
    
    /** 静态成员 **/
    //应用程序名
    private static $appName = "BGMYetu";
    //api链接
    private static $apiUrl = "https://api.bgm.tv";
    /** 成员 **/
    //用户名（邮箱）
    public $userName = "";
    //密码
    public $passWord = "";
    //用户id
    private $userID = "";
    //auth
    private $auth = "";
    //auth urlencoding
    private $authEncode = "";
    //登陆api
    private $loginApi = "";
    //收藏api
    private $collectionApi = "";
    //日历api
    private static $calendarApi="https://api.bgm.tv/calendar";

    /**方法**/
    public static function GetInstance()
    {
        if (BangumiAPI::$bangumiAPI == null) {
            BangumiAPI::$bangumiAPI = new BangumiAPI();
        }
        return BangumiAPI::$bangumiAPI;
    }
    //构造方法
    private function __construct()
    {
        //echo "构造方法";
    }
    //初始化对象
    public function init($_userName, $_passWord)
    {
        $this->userName = $_userName;
        $this->passWord = $_passWord;
        
        if ($_userName == null || $_passWord == null) 
        {
            //程序返回
            echo "初始化参数错误！";
            return;
        }

        //登录 API 初始化
        $this->loginApi = BangumiAPI::$apiUrl . "/auth?source=" . BangumiAPI::$appName;
        //登录
        if ($this->userID == "" || $this->authEncode == "")
        {
            //登陆post字符串
            $postData = array('username' => $this->userName, 'password' => $this->passWord);
            //获取登陆返回json
            $userContent = BangumiAPI::curl_post_contents($this->loginApi, $postData);
            //json to object
            $userData = json_decode($userContent);
            //存在error属性
            if (property_exists($userData, "error")) {
                //输出错误信息
                echo "登陆错误：" . $userData->error;
                //程序返回
                return;
            }

            //初始化
            $this->userID = $userData->id;
            $this->auth = $userData->auth;
            $this->authEncode = $userData->auth_encode;
        }
        $this->collectionApi = BangumiAPI::$apiUrl . "/user/" . $this->userID . "/collection?cat=playing&";
    }

    //获得收藏数据
    public function GetCollection()
    {
        
        if ($this->userID == "" || $this->collectionApi == "") {
            return null;
        }
        return BangumiAPI::curl_get_contents($this->collectionApi);
    }

    //收藏数据格式化返回
    public function ParseCollection()
    {
        $content = $this->GetCollection();
        if ($content == null || $content == "") {
            echo "获取失败";
            return;
        }
        //返回不是json
        if (strpos($content, "[{") != false && $content != "") {
            echo "用户不存在！";
            return;
        }
        $collData = json_decode($content);
        if (sizeof($collData) == 0 || $collData == null) {
            echo "还没有记录哦~";
            return;
        }


        $collections=array();
        foreach ($collData as $value)
        {
            if(!$value->subject->eps_count || $value->subject->eps_count=='' || $value->subject->eps_count==0)
            {
                $eps_count='Unknown';
            }
            else
            {
                $eps_count=$value->subject->eps_count;
            }
            
            $item=array(
                "name"=>$value->subject->name,
                "id"=>$value->subject->id,
                "name_cn"=>$value->subject->name_cn,
                "ep_count"=>$eps_count,
                "ep_status"=>$value->ep_status,
                "air_date"=>$value->subject->air_date,
                "air_weekday"=>$value->subject->air_weekday,
                "url"=>$value->subject->url,
                "img_url"=>str_replace("http://", "https://", $value->subject->images->common),
                "summary"=>json_decode(BangumiAPI::curl_get_contents('https://api.bgm.tv/subject/'.(string)$value->subject->id))->summary
            );

            array_push($collections,$item);
        }

        return $collections;
    }

    //日历数据格式化返回
    public function ParseCalendar()
    {
        $content = json_decode(BangumiAPI::curl_get_contents('https://api.bgm.tv/calendar'));
        
        $calendar=array();
        
        foreach ($content as $value)
        {
            $items=array();
            foreach($value->items as $item)
            {
                $e=array
                (
                    "id"=>$item->id,
                    "name"=>$item->name,
                    "name_cn"=>$item->name_cn
                );
                array_push($items,$e);
            }
            $oneday=array
            (   
                "day_en"=>$value->weekday->en,
                "day_cn"=>$value->weekday->cn,
                "items"=>$items
            );
            array_push($calendar,$oneday);
        }

        return $calendar;
    }


    //GET 方法
    public static function curl_get_contents($_url)
    {
        $myCurl = curl_init($_url);
        //不验证证书
        curl_setopt($myCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($myCurl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($myCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($myCurl, CURLOPT_HEADER, false);
        //获取
        $content = curl_exec($myCurl);
        //关闭
        curl_close($myCurl);
        return $content;
    }

    //POST 方法
    public static function curl_post_contents($_url, $_postdata)
    {
        $myCurl = curl_init($_url);
        //不验证证书
        curl_setopt($myCurl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($myCurl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($myCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($myCurl, CURLOPT_POST, 1);
        curl_setopt($myCurl, CURLOPT_POSTFIELDS, $_postdata);
        $output = curl_exec($myCurl);
        curl_close($myCurl);
        return $output;
    }
}

/*一些功能函数*/

function UpdateData($email, $password, $filePath)
{
    $bangumi = BangumiAPI::GetInstance();
    $bangumi->init($email, $password);
    $collection = $bangumi->ParseCollection();
    $calendar = $bangumi->ParseCalendar();


    $data=array
    (
        "time"=>time(),
        "collection"=>$collection,
        "cal"=>$calendar
    );

    $file=fopen($filePath,"w");
    fwrite($file,json_encode($data));
    fclose($file);
}


function GetData($email, $password,$filePath)
{
    $fp = fopen($filePath, 'r');


    if($fp)//数据文件存在
    {
        $content=fread($fp,filesize($filePath));
        fclose($fp);
        $data=json_decode($content);

        if(time()-$data->time > 60*60*12)//缓存文件过期
        {
            UpdateData($email, $password, $filePath);
            return GetData($email, $password, $filePath);
        }
        else
        {
            return $data;
        }
    }
    else
    {
        UpdateData($email, $password, $filePath);
        return GetData($email, $password, $filePath);
    }
}


function PrintCalendar($email, $password,$filePath)
{
    
    $data = GetData($email, $password,$filePath);
    $calendar=$data->cal;
    $collection=$data->collection;

    $id_list=array();
    foreach($collection as $value)
    {
        array_push($id_list,$value->id);
    }

    $html='<div id="PandaBangumi-calendar"><h2 style="margin:0">番剧日历</h2><hr>';
    foreach ($calendar as $value) {
        $html.='<h4>'.$value->day_en.' . '.$value->day_cn.'</h4><p>/ ';
        foreach ($value->items as $bgmi) {
            $id=$bgmi->id;
            if($bgmi->name_cn !== '' && $bgmi->name_cn !== null) {$name=$bgmi->name_cn;}
            else{$name=$bgmi->name;}
            if(in_array($id,$id_list)){
                $html.='<strong>'.$name.'</strong> / ';
            }else{
                $html.=$name.' / ';
            }
            
        }
        $html.='</p><hr>';
    }
    $html.='</div>';
    return $html;
}


/**
 * 返回请求的番剧列表
 * @param $email        用户邮箱
 * @param $password     用户密码
 * @param $filePath     缓存文件路径
 * @param $page         第 $page 页
 * @param $perpage      每页数量
 * @return string
 */

function PrintCollection($email, $password, $filePath, $page, $perpage, $with_holder)
{
    if($page<1) $page=1;

    $data = GetData($email, $password,$filePath);
    $collection=$data->collection;

    if($perpage==-1) $perpage=count($collection);

    $totalpage=ceil(count($collection)/$perpage);
    if($page>$totalpage) $page=$totalpage;

    $result='';
    if($with_holder) $result.='<div id="PandaBangumi-collections-holder">';

    $result.='
    <div id="PandaBangumi-collections" pagenum="'.$page.'" totalpage="'.$totalpage.'"><h2 style="margin:0">追番清单</h2>
    <div id="PandaBangumi-Pager" width="100%">第 '.$page.' 页 | 共 '.$totalpage.' 页 | 
    <a style="cursor:pointer" onclick="PandaBangumi_turnPage(-1)">上一页</a> | <a style="cursor:pointer" onclick="PandaBangumi_turnPage(1)">下一页</a> | <a onclick="PandaBangumi_refresh()">刷新</a>    
    </div>
    ';

    for ($i=($page-1)*$perpage; $i < min($page*$perpage,count($collection)); $i++) 
    { 
        $item=$collection[$i];
        $name_cn=$item->name_cn;
        $name=$item->name;
        $air_date=$item->air_date;
        $air_weekday=$item->air_weekday;
        $ep_count=$item->ep_count;
        $ep_status=$item->ep_status;
        $url=$item->url;
        $img_url=$item->img_url;
        $summary=$item->summary;

		if(!$name_cn || $name_cn=='') $name_cn=$name;
		
        $html='
        <div class="PandaBangumi-item">
            <img title="点击显示简介" class="PandaBangumi-thumb" src="'.$img_url.'"/>
            <div class="PandaBangumi-content">
				<a href="'.$url.'" class="PandaBangumi-content-title" target="_blank">'.$name_cn.'<span style="font-size: 15px;margin-left: 0.3em"><i class="fa fa-external-link"></i></span></a>
                <p class="PandaBangumi-content-title-jp">'.$name.'</p>
                <p  title="点击显示简介" class="PandaBangumi-content-des">首播：'.$air_date.'</p>
                <p  title="点击显示简介" class="PandaBangumi-content-des">播出：周'.$air_weekday.'</p>
                <p  title="点击显示简介" class="PandaBangumi-content-des">进度：'.$ep_status.' / '.$ep_count.'</p>
            </div>
            <div class="PandaBangumi-status-bar" data1="'.$ep_status.'" data2="'.$ep_count.'"></div>
            <div class="PandaBangumi-summary" title="点击隐藏简介"><p>'.$summary.'</p></div>
        </div>';

        $result.=$html;
    }

    $result.='</div>';
    if($with_holder) $result.='</div>';

    return $result;
}

class PandaBangumi_Action extends Widget_Abstract_Contents implements Widget_Interface_Do 
{
    
    /**
     * 返回请求的 HTML
     * @access public
     */
    
    public function action()
    {
        if($_GET['getboard']==1)
        {
            $html='';
            $id=$_GET['id'];
            $res=json_decode(BangumiAPI::curl_get_contents('https://api.bgm.tv/subject/'.(string)$id));
            
            $img_url=str_replace("http://", "https://",$res->images->large);
            $url=$res->url;
            $name=$res->name;
            $name_cn=$res->name_cn;
            $summary=$res->summary;
            $rating=$res->rating->score;
            $rank=$res->rank;
            $air_date=$res->air_date;
            $air_weekday=$res->air_weekday;
            $eps_count=$res->eps_count; 
            
            if(!$eps_count || $eps_count=='' || $eps_count==0) $eps_count='Unknown';
            
            if(!$name_cn || $name_cn=='') $name_cn=$name;

            $html ='<div title="点击显示简介" class="bgm-board-thumb" style="background-image: url('.$img_url.')"></div>
                    <div class="bgm-board-content">
                        <a class="bgm-board-title" href="'.$url.'" target="_blank">'.$name_cn.'<span style="margin-left: 0.3em"><i class="fa fa-external-link"></i></span></a>
                        <p class="bgm-board-title-jp">'.$name.'</p>
                        <div class="bgm-board-info" title="点击显示简介">
                            <p>评分：'.$rating.'</p>
                            <p>排名：'.$rank.'</p>
                            <p>首播：'.$air_date.'</p>
                            <p>播放：周'.$air_weekday.'</p>
                            <p>集数：'.$eps_count.'</p>
                        </div>
                    </div>
                    <div class="bgm-board-summary" title="点击隐藏简介"><p>'.$summary.'</p></div>';
            echo $html;  
            return;      
        }
        

        $html='';
        $options = Helper::options();
		$email = $options->plugin('PandaBangumi')->email;
        $password = $options->plugin('PandaBangumi')->password;
        $filePath=__DIR__.'/json/bangumi.json';
        $perpage=$options->plugin('PandaBangumi')->perpage;
        $requestedpage=$_GET['page'];


        /**
        * 如果强制刷新缓存
        */
        if($_GET['forcerefresh']=='true')
        {
            UpdateData($email, $password, $filePath);
        }

        if($_GET['cleancache']==1)
        {
            if(unlink($filePath))
            {
                echo '清除成功，去刷新页面看看吧～';
                return;
            }
            else
            {
                echo '没有清除掉……是不是权限不对？还是本来就没有缓存？';
            }
            return;
        }

        if($_GET['onlycollection']==0)
        {
            if($options->plugin('PandaBangumi')->calendar=='top')
            {
                $html.=PrintCalendar($email, $password,$filePath);
            }

            //显示番剧
            if($options->plugin('PandaBangumi')->perpage!=0)
            {
                $html.=PrintCollection($email, $password, $filePath, $requestedpage, $perpage, 1);
            }

            if($options->plugin('PandaBangumi')->calendar=='bottom')
            {
                $html.=PrintCalendar($email, $password,$filePath);
            }
        }
        else
        {
            $html.=PrintCollection($emial, $password, $filePath, $requestedpage, $perpage, 0);
        }

        echo $html;
    }
}

?>


