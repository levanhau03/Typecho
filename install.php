<?php if (!file_exists(dirname(__FILE__) . '/config.inc.php')): ?>
<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/** 定义根目录 */
define('__TYPECHO_ROOT_DIR__', dirname(__FILE__));

/** 定义插件目录(相对路径) */
define('__TYPECHO_PLUGIN_DIR__', '/usr/plugins');

/** 定义模板目录(相对路径) */
define('__TYPECHO_THEME_DIR__', '/usr/themes');

/** 后台路径(相对路径) */
define('__TYPECHO_ADMIN_DIR__', '/admin/');

/** 设置包含路径 */
@set_include_path(get_include_path() . PATH_SEPARATOR .
__TYPECHO_ROOT_DIR__ . '/var' . PATH_SEPARATOR .
__TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__);

/** 载入API支持 */
require_once 'Typecho/Common.php';

/** 载入Response支持 */
require_once 'Typecho/Response.php';

/** 载入配置支持 */
require_once 'Typecho/Config.php';

/** 载入异常支持 */
require_once 'Typecho/Exception.php';

/** 载入插件支持 */
require_once 'Typecho/Plugin.php';

/** 载入国际化支持 */
require_once 'Typecho/I18n.php';

/** 载入数据库支持 */
require_once 'Typecho/Db.php';

/** 载入路由器支持 */
require_once 'Typecho/Router.php';

/** 程序初始化 */
Typecho_Common::init();

else:

    require_once dirname(__FILE__) . '/config.inc.php';

    //判断是否已经安装
    $db = Typecho_Db::get();
    try {
        $installed = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'installed'));
        if (empty($installed) || $installed['value'] == 1) {
            Typecho_Response::setStatus(404);
            exit;
        }
    } catch (Exception $e) {
        // do nothing
    }

endif;

// 挡掉可能的跨站请求
if (!empty($_GET) || !empty($_POST)) {
    if (empty($_SERVER['HTTP_REFERER'])) {
        exit;
    }

    $parts = parse_url($_SERVER['HTTP_REFERER']);
	if (!empty($parts['port'])) {
        $parts['host'] = "{$parts['host']}:{$parts['port']}";
    }

    if (empty($parts['host']) || $_SERVER['HTTP_HOST'] != $parts['host']) {
        exit;
    }
}

/**
 * 获取传递参数
 *
 * @param string $name 参数名称
 * @param string $default 默认值
 * @return string
 */
function _r($name, $default = NULL) {
    return isset($_REQUEST[$name]) ?
        (is_array($_REQUEST[$name]) ? $default : $_REQUEST[$name]) : $default;
}

/**
 * 获取多个传递参数
 *
 * @return array
 */
function _rFrom() {
    $result = array();
    $params = func_get_args();

    foreach ($params as $param) {
        $result[$param] = isset($_REQUEST[$param]) ?
            (is_array($_REQUEST[$param]) ? NULL : $_REQUEST[$param]) : NULL;
    }

    return $result;
}

/**
 * 输出传递参数
 *
 * @param string $name 参数名称
 * @param string $default 默认值
 * @return string
 */
function _v($name, $default = '') {
    echo _r($name, $default);
}

/**
 * 判断是否兼容某个环境(perform)
 *
 * @param string $adapter 适配器
 * @return boolean
 */
function _p($adapter) {
    switch ($adapter) {
        case 'Mysql':
            return Typecho_Db_Adapter_Mysql::isAvailable();
        case 'Mysqli':
            return Typecho_Db_Adapter_Mysqli::isAvailable();
        case 'Pdo_Mysql':
            return Typecho_Db_Adapter_Pdo_Mysql::isAvailable();
        case 'SQLite':
            return Typecho_Db_Adapter_SQLite::isAvailable();
        case 'Pdo_SQLite':
            return Typecho_Db_Adapter_Pdo_SQLite::isAvailable();
        case 'Pgsql':
            return Typecho_Db_Adapter_Pgsql::isAvailable();
        case 'Pdo_Pgsql':
            return Typecho_Db_Adapter_Pdo_Pgsql::isAvailable();
        default:
            return false;
    }
}

/**
 * 获取url地址
 *
 * @return string
 */
function _u() {
    $url = Typecho_Request::getUrlPrefix() . $_SERVER['REQUEST_URI'];
    if (isset($_SERVER['QUERY_STRING'])) {
        $url = str_replace('?' . $_SERVER['QUERY_STRING'], '', $url);
    }

    return dirname($url);
}

$options = new stdClass();
$options->generator = 'Typecho ' . Typecho_Common::VERSION;
list($soft, $currentVersion) = explode(' ', $options->generator);

$options->software = $soft;
$options->version = $currentVersion;

list($prefixVersion, $suffixVersion) = explode('/', $currentVersion);

/** 获取语言 */
$lang = _r('lang', Typecho_Cookie::get('__typecho_lang'));
$langs = Widget_Options_General::getLangs();

if (empty($lang) && count($langs) > 1) {
    foreach ($langs as $lang) {
        if ('zh_CN' != $lang) {
            break;
        }
    }
}

if (empty($lang)) {
    $lang = 'zh_CN';
}

if ('zh_CN' != $lang) {
    $dir = defined('__TYPECHO_LANG_DIR__') ? __TYPECHO_LANG_DIR__ : __TYPECHO_ROOT_DIR__ . '/usr/langs';
    Typecho_I18n::setLang($dir . '/' . $lang . '.mo');
}

Typecho_Cookie::set('__typecho_lang', $lang);

?><!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head lang="zh-CN">
    <meta charset="<?php _e('UTF-8'); ?>" />
	<title><?php _e('Trình cài đặt Typecho'); ?></title>
    <link rel="stylesheet" type="text/css" href="admin/css/normalize.css" />
    <link rel="stylesheet" type="text/css" href="admin/css/grid.css" />
    <link rel="stylesheet" type="text/css" href="admin/css/style.css" />
</head>
<body>
<div class="typecho-install-patch">
    <h1>Typecho</h1>
    <ol class="path">
        <li<?php if (!isset($_GET['finish']) && !isset($_GET['config'])) : ?> class="current"<?php endif; ?>><span>1</span><?php _e('Chào mừng'); ?></li>
        <li<?php if (isset($_GET['config'])) : ?> class="current"<?php endif; ?>><span>2</span><?php _e('Cấu hình ban đầu'); ?></li>
        <li<?php if (isset($_GET['start'])) : ?> class="current"<?php endif; ?>><span>3</span><?php _e('Bắt đầu cài đặt'); ?></li>
        <li<?php if (isset($_GET['finish'])) : ?> class="current"<?php endif; ?>><span>4</span><?php _e('Cài đặt thành công'); ?></li>
    </ol>
</div>
<div class="container">
    <div class="row">
        <div class="col-mb-12 col-tb-8 col-tb-offset-2">
            <div class="column-14 start-06 typecho-install">
            <?php if (isset($_GET['finish'])) : ?>
                <?php if (!isset($db)) : ?>
                <h1 class="typecho-install-title"><?php _e('Cài đặt thất bại!'); ?></h1>
                <div class="typecho-install-body">
                    <form method="post" action="?config" name="config">
                    <p class="message error"><?php _e('Bạn không tải lên tệp config.inc.php, vui lòng cài đặt lại nó!'); ?> <button class="btn primary" type="submit"><?php _e('Cài đặt lại &raquo;'); ?></button></p>
                    </form>
                </div>
                <?php elseif (!Typecho_Cookie::get('__typecho_config')): ?>
                    <h1 class="typecho-install-title"><?php _e('Chưa cài đặt!'); ?></h1>
                    <div class="typecho-install-body">
                        <form method="post" action="?config" name="config">
                            <p class="message error"><?php _e('Bạn không thực hiện các bước cài đặt, vui lòng cài đặt lại!'); ?> <button class="btn primary" type="submit"><?php _e('Cài đặt lại &raquo;'); ?></button></p>
                        </form>
                    </div>
                <?php else : ?>
                    <?php
                    $db->query($db->update('table.options')->rows(['value' => 1])->where('name = ?', 'installed'));
                    ?>
                <h1 class="typecho-install-title"><?php _e('Cài đặt thành công!'); ?></h1>
                <div class="typecho-install-body">
                    <div class="message success">
                    <?php if(isset($_GET['use_old']) ) : ?>
                    <?php _e('Bạn đã chọn sử dụng dữ liệu gốc, tên người dùng và mật khẩu của bạn giống với dữ liệu gốc'); ?>
                    <?php else : ?>
                        <?php if (isset($_REQUEST['user']) && isset($_REQUEST['password'])): ?>
                            <?php _e('Tài khoản của bạn là'); ?>: <strong class="mono"><?php echo htmlspecialchars(_r('user')); ?></strong><br>
                            <?php _e('Mật khẩu của bạn là'); ?>: <strong class="mono"><?php echo htmlspecialchars(_r('password')); ?></strong>
                        <?php endif;?>
                    <?php endif;?>
                    </div>

                    <div class="session">
                    <p><?php _e('Bạn có thể lưu hai liên kết sau vào mục yêu thích của mình'); ?>:</p>
                    <ul>
                    <?php
                        if (isset($_REQUEST['user']) && isset($_REQUEST['password'])) {
                            $loginUrl = _u() . '/index.php/action/login?name=' . urlencode(_r('user')) . '&password='
                            . urlencode(_r('password')) . '&referer=' . _u() . '/admin/index.php';
                            $loginUrl = Typecho_Widget::widget('Widget_Security')->getTokenUrl($loginUrl);
                        } else {
                            $loginUrl = _u() . '/admin/index.php';
                        }
                    ?>
                        <li><a href="<?php echo $loginUrl; ?>"><?php _e('Nhấp vào đây để truy cập bảng điều khiển của bạn'); ?></a></li>
                        <li><a href="<?php echo _u(); ?>/index.php"><?php _e('Bấm vào đây để xem blog của bạn'); ?></a></li>
                    </ul>
                    </div>

                    <p><?php _e('Hy vọng bạn có thể tận hưởng niềm vui của Typecho!'); ?></p>
                </div>
                <?php endif;?>
            <?php elseif (isset($_GET['start'])): ?>
                <?php if (!isset($db)) : ?>
                <h1 class="typecho-install-title"><?php _e('Cài đặt thất bại!'); ?></h1>
                <div class="typecho-install-body">
                    <form method="post" action="?config" name="config">
                    <p class="message error"><?php _e('Bạn không tải lên tệp config.inc.php, vui lòng cài đặt lại nó!'); ?> <button class="btn primary" type="submit"><?php _e('Cài đặt lại &raquo;'); ?></button></p>
                    </form>
                </div>
                <?php else : ?>
            <?php
                                    $config = unserialize(base64_decode(Typecho_Cookie::get('__typecho_config')));
                                    $type = explode('_', $config['adapter']);
                                    $type = array_pop($type);
                                    $type = $type == 'Mysqli' ? 'Mysql' : $type;
                                    $installDb = $db;

                                    try {
                                        /** 初始化数据库结构 */
                                        $scripts = file_get_contents ('./install/' . $type . '.sql');
                                        $scripts = str_replace('typecho_', $config['prefix'], $scripts);

                                        if (isset($config['charset'])) {
                                            $scripts = str_replace('%charset%', $config['charset'], $scripts);
                                        }

                                        $scripts = explode(';', $scripts);
                                        foreach ($scripts as $script) {
                                            $script = trim($script);
                                            if ($script) {
                                                $installDb->query($script, Typecho_Db::WRITE);
                                            }
                                        }

                                        /** 全局变量 */
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'theme', 'user' => 0, 'value' => 'default')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'theme:default', 'user' => 0, 'value' => 'a:2:{s:7:"logoUrl";N;s:12:"sidebarBlock";a:5:{i:0;s:15:"ShowRecentPosts";i:1;s:18:"ShowRecentComments";i:2;s:12:"ShowCategory";i:3;s:11:"ShowArchive";i:4;s:9:"ShowOther";}}')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'timezone', 'user' => 0, 'value' => _t('28800'))));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'lang', 'user' => 0, 'value' => $lang)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'charset', 'user' => 0, 'value' => _t('UTF-8'))));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'contentType', 'user' => 0, 'value' => 'text/html')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'gzip', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'generator', 'user' => 0, 'value' => $options->generator)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'title', 'user' => 0, 'value' => 'Hello World')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'description', 'user' => 0, 'value' => 'Just So So ...')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'keywords', 'user' => 0, 'value' => 'typecho,php,blog')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'rewrite', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'frontPage', 'user' => 0, 'value' => 'recent')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'frontArchive', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsRequireMail', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsWhitelist', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsRequireURL', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsRequireModeration', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'plugins', 'user' => 0, 'value' => 'a:0:{}')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentDateFormat', 'user' => 0, 'value' => 'F jS, Y \a\t h:i a')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'siteUrl', 'user' => 0, 'value' => $config['siteUrl'])));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'defaultCategory', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'allowRegister', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'defaultAllowComment', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'defaultAllowPing', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'defaultAllowFeed', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'pageSize', 'user' => 0, 'value' => 5)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'postsListSize', 'user' => 0, 'value' => 10)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsListSize', 'user' => 0, 'value' => 10)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsHTMLTagAllowed', 'user' => 0, 'value' => NULL)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'postDateFormat', 'user' => 0, 'value' => 'Y-m-d')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'feedFullText', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'editorSize', 'user' => 0, 'value' => 350)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'autoSave', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'markdown', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'xmlrpcMarkdown', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsMaxNestingLevels', 'user' => 0, 'value' => 5)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsPostTimeout', 'user' => 0, 'value' => 24 * 3600 * 30)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsUrlNofollow', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsShowUrl', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsMarkdown', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsPageBreak', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsThreaded', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsPageSize', 'user' => 0, 'value' => 20)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsPageDisplay', 'user' => 0, 'value' => 'last')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsOrder', 'user' => 0, 'value' => 'ASC')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsCheckReferer', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsAutoClose', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsPostIntervalEnable', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsPostInterval', 'user' => 0, 'value' => 60)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsShowCommentOnly', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsAvatar', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsAvatarRating', 'user' => 0, 'value' => 'G')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'commentsAntiSpam', 'user' => 0, 'value' => 1)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'routingTable', 'user' => 0, 'value' => 'a:25:{s:5:"index";a:3:{s:3:"url";s:1:"/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:7:"archive";a:3:{s:3:"url";s:6:"/blog/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:2:"do";a:3:{s:3:"url";s:22:"/action/[action:alpha]";s:6:"widget";s:9:"Widget_Do";s:6:"action";s:6:"action";}s:4:"post";a:3:{s:3:"url";s:24:"/archives/[cid:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:10:"attachment";a:3:{s:3:"url";s:26:"/attachment/[cid:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:8:"category";a:3:{s:3:"url";s:17:"/category/[slug]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:3:"tag";a:3:{s:3:"url";s:12:"/tag/[slug]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:6:"author";a:3:{s:3:"url";s:22:"/author/[uid:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:6:"search";a:3:{s:3:"url";s:19:"/search/[keywords]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:10:"index_page";a:3:{s:3:"url";s:21:"/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:12:"archive_page";a:3:{s:3:"url";s:26:"/blog/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:13:"category_page";a:3:{s:3:"url";s:32:"/category/[slug]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:8:"tag_page";a:3:{s:3:"url";s:27:"/tag/[slug]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:11:"author_page";a:3:{s:3:"url";s:37:"/author/[uid:digital]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:11:"search_page";a:3:{s:3:"url";s:34:"/search/[keywords]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:12:"archive_year";a:3:{s:3:"url";s:18:"/[year:digital:4]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:13:"archive_month";a:3:{s:3:"url";s:36:"/[year:digital:4]/[month:digital:2]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:11:"archive_day";a:3:{s:3:"url";s:52:"/[year:digital:4]/[month:digital:2]/[day:digital:2]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:17:"archive_year_page";a:3:{s:3:"url";s:38:"/[year:digital:4]/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:18:"archive_month_page";a:3:{s:3:"url";s:56:"/[year:digital:4]/[month:digital:2]/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:16:"archive_day_page";a:3:{s:3:"url";s:72:"/[year:digital:4]/[month:digital:2]/[day:digital:2]/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:12:"comment_page";a:3:{s:3:"url";s:53:"[permalink:string]/comment-page-[commentPage:digital]";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:4:"feed";a:3:{s:3:"url";s:20:"/feed[feed:string:0]";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:4:"feed";}s:8:"feedback";a:3:{s:3:"url";s:31:"[permalink:string]/[type:alpha]";s:6:"widget";s:15:"Widget_Feedback";s:6:"action";s:6:"action";}s:4:"page";a:3:{s:3:"url";s:12:"/[slug].html";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}}')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'actionTable', 'user' => 0, 'value' => 'a:0:{}')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'panelTable', 'user' => 0, 'value' => 'a:0:{}')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'attachmentTypes', 'user' => 0, 'value' => '@image@')));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'secret', 'user' => 0, 'value' => Typecho_Common::randString(32, true))));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'installed', 'user' => 0, 'value' => 0)));
                                        $installDb->query($installDb->insert('table.options')->rows(array('name' => 'allowXmlRpc', 'user' => 0, 'value' => 2)));

                                        /** 初始分类 */
                                        $installDb->query($installDb->insert('table.metas')->rows(array('name' => _t('Mặc định'), 'slug' => 'default', 'type' => 'category', 'description' => _t('Chỉ là một danh mục mặc định'),
                                        'count' => 1, 'order' => 1)));

                                        /** 初始关系 */
                                        $installDb->query($installDb->insert('table.relationships')->rows(array('cid' => 1, 'mid' => 1)));

                                        /** 初始内容 */
                                        $installDb->query($installDb->insert('table.contents')->rows(array('title' => _t('Chào mừng đến với'), 'slug' => 'start', 'created' => Typecho_Date::time(), 'modified' => Typecho_Date::time(),
                                        'text' => '<!--markdown-->' . _t('Nếu bạn thấy bài viết này, có nghĩa là blog của bạn đã được cài đặt thành công.'), 'authorId' => 1, 'type' => 'post', 'status' => 'publish', 'commentsNum' => 1, 'allowComment' => 1,
                                        'allowPing' => 1, 'allowFeed' => 1, 'parent' => 0)));

                                        $installDb->query($installDb->insert('table.contents')->rows(array('title' => _t('About'), 'slug' => 'start-page', 'created' => Typecho_Date::time(), 'modified' => Typecho_Date::time(),
                                        'text' => '<!--markdown-->' . _t('Trang này được tạo bởi Typecho, đây chỉ là một trang thử nghiệm.'), 'authorId' => 1, 'order' => 0, 'type' => 'page', 'status' => 'publish', 'commentsNum' => 0, 'allowComment' => 1,
                                        'allowPing' => 1, 'allowFeed' => 1, 'parent' => 0)));

                                        /** 初始评论 */
                                        $installDb->query($installDb->insert('table.comments')->rows(array('cid' => 1, 'created' => Typecho_Date::time(), 'author' => 'Typecho', 'ownerId' => 1, 'url' => 'http://typecho.org',
                                        'ip' => '127.0.0.1', 'agent' => $options->generator, 'text' => 'Chào mừng đến với gia đình Typecho', 'type' => 'comment', 'status' => 'approved', 'parent' => 0)));

                                        /** 初始用户 */
                                        $password = empty($config['userPassword']) ? substr(uniqid(), 7) : $config['userPassword'];
                                        $hasher = new PasswordHash(8, true);

                                        $installDb->query($installDb->insert('table.users')->rows(array('name' => $config['userName'], 'password' => $hasher->HashPassword($password), 'mail' => $config['userMail'],
                                        'url' => 'http://www.typecho.org', 'screenName' => $config['userName'], 'group' => 'administrator', 'created' => Typecho_Date::time())));

                                        unset($_SESSION['typecho']);
                                        header('Location: ./install.php?finish&user=' . urlencode($config['userName'])
                                            . '&password=' . urlencode($password));
                                    } catch (Typecho_Db_Exception $e) {
                                        $success = false;
                                        $code = $e->getCode();
?>
<h1 class="typecho-install-title"><?php _e('Cài đặt thất bại!'); ?></h1>
                <div class="typecho-install-body">
                    <form method="post" action="?start" name="check">
<?php
                                        if(('Mysql' == $type && (1050 == $code || '42S01' == $code)) ||
                                        ('SQLite' == $type && ('HY000' == $code || 1 == $code)) ||
                                        ('Pgsql' == $type && '42P07' == $code)) {
                                            if(_r('delete')) {
                                                //Xóa dữ liệu gốc
                                                $dbPrefix = $config['prefix'];
                                                $tableArray = array($dbPrefix . 'comments', $dbPrefix . 'contents', $dbPrefix . 'fields', $dbPrefix . 'metas', $dbPrefix . 'options', $dbPrefix . 'relationships', $dbPrefix . 'users',);
                                                foreach($tableArray as $table) {
                                                    if($type == 'Mysql') {
                                                        $installDb->query("DROP TABLE IF EXISTS `{$table}`");
                                                    } elseif($type == 'Pgsql') {
                                                        $installDb->query("DROP TABLE {$table}");
                                                    } elseif($type == 'SQLite') {
                                                        $installDb->query("DROP TABLE {$table}");
                                                    }
                                                }
                                                echo '<p class="message success">' . _t('Dữ liệu gốc đã bị xóa') . '<br /><br /><button class="btn primary" type="submit" class="primary">'
                                                    . _t('Tiếp tục cài đặt &raquo;') . '</button></p>';
                                            } elseif (_r('goahead')) {
                                                //Sử dụng dữ liệu gốc
                                                //Nhưng để cập nhật trang web của người dùng
                                                $installDb->query($installDb->update('table.options')->rows(array('value' => $config['siteUrl']))->where('name = ?', 'siteUrl'));
                                                unset($_SESSION['typecho']);
                                                header('Location: ./install.php?finish&use_old');
                                                exit;
                                            } else {
                                                 echo '<p class="message error">' . _t('Trình cài đặt kiểm tra xem bảng dữ liệu gốc đã tồn tại chưa.')
                                                    . '<br /><br />' . '<button type="submit" name="delete" value="1" class="btn btn-warn">' . _t('Xóa dữ liệu gốc') . '</button> '
                                                    . _t('hoặc') . ' <button type="submit" name="goahead" value="1" class="btn primary">' . _t('Sử dụng dữ liệu gốc') . '</button></p>';
                                            }
                                        } else {
                                            echo '<p class="message error">' . _t('Trình cài đặt gặp lỗi sau: "%s". Chương trình đã bị chấm dứt, vui lòng kiểm tra thông tin cấu hình của bạn.',$e->getMessage()) . '</p>';
                                        }
                                        ?>
                    </form>
                </div>
                                        <?php
                                    }
            ?>
                <?php endif;?>
            <?php elseif (isset($_GET['config'])): ?>
            <?php
                    $adapters = array('Mysql', 'Mysqli', 'Pdo_Mysql', 'SQLite', 'Pdo_SQLite', 'Pgsql', 'Pdo_Pgsql');
                    foreach ($adapters as $firstAdapter) {
                        if (_p($firstAdapter)) {
                            break;
                        }
                    }
                    $adapter = _r('dbAdapter', $firstAdapter);
                    $parts = explode('_', $adapter);

                    $type = $adapter == 'Mysqli' ? 'Mysql' : array_pop($parts);
            ?>
                <form method="post" action="?config" name="config">
                    <h1 class="typecho-install-title"><?php _e('Xác nhận cấu hình của bạn'); ?></h1>
                    <div class="typecho-install-body">
                        <h2><?php _e('Cấu hình cơ sở dữ liệu'); ?></h2>
                        <?php
                            if ('config' == _r('action')) {
                                $success = true;

                                if (_r('created') && !file_exists('./config.inc.php')) {
                                    echo '<p class="message error">' . _t('Tệp cấu hình bạn đã tạo theo cách thủ công không được phát hiện, vui lòng kiểm tra và tạo lại') . '</p>';
                                    $success = false;
                                } else {
                                    if (NULL == _r('userUrl')) {
                                        $success = false;
                                        echo '<p class="message error">' . _t('Vui lòng điền vào địa chỉ trang web của bạn') . '</p>';
                                    } else if (NULL == _r('userName')) {
                                        $success = false;
                                        echo '<p class="message error">' . _t('Vui lòng điền vào tên người dùng của bạn') . '</p>';
                                    } else if (NULL == _r('userMail')) {
                                        $success = false;
                                        echo '<p class="message error">' . _t('Vui lòng điền địa chỉ email của bạn') . '</p>';
                                    } else if (32 < strlen(_r('userName'))) {
                                        $success = false;
                                        echo '<p class="message error">' . _t('Độ dài của tên người dùng vượt quá giới hạn, vui lòng không vượt quá 32 ký tự') . '</p>';
                                    } else if (200 < strlen(_r('userMail'))) {
                                        $success = false;
                                        echo '<p class="message error">' . _t('Độ dài của hộp thư vượt quá giới hạn, vui lòng không vượt quá 200 ký tự') . '</p>';
                                    }
                                }

                                $_dbConfig = _rFrom('dbHost', 'dbUser', 'dbPassword', 'dbCharset', 'dbPort', 'dbDatabase', 'dbFile', 'dbDsn');

                                $_dbConfig = array_filter($_dbConfig);
                                $dbConfig = array();
                                foreach ($_dbConfig as $key => $val) {
                                    $dbConfig[strtolower (substr($key, 2))] = $val;
                                }

                                // 在特殊服务器上的特殊安装过程处理
                                if (_r('config')) {
                                    $replace = array_keys($dbConfig);
                                    foreach ($replace as &$key) {
                                        $key = '{' . $key . '}';
                                    }

                                    if (!empty($_dbConfig['dbDsn'])) {
                                        $dbConfig['dsn'] = str_replace($replace, array_values($dbConfig), $dbConfig['dsn']);
                                    }
                                    $config = str_replace($replace, array_values($dbConfig), _r('config'));
                                }

                                if (!isset($config) && $success && !_r('created')) {
                                    $installDb = new Typecho_Db($adapter, _r('dbPrefix'));
                                    $installDb->addServer($dbConfig, Typecho_Db::READ | Typecho_Db::WRITE);


                                    /** 检测数据库配置 */
                                    try {
                                        $installDb->query('SELECT 1=1');
                                    } catch (Typecho_Db_Adapter_Exception $e) {
                                        $success = false;
                                        echo '<p class="message error">'
                                        . _t('Xin lỗi, không thể kết nối với cơ sở dữ liệu, vui lòng kiểm tra cấu hình cơ sở dữ liệu trước khi tiến hành cài đặt') . '</p>';
                                    } catch (Typecho_Db_Exception $e) {
                                        $success = false;
                                        echo '<p class="message error">'
                                        . _t('Trình cài đặt gặp lỗi sau: " %s ". Chương trình đã bị chấm dứt, vui lòng kiểm tra thông tin cấu hình của bạn.',$e->getMessage()) . '</p>';
                                    }
                                }

                                if($success) {
                                    // Đặt lại trạng thái cơ sở dữ liệu ban đầu
                                    if (isset($installDb)) {
                                        try {
                                            $installDb->query($installDb->update('table.options')
                                                ->rows(array('value' => 0))->where('name = ?', 'installed'));
                                        } catch (Exception $e) {
                                            // do nothing
                                        }
                                    }

                                    Typecho_Cookie::set('__typecho_config', base64_encode(serialize(array_merge(array(
                                        'prefix'    =>  _r('dbPrefix'),
                                        'userName'  =>  _r('userName'),
                                        'userPassword'  =>  _r('userPassword'),
                                        'userMail'  =>  _r('userMail'),
                                        'adapter'   =>  $adapter,
                                        'siteUrl'   =>  _r('userUrl')
                                    ), $dbConfig))));

                                    if (_r('created')) {
                                        header('Location: ./install.php?start');
                                        exit;
                                    }

                                    /** 初始化配置文件 */
                                    $lines = array_slice(file(__FILE__), 1, 52);
                                    $lines[] = "
/** 定义数据库参数 */
\$db = new Typecho_Db('{$adapter}', '" . _r('dbPrefix') . "');
\$db->addServer(" . (empty($config) ? var_export($dbConfig, true) : $config) . ", Typecho_Db::READ | Typecho_Db::WRITE);
Typecho_Db::set(\$db);
";
                                    $contents = implode('', $lines);
                                    if (!Typecho_Common::isAppEngine()) {
                                        @file_put_contents('./config.inc.php', $contents);
                                    }

                                    if (!file_exists('./config.inc.php')) {
                                    ?>
<div class="message notice"><p><?php _e('Trình cài đặt không thể tự động tạo tệp <strong>config.inc.php</strong>'); ?><br />
<?php _e('Bạn có thể tạo thủ công tệp <strong>config.inc.php</strong> trong thư mục gốc của trang web và sao chép mã sau vào đó'); ?></p>
<p><textarea rows="5" onmouseover="this.select();" class="w-100 mono" readonly><?php echo htmlspecialchars($contents); ?></textarea></p>
<p><button name="created" value="1" type="submit" class="btn primary">Đã tạo, tiếp tục cài đặt &raquo;</button></p></div>
                                    <?php
                                    } else {
                                        header('Location: ./install.php?start');
                                        exit;
                                    }
                                }

                                // 安装不成功删除配置文件
                                if($success != true && file_exists(__TYPECHO_ROOT_DIR__ . '/config.inc.php')) {
                                    @unlink(__TYPECHO_ROOT_DIR__ . '/config.inc.php');
                                }
                            }
                        ?>
                        <ul class="typecho-option">
                            <li>
                            <label for="dbAdapter" class="typecho-label"><?php _e('Bộ điều hợp cơ sở dữ liệu'); ?></label>
                            <select name="dbAdapter" id="dbAdapter">
                                <?php if (_p('Mysql')): ?><option value="Mysql"<?php if('Mysql' == $adapter): ?> selected="selected"<?php endif; ?>><?php _e('Mysql function') ?></option><?php endif; ?>
                                <?php if (_p('SQLite')): ?><option value="SQLite"<?php if('SQLite' == $adapter): ?> selected="selected"<?php endif; ?>><?php _e('SQLite function (SQLite 2.x)') ?></option><?php endif; ?>
                                <?php if (_p('Pgsql')): ?><option value="Pgsql"<?php if('Pgsql' == $adapter): ?> selected="selected"<?php endif; ?>><?php _e('Pgsql function') ?></option><?php endif; ?>
                                <?php if (_p('Pdo_Mysql')): ?><option value="Pdo_Mysql"<?php if('Pdo_Mysql' == $adapter): ?> selected="selected"<?php endif; ?>><?php _e('Pdo driver Mysql') ?></option><?php endif; ?>
                                <?php if (_p('Pdo_SQLite')): ?><option value="Pdo_SQLite"<?php if('Pdo_SQLite' == $adapter): ?> selected="selected"<?php endif; ?>><?php _e('Pdo driver SQLite (SQLite 3.x)') ?></option><?php endif; ?>
                                <?php if (_p('Pdo_Pgsql')): ?><option value="Pdo_Pgsql"<?php if('Pdo_Pgsql' == $adapter): ?> selected="selected"<?php endif; ?>><?php _e('Pdo driver PostgreSql') ?></option><?php endif; ?>
                            </select>
                            <p class="description"><?php _e('Vui lòng chọn bộ điều hợp thích hợp theo loại cơ sở dữ liệu của bạn'); ?></p>
                            </li>
                            <?php require_once './install/' . $type . '.php'; ?>
                            <li>
                            <label class="typecho-label" for="dbPrefix"><?php _e('Tiền tố cơ sở dữ liệu'); ?></label>
                            <input type="text" class="text" name="dbPrefix" id="dbPrefix" value="<?php _v('dbPrefix', 'typecho_'); ?>" />
                            <p class="description"><?php _e('Tiền tố mặc định là "typecho_"'); ?></p>
                            </li>
                        </ul>

                        <script>
                        var _select = document.config.dbAdapter;
                        _select.onchange = function() {
                            setTimeout("window.location.href = 'install.php?config&dbAdapter=" + this.value + "'; ",0);
                        }
                        </script>

                        <h2><?php _e('Tạo tài khoản quản trị của bạn'); ?></h2>
                        <ul class="typecho-option">
                            <li>
                            <label class="typecho-label" for="userUrl"><?php _e('Địa chỉ trang web'); ?></label>
                            <input type="text" name="userUrl" id="userUrl" class="text" value="<?php _v('userUrl', _u()); ?>" />
                            <p class="description"><?php _e('Đây là đường dẫn được chương trình tự động tạo, vui lòng sửa đổi nếu nó không chính xác'); ?></p>
                            </li>
                            <li>
                            <label class="typecho-label" for="userName"><?php _e('Tên tài khoản'); ?></label>
                            <input type="text" name="userName" id="userName" class="text" value="<?php _v('userName', 'admin'); ?>" />
                            <p class="description"><?php _e('Vui lòng điền vào tên người dùng của bạn'); ?></p>
                            </li>
                            <li>
                            <label class="typecho-label" for="userPassword"><?php _e('Mật khẩu đăng nhập'); ?></label>
                            <input type="password" name="userPassword" id="userPassword" class="text" value="<?php _v('userPassword'); ?>" />
                            <p class="description"><?php _e('Vui lòng điền mật khẩu đăng nhập của bạn, nếu bạn để trống hệ thống sẽ tạo ngẫu nhiên cho bạn'); ?></p>
                            </li>
                            <li>
                            <label class="typecho-label" for="userMail"><?php _e('Địa chỉ thư điện tử'); ?></label>
                            <input type="text" name="userMail" id="userMail" class="text" value="<?php _v('userMail', 'webmaster@yourdomain.com'); ?>" />
                            <p class="description"><?php _e('Vui lòng điền vào một địa chỉ email chung'); ?></p>
                            </li>
                        </ul>
                    </div>
                    <input type="hidden" name="action" value="config" />
                    <p class="submit"><button type="submit" class="btn primary"><?php _e('Xác nhận, bắt đầu cài đặt &raquo;'); ?></button></p>
                </form>
            <?php  else: ?>
                <form method="post" action="?config">
                <h1 class="typecho-install-title"><?php _e('Chào mừng đến với'); ?></h1>
                <div class="typecho-install-body">
                <h2><?php _e('Ghi chú cài đặt'); ?></h2>
                <p><strong><?php _e('Chương trình cài đặt này sẽ tự động phát hiện xem môi trường máy chủ có đáp ứng các yêu cầu cấu hình tối thiểu hay không. Nếu nó không đáp ứng các yêu cầu cấu hình tối thiểu, một thông báo nhắc nhở sẽ xuất hiện ở trên, vui lòng làm theo thông tin nhắc để kiểm tra cấu hình máy chủ của bạn.'); ?></strong></p>
                <h2><?php _e('Giấy phép và thỏa thuận'); ?></h2>
                <p><?php _e('Typecho được phát hành theo thỏa thuận <a href="http://www.gnu.org/copyleft/gpl.html">GPL.</a> Chúng tôi cho phép người dùng sử dụng, sao chép, sửa đổi và phân phối chương trình này trong phạm vi của thỏa thuận GPL.'); ?>
                <?php _e('Trong phạm vi của giấy phép GPL, bạn có thể tự do sử dụng nó cho các mục đích thương mại và phi thương mại.'); ?></p>
                <p><?php _e('Phần mềm Typecho được hỗ trợ bởi cộng đồng của nó và nhóm phát triển cốt lõi chịu trách nhiệm phát triển hàng ngày của chương trình bảo trì và xây dựng các tính năng mới.'); ?>
                <?php _e('Nếu bạn gặp sự cố khi sử dụng, lỗi trong chương trình và các tính năng mới dự kiến, bạn có thể giao tiếp trong cộng đồng hoặc trực tiếp đóng góp mã cho chúng tôi.'); ?>
                <?php _e('Đối với những người đóng góp xuất sắc, tên của anh ấy sẽ xuất hiện trong danh sách những người đóng góp.'); ?></p>
                </div>
                <p class="submit">
                    <button type="submit" class="btn primary"><?php _e('Tôi đã sẵn sàng &raquo;'); ?></button>

                    <?php if (count($langs) > 1): ?>
                    <select style="float: right" onchange="window.location.href='install.php?lang=' + this.value">
                        <?php foreach ($langs as $key => $val): ?>
                        <option value="<?php echo $key; ?>"<?php if ($lang == $key): ?> selected<?php endif; ?>><?php echo $val; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </p>
                </form>
            <?php endif; ?>

            </div>
        </div>
    </div>
</div>
<?php
include 'admin/copyright.php';
include 'admin/footer.php';
?>
