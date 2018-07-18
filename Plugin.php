<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;?>

<?php
/**
 * 给博客添加精美的番剧展示页吧！
 *  
 * 
 * @package PandaBangumi
 * @author 熊猫小A
 * @version 1.1
 * @link https://imalan.cn
 */

define('PandaBangumi_Plugin_VERSION', '1.1');

class PandaBangumi_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->header = array('PandaBangumi_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('PandaBangumi_Plugin', 'footer');
        Helper::addRoute("route_PandaBangumi","/PandaBangumi","PandaBangumi_Action",'action');
        //Helper::addRoute("route_PandaBangumi_Board","/PandaBangumi_Board","PandaBangumi_Action",'getBoard');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeRoute("route_PandaBangumi");
       // Helper::removeRoute("route_PandaBangumi_Board");
    }

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        echo '<p>账号密码仅用于拉取数据，我不会收集你的信息。<br />可以选择是否开启追番列表展示或者追番日历展示。</p>';
        echo '作者：<a href="http://imalan.cn">熊猫小A</a>，插件介绍页：<a href="https://imalan.cn/archives/128/">熊猫追番 (PandaBangumi) for Typecho</a><br>';
        echo '<br><strong>使用方法：</strong><br>';
        echo '<strong>展示追番列表与追番日历</strong><br>在文章要插入的地方写: ';
        echo htmlspecialchars('<div class="PandaBangumi-Content" id="PandaBangumi-Content"></div>');
        echo '<br><strong>展示单部番剧</strong><br>在要插入的地方写: ';
        echo htmlspecialchars('<div class="PandaBangumi_Board" data="【番组ID】"></div>');
        echo '，将【番组ID】替换为要展示的番剧的ID，这个ID可以从 Bangumi 番剧主页地址栏获取。<br><br>';
        echo '点这里可以手动删除缓存文件，下次加载会从 Bangumi 拉取数据<br><a href="/index.php/PandaBangumi?cleancache=1" target="_blank"><button class="btn" style="outline: 0">' . _t('手动清除缓存'). '</button></a>';

        $email = new Typecho_Widget_Helper_Form_Element_Text('email', NULL, '', _t('账号邮箱'), _t('填写 Bangumi 账号邮箱'));
        $form->addInput($email);

        $password = new Typecho_Widget_Helper_Form_Element_Text('password', NULL, '', _t('账号密码'), _t('填写 Bangumi 账号密码'));
        $form->addInput($password);

        $perpage = new Typecho_Widget_Helper_Form_Element_Text('perpage', NULL, '6', _t('追番列表每页数量'), _t('设置每页显示的番剧数量，填写 0 则不显示，填写 -1 则在一页内全部显示'));
        $form->addInput($perpage);
        
        $calendar = new Typecho_Widget_Helper_Form_Element_Radio(
            'calendar',
            array('no' => _t('不显示'),'top' => _t('显示在列表上方'),'bottom' => _t('显示在列表下方')),
            'bottom',
            _t('番剧日历选项'),
            _t('配置是否显示番剧日历与日历位置')
        );
        $form->addInput($calendar);

        $bgmst= new Typecho_Widget_Helper_Form_Element_Checkbox(
            'bgmst',  
            array('jq'=>_t('配置是否引入 JQuery：勾选则引入不勾选则不引入<br>'),
                    'darkmode'=>_t('开启暗色模式：勾选则开启')),
            array('jq','darkmode'), _t('基本设置'));
        $form->addInput($bgmst);
    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 输出头部css
     * 
     * @access public
     * @return void
     */
    public static function header()
    {
        echo '<link rel="stylesheet" href="/usr/plugins/PandaBangumi/css/PandaBangumi.css?v='.PandaBangumi_Plugin_VERSION.'" />';
        if (!empty(Helper::options()->plugin('PandaBangumi')->bgmst) && in_array('jq', Helper::options()->plugin('PandaBangumi')->bgmst))
        {
            echo '<script src="https://cdn.bootcss.com/jquery/2.1.4/jquery.min.js"></script>';
        }
        if(PandaBangumi_Plugin::isMobile())
        {
            echo '<script>var isMobile=true</script>';
        }
        else
        {
            echo '<script>var isMobile=false</script>';
        }

        if (!empty(Helper::options()->plugin('PandaBangumi')->bgmst) && in_array('darkmode', Helper::options()->plugin('PandaBangumi')->bgmst))
        {
            echo '
                <style>
                .PandaBangumi_Board{background:rgba(0,0,0,.8)}
                .PandaBangumi_Board:hover{background:rgba(40,40,40,.8)}
                .PandaBangumi_Board_Img_Box{box-shadow:none}
                .PandaBangumi_Board_Content h2 a{color:#fff!important}
                .PandaBangumi_Board_Content h4{color:#f0f0f0!important}
                .PandaBangumi_Board_Content p{color:#fff!important}
                .PandaBangumi-item{background:rgba(0,0,0,.8)}
                .PandaBangumi-item:hover{background:rgba(40,40,40,.8)}
                .PandaBangumi-content .PandaBangumi-content-title{color:#fff!important}
                .PandaBangumi-content .PandaBangumi-content-title:hover{color:#f0f0f0!important}
                .PandaBangumi-content .PandaBangumi-content-title-jp{color:#f0f0f0!important}
                .PandaBangumi-content-des{color:#fff!important}
                .PandaBangumi-status-bar{background-color:pink}
                </style>
                ';
        }   
    }  

    /**
     * 在底部输出所需 JS
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function footer()
    {
        echo '<script type="text/javascript" src="/usr/plugins/PandaBangumi/js/PandaBangumi.js?v='.PandaBangumi_Plugin_VERSION.'"></script>';
    }

    /**
     * 移动设备识别
     *
     * @return boolean
     */
    public static function isMobile(){
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $mobile_browser = Array(
            "mqqbrowser", // 手机QQ浏览器
            "opera mobi", // 手机opera
            "juc","iuc", 'ucbrowser', // uc浏览器
            "fennec","ios","applewebKit/420","applewebkit/525","applewebkit/532","ipad","iphone","ipaq","ipod",
            "iemobile", "windows ce", // windows phone
            "240x320","480x640","acer","android","anywhereyougo.com","asus","audio","blackberry",
            "blazer","coolpad" ,"dopod", "etouch", "hitachi","htc","huawei", "jbrowser", "lenovo",
            "lg","lg-","lge-","lge", "mobi","moto","nokia","phone","samsung","sony",
            "symbian","tablet","tianyu","wap","xda","xde","zte"
        );
        $is_mobile = false;
        foreach ($mobile_browser as $device) {
            if (stristr($user_agent, $device)) {
                $is_mobile = true;
                break;
            }
        }
        return $is_mobile;
    }
}

?>
