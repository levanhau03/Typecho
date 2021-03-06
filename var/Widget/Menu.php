<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
class Widget_Menu extends Typecho_Widget
{
    private $_menu = array();
    private $_currentParent = 1;
    private $_currentChild = 0;
    private $_currentUrl;
    protected $options;
    protected $user;
    public $title;
    public $addLink;
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        /** 初始化常用组件 */
        $this->options = $this->widget('Widget_Options');
        $this->user = $this->widget('Widget_User');
    }

    public function execute()
    {
        $parentNodes = array(NULL, _t('Bảng điều khiển'), _t('Khởi tạo'), _t('Quản lý'), _t('Thiết lập'));

        $childNodes =  array(
        array(
            array(_t('Đăng nhập'), _t('Đăng nhập %s', $this->options->title), 'login.php', 'visitor'),
            array(_t('Đăng ký'), _t('Đăng ký %s', $this->options->title), 'register.php', 'visitor')
        ),
        array(
            array(_t('Tổng quát'), _t('Tóm tắt trang web'), 'index.php', 'subscriber'),
            array(_t('Thiết lập cá nhân'), _t('Thiết lập cá nhân'), 'profile.php', 'subscriber'),
            array(_t('Plugin'), _t('Quản lý plugin'), 'plugins.php', 'administrator'),
            array(array('Widget_Plugins_Config', 'getMenuTitle'), array('Widget_Plugins_Config', 'getMenuTitle'), 'options-plugin.php?config=', 'administrator', true),
            array(_t('Giao diện'), _t('Giao diện trang web'), 'themes.php', 'administrator'),
            array(array('Widget_Themes_Files', 'getMenuTitle'), array('Widget_Themes_Files', 'getMenuTitle'), 'theme-editor.php', 'administrator', true),
            array(_t('Đặt giao diện'), _t('Đặt giao diện'), 'options-theme.php', 'administrator', true),
            array(_t('Sao lưu'), _t('Sao lưu'), 'backup.php', 'administrator'),
            array(_t('Nâng cấp'), _t('Quy trình nâng cấp'), 'upgrade.php', 'administrator', true),
            array(_t('Chào mừng'), _t('Chào mừng'), 'welcome.php', 'subscriber', true)
        ),
        array(
            array(_t('Bài viết mới'), _t('Đăng bài viết mới'), 'write-post.php', 'contributor'),
            array(array('Widget_Contents_Post_Edit', 'getMenuTitle'), array('Widget_Contents_Post_Edit', 'getMenuTitle'), 'write-post.php?cid=', 'contributor', true),
            array(_t('Tạo trang mới'), _t('Tạo trang mới'), 'write-page.php', 'editor'),
            array(array('Widget_Contents_Page_Edit', 'getMenuTitle'), array('Widget_Contents_Page_Edit', 'getMenuTitle'), 'write-page.php?cid=', 'editor', true),
        ),
        array(
            array(_t('Bài viết'), _t('Quản lý các bài viết'), 'manage-posts.php', 'contributor', false, 'write-post.php'),
            array(array('Widget_Contents_Post_Admin', 'getMenuTitle'), array('Widget_Contents_Post_Admin', 'getMenuTitle'), 'manage-posts.php?uid=', 'contributor', true),
            array(_t('Trang độc lập'), _t('Quản lý các trang độc lập'), 'manage-pages.php', 'editor', false, 'write-page.php'),
            array(_t('Bình luận'), _t('Quản lý bình luận'), 'manage-comments.php', 'contributor'),
            array(array('Widget_Comments_Admin', 'getMenuTitle'), array('Widget_Comments_Admin', 'getMenuTitle'), 'manage-comments.php?cid=', 'contributor', true),
            array(_t('Danh mục'), _t('Quản lý danh mục'), 'manage-categories.php', 'editor', false, 'category.php'),
            array(_t('Danh mục mới'), _t('Danh mục mới'), 'category.php', 'editor', true),
            array(array('Widget_Metas_Category_Admin', 'getMenuTitle'), array('Widget_Metas_Category_Admin', 'getMenuTitle'), 'manage-categories.php?parent=', 'editor', true, array('Widget_Metas_Category_Admin', 'getAddLink')),
            array(array('Widget_Metas_Category_Edit', 'getMenuTitle'), array('Widget_Metas_Category_Edit', 'getMenuTitle'), 'category.php?mid=', 'editor', true),
            array(array('Widget_Metas_Category_Edit', 'getMenuTitle'), array('Widget_Metas_Category_Edit', 'getMenuTitle'), 'category.php?parent=', 'editor', true),
            array(_t('Thẻ'), _t('Quản lý thẻ'), 'manage-tags.php', 'editor'),
            array(array('Widget_Metas_Tag_Admin', 'getMenuTitle'), array('Widget_Metas_Tag_Admin', 'getMenuTitle'), 'manage-tags.php?mid=', 'editor', true),
            array(_t('Tài liệu'), _t('Quản lý tệp'), 'manage-medias.php', 'editor'),
            array(array('Widget_Contents_Attachment_Edit', 'getMenuTitle'), array('Widget_Contents_Attachment_Edit', 'getMenuTitle'), 'media.php?cid=', 'contributor', true),
            array(_t('Người dùng'), _t('Quản lý người dùng'), 'manage-users.php', 'administrator', false, 'user.php'),
            array(_t('Người dùng mới'), _t('Người dùng mới'), 'user.php', 'administrator', true),
            array(array('Widget_Users_Edit', 'getMenuTitle'), array('Widget_Users_Edit', 'getMenuTitle'), 'user.php?uid=', 'administrator', true),
        ),
        array(
            array(_t('Cơ bản'), _t('Cài đặt cơ bản'), 'options-general.php', 'administrator'),
            array(_t('Bình luận'), _t('Cài đặt bình luận'), 'options-discussion.php', 'administrator'),
            array(_t('Đọc'), _t('Cài đặt đọc'), 'options-reading.php', 'administrator'),
            array(_t('Permalink'), _t('Cài đặt Permalink'), 'options-permalink.php', 'administrator'),
        ));

        /** 获取扩展菜单 */
        $panelTable = unserialize($this->options->panelTable);
        $extendingParentMenu = empty($panelTable['parent']) ? array() : $panelTable['parent'];
        $extendingChildMenu = empty($panelTable['child']) ? array() : $panelTable['child'];
        $currentUrl = $this->request->makeUriByRequest();
        $adminUrl = $this->options->adminUrl;
        $menu = array();
        $defaultChildeNode = array(NULL, NULL, NULL, 'administrator', false, NULL);

        $currentUrlParts = parse_url($currentUrl);
        $currentUrlParams = array();
        if (!empty($currentUrlParts['query'])) {
            parse_str($currentUrlParts['query'], $currentUrlParams);
        }

        if ('/' == $currentUrlParts['path'][strlen($currentUrlParts['path']) - 1]) {
            $currentUrlParts['path'] .= 'index.php';
        }

        foreach ($extendingParentMenu as $key => $val) {
            $parentNodes[10 + $key] = $val;
        }

        foreach ($extendingChildMenu as $key => $val) {
            $childNodes[$key] = array_merge(isset($childNodes[$key]) ? $childNodes[$key] : array(), $val);
        }

        foreach ($parentNodes as $key => $parentNode) {
            // this is a simple struct than before
            $children = array();
            $showedChildrenCount = 0;
            $firstUrl = NULL;
            
            foreach ($childNodes[$key] as $inKey => $childNode) {
                // magic merge
                $childNode += $defaultChildeNode;
                list ($name, $title, $url, $access, $hidden, $addLink) = $childNode;

                // 保存最原始的hidden信息
                $orgHidden = $hidden;

                // parse url
                $url = Typecho_Common::url($url, $adminUrl);

                // compare url
                $urlParts = parse_url($url);
                $urlParams = array();
                if (!empty($urlParts['query'])) {
                    parse_str($urlParts['query'], $urlParams);
                }

                $validate = true;
                if ($urlParts['path'] != $currentUrlParts['path']) {
                    $validate = false;
                } else {
                    foreach ($urlParams as $paramName => $paramValue) {
                        if (!isset($currentUrlParams[$paramName])) {
                            $validate = false;
                            break;
                        }
                    }
                }
                
                if ($validate
                    && basename($urlParts['path']) == 'extending.php'
                    && !empty($currentUrlParams['panel']) && !empty($urlParams['panel'])
                    && $urlParams['panel'] != $currentUrlParams['panel']){
                    $validate = false;
                }
                
                if ($hidden && $validate) {
                    $hidden = false;
                }

                if (!$hidden && !$this->user->pass($access, true)) {
                    $hidden = true;
                }

                if (!$hidden) {
                    $showedChildrenCount ++;

                    if (empty($firstUrl)) {
                        $firstUrl = $url;
                    }

                    if (is_array($name)) {
                        list($widget, $method) = $name;
                        $name = Typecho_Widget::widget($widget)->$method();
                    }
                    
                    if (is_array($title)) {
                        list($widget, $method) = $title;
                        $title = Typecho_Widget::widget($widget)->$method();
                    }

                    if (is_array($addLink)) {
                        list($widget, $method) = $addLink;
                        $addLink = Typecho_Widget::widget($widget)->$method();
                    }
                }

                if ($validate) {
                    if ('visitor' != $access) {
                        $this->user->pass($access);
                    }
                    
                    $this->_currentParent = $key;
                    $this->_currentChild = $inKey;
                    $this->title = $title;
                    $this->addLink = $addLink ? Typecho_Common::url($addLink, $adminUrl) : NULL;
                } 

                $children[$inKey] = array(
                    $name,
                    $title,
                    $url,
                    $access,
                    $hidden,
                    $addLink,
                    $orgHidden
                );
            }

            $menu[$key] = array($parentNode, $showedChildrenCount > 0, $firstUrl,$children);
        }

        $this->_menu = $menu;
        $this->_currentUrl = $currentUrl;
    }

    public function getCurrentMenu()
    {
        return $this->_currentParent > 0 ? $this->_menu[$this->_currentParent][3][$this->_currentChild] : NULL;
    }

    public function output($class = 'focus', $childClass = 'focus')
    {
        foreach ($this->_menu as $key => $node) {
            if (!$node[1] || !$key) {
                continue;
            }

            echo "<ul class=\"root" . ($key == $this->_currentParent ? ' ' . $class : NULL) 
                . "\"><li class=\"parent\"><a href=\"{$node[2]}\">{$node[0]}</a>"
                . "</li><ul class=\"child\">";

            $last = 0;
            foreach ($node[3] as $inKey => $inNode) {
                if (!$inNode[4]) {
                    $last = $inKey;
                }
            }
            
            foreach ($node[3] as $inKey => $inNode) {
                if ($inNode[4]) {
                    continue;
                }

                $classes = array();
                if ($key == $this->_currentParent && $inKey == $this->_currentChild) {
                    $classes[] = $childClass;
                } else if ($inNode[6]) {
                    continue;
                }

                if ($inKey == $last) {
                    $classes[] = 'last';
                }

                echo "<li" . (!empty($classes) ? ' class="' . implode(' ', $classes) . '"' : NULL) .
                    "><a href=\"" . ($key == $this->_currentParent && $inKey == $this->_currentChild ? $this->_currentUrl : $inNode[2]) . "\">{$inNode[0]}</a></li>";
            }

            echo "</ul></ul>";
        }
    }
}

