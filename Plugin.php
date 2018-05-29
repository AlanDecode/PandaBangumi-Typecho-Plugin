<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;?>

<?php
/**
 * 给博客添加精美的追番展示页吧！
 *  
 * 
 * @package BangumiList
 * @author 熊猫小A
 * @version 0.91
 * @link https://imalan.cn
 */

define('BangumiList_Plugin_VERSION', '0.91');

class BangumiList_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive')->header = array('BangumiList_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('BangumiList_Plugin', 'footer');
        //Helper::addRoute("route_BangumiList","/BangumiList","BangumiList_Action",'action');
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
        //Helper::removeRoute("route_BangumiList");
    }

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        $email = new Typecho_Widget_Helper_Form_Element_Text('email', NULL, '', _t('账号邮箱'), _t('填写 Bangumi 账号邮箱'));
        $form->addInput($email);
        $password = new Typecho_Widget_Helper_Form_Element_Text('password', NULL, '', _t('账号密码'), _t('填写 Bangumi 账号密码'));
        $form->addInput($password);
        $calposition = new Typecho_Widget_Helper_Form_Element_Text('calposition', NULL, 'bottom', _t('追番日历选项'), _t('番剧日历位置，top 表示显示在收藏列表上方，bottom 表示显示在其下方。默认在下方。'));
        $form->addInput($calposition);

        echo '<p>账号密码仅用于拉去数据，我不会收集你的信息。<br />可以选择是否开启追番列表展示或者追番日历展示。</p>';
        $bgmst= new Typecho_Widget_Helper_Form_Element_Checkbox('bgmst',  array(
            'collection' => _t('是否展示追番列表：勾选则展示不勾选则不加载<br>'),
            'calendar'=>_t('配置是否展示追番日历：勾选则展示不勾选则不展示<br>'),
            'jq'=>_t('配置是否引入 JQuery：勾选则引入不勾选则不引入<br>')
        ),
        array('collection','calendar','jq'), _t('基本设置'));
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
        echo '<link rel="stylesheet" href="/usr/plugins/BangumiList/css/bgmlist.css?v='.BangumiList_Plugin_VERSION.'" />';
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
        echo '<script type="text/javascript" src="/usr/plugins/BangumiList/js/bgmlist.js?v='.BangumiList_Plugin_VERSION.'"></script>';
        if (!empty(Helper::options()->plugin('BangumiList')->bgmst) && in_array('jq', Helper::options()->plugin('BangumiList')->bgmst))
        {
            echo '<script type="text/javascript" src="/usr/plugins/BangumiList/js/jquery.min.js"></script>';
        }

    }
}

?>
