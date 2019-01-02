<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;?>

<?php
/**
 * 给博客添加精美的番剧展示页吧！
 *  
 * 
 * @package PandaBangumi
 * @author 熊猫小A
 * @version 2.0
 * @link https://www.imalan.cn
 */

define('PandaBangumi_Plugin_VERSION', '2.0');

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
        echo '作者：<a href="https://www.imalan.cn">熊猫小A</a>，插件介绍页：<a href="https://blog.imalan.cn/archives/128/">熊猫追番 (PandaBangumi) for Typecho</a><br>';
        echo '<br><strong>使用方法：</strong><br>';
        echo '<br>在文章要插入的地方写: ';
        echo htmlspecialchars('<div class="bgm-collection" id="bgm-collection"></div>');
        
        $ID = new Typecho_Widget_Helper_Form_Element_Text('ID', NULL, '', _t('用户 ID'), _t('填写你的 Bangumi 主页链接 user 后面那一串数字'));
        $form->addInput($ID);

        $PageSize = new Typecho_Widget_Helper_Form_Element_Text('PageSize', NULL, '6', _t('每页数量'), _t('填写番剧列表每页数量，填写 -1 则在一页内全部显示，默认为 6.'));
        $form->addInput($PageSize);

        $ValidTimeSpan = new Typecho_Widget_Helper_Form_Element_Text('ValidTimeSpan', NULL, '86400', _t('缓存过期时间'), _t('设置缓存过期时间，单位为秒，默认 24 小时。'));
        $form->addInput($ValidTimeSpan);

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
        echo '<link rel="stylesheet" href="';
        Helper::options()->index('/usr/plugins/PandaBangumi/css/PandaBangumi.20.css');
        echo '?v='.PandaBangumi_Plugin_VERSION.'" />';
        if (!empty(Helper::options()->plugin('PandaBangumi')->bgmst) && in_array('jq', Helper::options()->plugin('PandaBangumi')->bgmst))
        {
            echo '<script src="';
            Helper::options()->index('/usr/plugins/PandaBangumi/js/jq.min.js');
            echo '"></script>';
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
        echo '<script type="text/javascript" src="';
        Helper::options()->index('/usr/plugins/PandaBangumi/js/PandaBangumi.20.js');
        echo '?v='.PandaBangumi_Plugin_VERSION.'"></script>';
    }
}

?>
