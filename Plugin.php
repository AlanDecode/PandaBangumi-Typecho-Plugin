<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;?>

<?php
/**
 * 给博客添加精美的番剧展示页吧！
 *  
 * 
 * @package PandaBangumi
 * @author 熊猫小A
 * @version 0.95
 * @link https://imalan.cn
 */

define('PandaBangumi_Plugin_VERSION', '0.95');

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
            array('jq'=>_t('配置是否引入 JQuery：勾选则引入不勾选则不引入<br>')),
            array('jq'), _t('基本设置'));
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
        if (!empty(Helper::options()->plugin('PandaBangumi')->bgmst) && in_array('jq', Helper::options()->plugin('PandaBangumi')->bgmst))
        {
            echo '<script type="text/javascript" src="/usr/plugins/PandaBangumi/js/jquery.min.js"></script>';
        }

    }
}

?>
