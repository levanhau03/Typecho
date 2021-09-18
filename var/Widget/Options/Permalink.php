<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 基本设置
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 基本设置组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Options_Permalink extends Widget_Abstract_Options implements Widget_Interface_Do
{
    /**
     * 编码自定义的路径
     *
     * @access private
     * @param string $rule 待编码的路径
     * @return string
     */
    protected function encodeRule($rule)
    {
        return str_replace(array('{cid}', '{slug}', '{category}', '{directory}', '{year}', '{month}', '{day}', '{mid}'),
            array('[cid:digital]', '[slug]', '[category]', '[directory:split:0]',
                '[year:digital:4]', '[month:digital:2]', '[day:digital:2]', '[mid:digital]'), $rule);
    }

    /**
     * 解析自定义的路径
     *
     * @access private
     * @param string $rule 待解码的路径
     * @return string
     */
    protected function decodeRule($rule)
    {
        return preg_replace("/\[([_a-z0-9-]+)[^\]]*\]/i", "{\\1}", $rule);
    }

    /**
     * 检验规则是否冲突
     *
     * @access public
     * @param string $value 路由规则
     * @return boolean
     */
    public function checkRule($value)
    {
        if ('custom' != $value) {
            return true;
        }

        $routingTable = $this->options->routingTable;
        $currentTable = array('custom' => array('url' => $this->encodeRule($this->request->customPattern)));
        $parser = new Typecho_Router_Parser($currentTable);
        $currentTable = $parser->parse();
        $regx = $currentTable['custom']['regx'];

        foreach ($routingTable as $key => $val) {
            if ('post' != $key && 'page' != $key) {
                $pathInfo = preg_replace("/\[([_a-z0-9-]+)[^\]]*\]/i", "{\\1}", $val['url']);
                $pathInfo = str_replace(array('{cid}', '{slug}', '{category}', '{year}', '{month}', '{day}', '{', '}'),
                    array('123', 'hello', 'default', '2008', '08', '08', '', ''), $pathInfo);

                if (preg_match($regx, $pathInfo)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 检查pagePattern里是否含有必要参数
     * 
     * @param mixed $value 
     * @access public
     * @return void
     */
    public function checkPagePattern($value)
    {
        return strpos($value, '{slug}') !== false || strpos($value, '{cid}') !== false;
    }

    /**
     * 检查categoryPattern里是否含有必要参数
     * 
     * @param mixed $value 
     * @access public
     * @return void
     */
    public function checkCategoryPattern($value)
    {
        return strpos($value, '{slug}') !== false || strpos($value, '{mid}') !== false || strpos($value, '{directory}') !== false;
    }

    /**
     * 检测是否可以rewrite
     *
     * @access public
     * @param string $value 是否打开rewrite
     * @return void
     */
    public function checkRewrite($value)
    {
        if ($value) {
            $this->user->pass('administrator');

            /** 首先直接请求远程地址验证 */
            $client = Typecho_Http_Client::get();
            $hasWrote = false;

            if (!file_exists(__TYPECHO_ROOT_DIR__ . '/.htaccess')
                && !Typecho_Common::isAppEngine()) {
                if (is_writeable(__TYPECHO_ROOT_DIR__)) {
                    $parsed = parse_url($this->options->siteUrl);
                    $basePath = empty($parsed['path']) ? '/' : $parsed['path'];
                    $basePath = rtrim($basePath, '/') . '/';

                    $hasWrote = file_put_contents(__TYPECHO_ROOT_DIR__ . '/.htaccess', "<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase {$basePath}
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ {$basePath}index.php/$1 [L]
</IfModule>");
                }
            }

            try {
                if ($client) {
                    /** 发送一个rewrite地址请求 */
                    $client->setData(array('do' => 'remoteCallback'))
                    ->setHeader('User-Agent', $this->options->generator)
                    ->send(Typecho_Common::url('/action/ajax', $this->options->siteUrl));

                    if (200 == $client->getResponseStatus() && 'OK' == $client->getResponseBody()) {
                        return true;
                    }
                }

                if (false !== $hasWrote) {
                    @unlink(__TYPECHO_ROOT_DIR__ . '/.htaccess');

                    //增强兼容性,使用wordpress的redirect式rewrite规则,虽然效率有点地下,但是对fastcgi模式兼容性较好
                    $hasWrote = file_put_contents(__TYPECHO_ROOT_DIR__ . '/.htaccess', "<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase {$basePath}
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . {$basePath}index.php [L]
</IfModule>");

                    //再次进行验证
                    $client = Typecho_Http_Client::get();

                    if ($client) {
                        /** 发送一个rewrite地址请求 */
                        $client->setData(array('do' => 'remoteCallback'))
                        ->setHeader('User-Agent', $this->options->generator)
                        ->send(Typecho_Common::url('/action/ajax', $this->options->siteUrl));

                        if (200 == $client->getResponseStatus() && 'OK' == $client->getResponseBody()) {
                            return true;
                        }
                    }

                    unlink(__TYPECHO_ROOT_DIR__ . '/.htaccess');
                }
            } catch (Typecho_Http_Client_Exception $e) {
                if (false !== $hasWrote) {
                    @unlink(__TYPECHO_ROOT_DIR__ . '/.htaccess');
                }
                return false;
            }

            return false;
        } else if (file_exists(__TYPECHO_ROOT_DIR__ . '/.htaccess')) {
            @unlink(__TYPECHO_ROOT_DIR__ . '/.htaccess');
        }

        return true;
    }

    /**
     * 输出表单结构
     *
     * @access public
     * @return Typecho_Widget_Helper_Form
     */
    public function form()
    {
        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form($this->security->getRootUrl('index.php/action/options-permalink'),
        Typecho_Widget_Helper_Form::POST_METHOD);

        if (!defined('__TYPECHO_REWRITE__')) {
            /** 是否使用地址重写功能 */
            $rewrite = new Typecho_Widget_Helper_Form_Element_Radio('rewrite', array('0' => _t('Không kích hoạt'), '1' => _t('Kích hoạt')),
                $this->options->rewrite, _t('Có sử dụng chức năng Rewrite hay không'), _t('Rewrite là một chức năng được cung cấp bởi một số phần mềm máy chủ để tối ưu hóa các kết nối nội bộ.') . '<br />'
                . _t('Bật chức năng này có thể làm cho liên kết của bạn trông hoàn toàn tĩnh.')); 

            // disable rewrite check when rewrite opened
            if (!$this->options->rewrite && !$this->request->is('enableRewriteAnyway=1')) {
                $errorStr = _t('Kiểm tra chức năng viết lại không thành công, vui lòng kiểm tra cài đặt máy chủ của bạn');

                /** 如果是apache服务器, 可能存在无法写入.htaccess文件的现象 */
                if (((isset($_SERVER['SERVER_SOFTWARE']) && false !== strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'apache'))
                    || function_exists('apache_get_version')) && !file_exists(__TYPECHO_ROOT_DIR__ . '/.htaccess')
                    && !is_writeable(__TYPECHO_ROOT_DIR__)) {
                    $errorStr .= '<br /><strong>' . _t('Chúng tôi đã phát hiện ra rằng bạn đang sử dụng máy chủ apache nhưng chương trình không thể tạo tệp .htaccess trong thư mục gốc. Đây có thể là nguyên nhân gây ra lỗi này.')
                        . _t('Vui lòng điều chỉnh quyền thư mục của bạn hoặc tạo tệp .htaccess theo cách thủ công.') . '</strong>';
                }

                $errorStr .= '<br /><input type="checkbox" name="enableRewriteAnyway" id="enableRewriteAnyway" value="1" />'
                    . ' <label for="enableRewriteAnyway">' . _t('Nếu bạn vẫn muốn kích hoạt tính năng này, vui lòng kiểm tra tại đây') . '</label>';
                $rewrite->addRule(array($this, 'checkRewrite'), $errorStr);
            }

            $form->addInput($rewrite);
        }

        $patterns = array('/archives/[cid:digital]/' => _t('Kiểu mặc định') . ' <code>/archives/{cid}/</code>',
        '/archives/[slug].html' => _t('Kiểu wordpress') . ' <code>/archives/{slug}.html</code>',
        '/[year:digital:4]/[month:digital:2]/[day:digital:2]/[slug].html' => _t('Lưu trữ theo ngày') . ' <code>/archives/{year}/{month}/{day}/{slug}.html</code>',
        '/[category]/[slug].html' => _t('Lưu trữ theo danh mục') . ' <code>/{category}/{slug}.html</code>');

        /** 自定义文章路径 */
        $postPatternValue = $this->options->routingTable['post']['url'];

        /** 增加个性化路径 */
        $customPatternValue = NULL;
        if (isset($this->request->__typecho_form_item_postPattern)) {
            $customPatternValue = $this->request->__typecho_form_item_postPattern;
            Typecho_Cookie::delete('__typecho_form_item_postPattern');
        } else if (!isset($patterns[$postPatternValue])) {
            $customPatternValue = $this->decodeRule($postPatternValue);
        }
        $patterns['custom'] = _t('Cá nhân hóa') . ' <input type="text" class="w-50 text-s mono" name="customPattern" value="' . $customPatternValue . '" />';

        $postPattern = new Typecho_Widget_Helper_Form_Element_Radio('postPattern', $patterns,
        $postPatternValue, _t('Đường dẫn bài viết tùy chỉnh'), _t('Các thông số có sẵn: <code>{cid}</code> ID bài, <code>{slug}</code> Tên viết tắt bài, <code>{category}</code> Danh mục, <code>{directory}</code> Chuyên mục, <code>{year}</code> Năm, <code>{month}</code> Tháng, <code>{day}</code> Ngày')
            . '<br />' . _t('Chọn một phong cách đường dẫn tĩnh phù hợp cho bài viết để làm cho các liên kết trang web của bạn trở nên thân thiện hơn.') 
            . '<br />' . _t('Khi bạn chọn một kiểu liên kết, vui lòng không sửa đổi nó một cách nhẹ nhàng.'));
        if ($customPatternValue) {
            $postPattern->value('custom');
        }
        $form->addInput($postPattern->multiMode());

        /** 独立页面后缀名 */
        $pagePattern = new Typecho_Widget_Helper_Form_Element_Text('pagePattern', NULL, $this->decodeRule($this->options->routingTable['page']['url']), _t('Đường dẫn trang độc lập'), _t('Các thông số có sẵn: <code>{cid}</code> Id trang, <code>{slug}</code> Tên viết tắt trang')
            . '<br />' . _t('Vui lòng bao gồm ít nhất một trong các tham số trên trong đường dẫn.'));
        $pagePattern->input->setAttribute('class', 'mono w-60');
        $form->addInput($pagePattern->addRule(array($this, 'checkPagePattern'), _t('Đường dẫn trang độc lập không chứa {cid} hoặc {slug} ')));

        /** 分类页面 */
        $categoryPattern = new Typecho_Widget_Helper_Form_Element_Text('categoryPattern', NULL, $this->decodeRule($this->options->routingTable['category']['url']), _t('Đường dẫn danh mục'), _t('Các thông số có sẵn: <code>{mid}</code> ID danh mục, <code>{slug}</code> Viết tắt danh mục, <code>{directory}</code> Chuyên mục')
            . '<br />' . _t('Vui lòng bao gồm ít nhất một trong các tham số trên trong đường dẫn.'));
        $categoryPattern->input->setAttribute('class', 'mono w-60');
        $form->addInput($categoryPattern->addRule(array($this, 'checkCategoryPattern'), _t('Đường dẫn danh mục không chứa {mid} hoặc {slug} ')));

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit('submit', NULL, _t('Lưu các thiết lập'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * 执行更新动作
     *
     * @access public
     * @return void
     */
    public function updatePermalinkSettings()
    {
        /** 验证格式 */
        if ($this->form()->validate()) {
            Typecho_Cookie::set('__typecho_form_item_postPattern', $this->request->customPattern);
            $this->response->goBack();
        }

        $patternValid = $this->checkRule($this->request->postPattern);

        /** 解析url pattern */
        if ('custom' == $this->request->postPattern) {
            $this->request->postPattern = '/' . ltrim($this->encodeRule($this->request->customPattern), '/');
        }

        $settings = defined('__TYPECHO_REWRITE__') ? array() : $this->request->from('rewrite');
        if (isset($this->request->postPattern) && isset($this->request->pagePattern)) {
            $routingTable = $this->options->routingTable;
            $routingTable['post']['url'] = $this->request->postPattern;
            $routingTable['page']['url'] = '/' . ltrim($this->encodeRule($this->request->pagePattern), '/');
            $routingTable['category']['url'] = '/' . ltrim($this->encodeRule($this->request->categoryPattern), '/');
            $routingTable['category_page']['url'] = rtrim($routingTable['category']['url'], '/') . '/[page:digital]/';

            if (isset($routingTable[0])) {
                unset($routingTable[0]);
            }

            $settings['routingTable'] = serialize($routingTable);
        }

        foreach ($settings as $name => $value) {
            $this->update(array('value' => $value), $this->db->sql()->where('name = ?', $name));
        }

        if ($patternValid) {
            $this->widget('Widget_Notice')->set(_t("Các cài đặt đã được lưu"), 'success');
        } else {
            $this->widget('Widget_Notice')->set(_t("Liên kết tùy chỉnh xung đột với các quy tắc hiện có! Nó có thể ảnh hưởng đến hiệu quả giải quyết. Bạn nên phân phối lại quy tắc."), 'notice');
        }
        $this->response->goBack();
    }

    /**
     * 绑定动作
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->isPost())->updatePermalinkSettings();
        $this->response->redirect($this->options->adminUrl);
    }
}
