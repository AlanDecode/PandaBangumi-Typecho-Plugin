<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 给博客添加精美的番剧展示页吧！
 *  
 * 
 * @package PandaBangumi
 * @author 熊猫小A
 * @version 2.3
 * @link https://www.imalan.cn
 */

define('PandaBangumi_Plugin_VERSION', '2.3');

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
        // 检查是否存在对应扩展
        if (!extension_loaded('openssl')) {
            throw new Typecho_Plugin_Exception('启用失败，PHP 需启用 OpenSSL 扩展。');
        }
        if (!extension_loaded('curl')) {
            throw new Typecho_Plugin_Exception('启用失败，PHP 需启用 CURL 扩展。');
        }

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
        echo '<br><strong>使用方法，在文章要插入的地方写：</strong><br>';
        echo htmlspecialchars('所有在看：<div data-type="watching" class="bgm-collection"></div>'); echo '<br>';
        echo htmlspecialchars('已看动画：<div data-type="watched" data-cate="anime" class="bgm-collection"></div>'); echo '<br>';
        echo htmlspecialchars('已看三次元：<div data-type="watched" data-cate="real" class="bgm-collection"></div>'); echo '<br>';

        $ID = new Typecho_Widget_Helper_Form_Element_Text('ID', NULL, '', _t('用户 ID'), _t('填写你的 Bangumi 主页链接 user 后面那一串数字'));
        $form->addInput($ID);

        $PageSize = new Typecho_Widget_Helper_Form_Element_Text('PageSize', NULL, '6', _t('每页数量'), _t('填写番剧列表每页数量，填写 -1 则在一页内全部显示，默认为 6.'));
        $form->addInput($PageSize);

        $ValidTimeSpan = new Typecho_Widget_Helper_Form_Element_Text('ValidTimeSpan', NULL, '86400', _t('缓存过期时间'), _t('设置缓存过期时间，单位为秒，默认 24 小时。'));
        $form->addInput($ValidTimeSpan);

        $bgmst= new Typecho_Widget_Helper_Form_Element_Checkbox(
            'bgmst',  
            array('jq'=>_t('配置是否引入 JQuery：勾选则引入不勾选则不引入')),
            array('jq'), _t('基本设置'));
        $form->addInput($bgmst);

        $ParseMethod = new Typecho_Widget_Helper_Form_Element_Radio('ParseMethod', array(
            'api' => 'API',
            'webpage' => '网页'), 'api', 
            '已看列表解析方式', 'API 解析相对稳定，但是有最多获取最近 25 部的限制。网页解析速度可能较慢，但能获取更多记录。不影响在看列表。');
        $form->addInput($ParseMethod);

        $Limit = new Typecho_Widget_Helper_Form_Element_Text('Limit', NULL, '20', _t('已看列表数量限制'), _t('设置获取数量限制，不建议设置得太大，有被 Bangumi 拉黑的风险。<b>仅当通过网页解析时有效</b>。不影响在看列表。'));
        $form->addInput($Limit);
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
        Helper::options()->pluginUrl('/PandaBangumi/css/PandaBangumi.24.css');
        echo '?v='.PandaBangumi_Plugin_VERSION.'" />';
        if (!empty(Helper::options()->plugin('PandaBangumi')->bgmst) && in_array('jq', Helper::options()->plugin('PandaBangumi')->bgmst))
        {
            echo '<script src="';
            Helper::options()->pluginUrl('/PandaBangumi/js/jq.min.js');
            echo '"></script>';
        }
        echo '<script>var bgmBase="';
        Helper::options()->index('/PandaBangumi');
        echo '"</script>';
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
        Helper::options()->pluginUrl('/PandaBangumi/js/PandaBangumi.24.js');
        echo '?v='.PandaBangumi_Plugin_VERSION.'"></script>';
    }
}
