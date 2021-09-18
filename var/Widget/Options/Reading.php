<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 文章阅读设置
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 文章阅读设置组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Options_Reading extends Widget_Options_Permalink
{
    /**
     * 输出表单结构
     *
     * @access public
     * @return Typecho_Widget_Helper_Form
     */
    public function form()
    {
        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/action/options-reading'),
            Typecho_Widget_Helper_Form::POST_METHOD);

        /** 文章日期格式 */
        $postDateFormat = new Typecho_Widget_Helper_Form_Element_Text('postDateFormat', NULL, $this->options->postDateFormat,
        _t('Định dạng ngày của bài viết'), _t('Định dạng này được sử dụng để chỉ định định dạng hiển thị mặc định của ngày được hiển thị trong kho lưu trữ bài viết.') . '<br />'
            . _t('Trong một số chủ đề, định dạng này có thể không có hiệu lực, vì tác giả chủ đề có thể tùy chỉnh định dạng ngày.') . '<br />'
            . _t('Vui lòng tham khảo <a href="http://www.php.net/manual/zh/function.date.php">định dạng ngày trong PHP</a>.'));
        $postDateFormat->input->setAttribute('class', 'w-40 mono');
        $form->addInput($postDateFormat->addRule('xssCheck', _t('Vui lòng không sử dụng các ký tự đặc biệt trong định dạng ngày tháng')));

        //首页显示
        $frontPageParts = explode(':', $this->options->frontPage);
        $frontPageType = $frontPageParts[0];
        $frontPageValue = count($frontPageParts) > 1 ? $frontPageParts[1] : '';

        $frontPageOptions = array(
            'recent'   =>  _t('Hiển thị các bài báo đã xuất bản mới nhất')
        );

        $frontPattern = '</label></span><span class="multiline front-archive%class%">'
            . '<input type="checkbox" id="frontArchive" name="frontArchive" value="1"'
            . ($this->options->frontArchive && 'recent' != $frontPageType ? ' checked' : '') .' />
<label for="frontArchive">' . _t('Đồng thời thay đổi đường dẫn của trang danh sách bài viết thành %s',
            '<input type="text" name="archivePattern" class="w-20 mono" value="' 
            . htmlspecialchars($this->decodeRule($this->options->routingTable['archive']['url'])) . '" />') 
            . '</label>';

        // 页面列表
        $pages = $this->db->fetchAll($this->db->select('cid', 'title')
        ->from('table.contents')->where('type = ?', 'page')
        ->where('status = ?', 'publish')->where('created < ?', $this->options->time));
        
        if (!empty($pages)) {
            $pagesSelect = '<select name="frontPagePage" id="frontPage-frontPagePage">';
            foreach ($pages as $page) {
                $selected = '';
                if ('page' == $frontPageType && $page['cid'] == $frontPageValue) {
                    $selected = ' selected="true"';
                }

                $pagesSelect .= '<option value="' . $page['cid'] . '"' . $selected
                . '>' . $page['title'] . '</option>';
            }
            $pagesSelect .= '</select>';
            $frontPageOptions['page'] = _t('Sử dụng trang %s làm trang chủ', '</label>' . $pagesSelect . '<label for="frontPage-frontPagePage">');
            $selectedFrontPageType = 'page';
        }

        // 自定义文件列表
        $files = glob($this->options->themeFile($this->options->theme, '*.php'));
        $filesSelect = '';

        foreach ($files as $file) {
            $info = Typecho_Plugin::parseInfo($file);
            $file = basename($file);

            if ('index.php' != $file && 'index' == $info['title']) {
                $selected = '';
                if ('file' == $frontPageType && $file == $frontPageValue) {
                    $selected = ' selected="true"';
                }

                $filesSelect .= '<option value="' . $file . '"' . $selected
                . '>' . $file . '</option>';
            }
        }

        if (!empty($filesSelect)) {
            $frontPageOptions['file'] = _t('Gọi trực tiếp tệp mẫu %s',
             '</label><select name="frontPageFile" id="frontPage-frontPageFile">'
            . $filesSelect . '</select><label for="frontPage-frontPageFile">');
            $selectedFrontPageType = 'file';
        }
        
        if (isset($frontPageOptions[$frontPageType]) && 'recent' != $frontPageType && isset($selectedFrontPageType)) {
            $selectedFrontPageType = $frontPageType;
            $frontPattern = str_replace('%class%', '', $frontPattern);
        }

        if (isset($selectedFrontPageType)) {
            $frontPattern = str_replace('%class%', ' hidden', $frontPattern);
            $frontPageOptions[$selectedFrontPageType] .= $frontPattern;
        }

        $frontPage = new Typecho_Widget_Helper_Form_Element_Radio('frontPage', $frontPageOptions,
        $frontPageType, _t('Trang chủ'));
        $form->addInput($frontPage->multiMode());

        /** 文章列表数目 */
        $postsListSize = new Typecho_Widget_Helper_Form_Element_Text('postsListSize', NULL, $this->options->postsListSize,
        _t('Số lượng danh sách bài viết'), _t('Con số này được sử dụng để chỉ định số lượng danh sách bài viết được hiển thị trong thanh bên.'));
        $postsListSize->input->setAttribute('class', 'w-20');
        $form->addInput($postsListSize->addRule('isInteger', _t('Vui lòng điền vào một số')));

        /** 每页文章数目 */
        $pageSize = new Typecho_Widget_Helper_Form_Element_Text('pageSize', NULL, $this->options->pageSize,
        _t('Số lượng bài viết trên mỗi trang'), _t('Con số này được sử dụng để chỉ định số lượng bài viết hiển thị trên mỗi trang khi bài viết lưu trữ xuất ra.'));
        $pageSize->input->setAttribute('class', 'w-20');
        $form->addInput($pageSize->addRule('isInteger', _t('Vui lòng điền vào một số')));

        /** FEED全文输出 */
        $feedFullText = new Typecho_Widget_Helper_Form_Element_Radio('feedFullText', array('0' => _t('Chỉ tóm tắt đầu ra'), '1' => _t('Toàn bộ văn bản đầu ra')),
        $this->options->feedFullText, _t('Tổng hợp đầu ra văn bản đầy đủ'), _t('Nếu bạn không muốn xuất toàn bộ văn bản của bài viết trong tập hợp, vui lòng sử dụng tùy chọn chỉ tóm tắt đầu ra.') . '<br />'
            . _t('Văn bản của phần tóm tắt phụ thuộc vào vị trí bạn sử dụng dấu phân tách trong bài viết.'));
        $form->addInput($feedFullText);

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
    public function updateReadingSettings()
    {
        /** 验证格式  */
        if ($this->form()->validate()) {
            $this->response->goBack();
        }

        $settings = $this->request->from('postDateFormat', 'frontPage', 'frontArchive', 'pageSize', 'postsListSize', 'feedFullText');

        if ('page' == $settings['frontPage'] && isset($this->request->frontPagePage) &&
        $this->db->fetchRow($this->db->select('cid')
        ->from('table.contents')->where('type = ?', 'page')
        ->where('status = ?', 'publish')->where('created < ?', $this->options->time)
        ->where('cid = ?', $pageId = intval($this->request->frontPagePage)))) {

            $settings['frontPage'] = 'page:' . $pageId;

        } else if ('file' == $settings['frontPage'] && isset($this->request->frontPageFile) &&
        file_exists(__TYPECHO_ROOT_DIR__ . '/' . __TYPECHO_THEME_DIR__ . '/' . $this->options->theme . '/' .
        ($file = trim($this->request->frontPageFile, " ./\\")))) {

            $settings['frontPage'] = 'file:' . $file;

        } else {
            $settings['frontPage'] = 'recent';
        }

        if ('recent' != $settings['frontPage']) {
            $settings['frontArchive'] = empty($settings['frontArchive']) ? 0 : 1;
            if ($settings['frontArchive']) {
                $routingTable = $this->options->routingTable;
                $routingTable['archive']['url'] = '/' . ltrim($this->encodeRule($this->request->archivePattern), '/');
                $routingTable['archive_page']['url'] = rtrim($routingTable['archive']['url'], '/') . '/page/[page:digital]/';

                if (isset($routingTable[0])) {
                    unset($routingTable[0]);
                }

                $settings['routingTable'] = serialize($routingTable);
            }
        } else {
            $settings['frontArchive'] = 0;
        }

        foreach ($settings as $name => $value) {
            $this->update(array('value' => $value), $this->db->sql()->where('name = ?', $name));
        }

        $this->widget('Widget_Notice')->set(_t("Các cài đặt đã được lưu"), 'success');
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
        $this->on($this->request->isPost())->updateReadingSettings();
        $this->response->redirect($this->options->adminUrl);
    }
}
