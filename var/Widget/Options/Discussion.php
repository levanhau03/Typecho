<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 评论设置
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 评论设置组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Options_Discussion extends Widget_Abstract_Options implements Widget_Interface_Do
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
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/action/options-discussion'),
        Typecho_Widget_Helper_Form::POST_METHOD);

        /** 评论日期格式 */
        $commentDateFormat = new Typecho_Widget_Helper_Form_Element_Text('commentDateFormat', NULL, $this->options->commentDateFormat,
        _t('Định dạng ngày bình luận'), _t('Đây là định dạng mặc định. Khi bạn gọi phương thức hiển thị ngày chú thích trong mẫu, nếu định dạng ngày không được chỉ định, nó sẽ được xuất ở định dạng này.') . '<br />'
            . _t('Để biết chi tiết, vui lòng tham khảo <a href="http://www.php.net/manual/zh/function.date.php">cách viết định dạng ngày tháng trong PHP</a>.'));
        $commentDateFormat->input->setAttribute('class', 'w-40 mono');
        $form->addInput($commentDateFormat);

        /** 评论列表数目 */
        $commentsListSize = new Typecho_Widget_Helper_Form_Element_Text('commentsListSize', NULL, $this->options->commentsListSize,
        _t('Số lượng danh sách bình luận'), _t('Con số này được sử dụng để chỉ định số lượng danh sách bình luận được hiển thị trong thanh bên.'));
        $commentsListSize->input->setAttribute('class', 'w-20');
        $form->addInput($commentsListSize->addRule('isInteger', _t('Vui lòng điền vào một số')));

        $commentsShowOptions = array(
            'commentsShowCommentOnly'   =>  _t('Chỉ hiển thị nhận xét, không hiển thị Pingback và Trackback'),
            'commentsMarkdown'      =>  _t('Sử dụng cú pháp Markdown trong nhận xét'),
            'commentsShowUrl'       =>  _t('Tự động được thêm liên kết vào khi tên của người nhận xét được hiển thị'),
            'commentsUrlNofollow'   =>  _t('Sử dụng thuộc tính <a href="http://en.wikipedia.org/wiki/Nofollow">nofollow</a> cho liên kết'),
            'commentsAvatar'        =>  _t('Bật dịch vụ hình đại diện <a href="http://gravatar.com">Gravatar</a>, xếp hạng hiển thị cao nhất là %s',
            '</label><select id="commentsShow-commentsAvatarRating" name="commentsAvatarRating">
            <option value="G"' . ('G' == $this->options->commentsAvatarRating ? ' selected="true"' : '') . '>G - Bình thường</option>
            <option value="PG"' . ('PG' == $this->options->commentsAvatarRating ? ' selected="true"' : '') . '>PG - Trên 13 tuổi</option>
            <option value="R"' . ('R' == $this->options->commentsAvatarRating ? ' selected="true"' : '') . '>R - Người lớn trên 17 tuổi</option>
            <option value="X"' . ('X' == $this->options->commentsAvatarRating ? ' selected="true"' : '') . '>X - Mức độ hạn chế</option></select>
            <label for="commentsShow-commentsAvatarRating">'),
            'commentsPageBreak'     =>  _t('Phân trang được bật %s nhận xét được hiển thị trên mỗi trang, %s được hiển thị theo mặc định khi được liệt kê',
            '</label><input type="text" value="' . $this->options->commentsPageSize
            . '" class="text num text-s" id="commentsShow-commentsPageSize" name="commentsPageSize" /><label for="commentsShow-commentsPageSize">',
            '</label><select id="commentsShow-commentsPageDisplay" name="commentsPageDisplay">
            <option value="first"' . ('first' == $this->options->commentsPageDisplay ? ' selected="true"' : '') . '>' . _t('Trang đầu tiên') . '</option>
            <option value="last"' . ('last' == $this->options->commentsPageDisplay ? ' selected="true"' : '') . '>' . _t('Trang cuối cùng') . '</option></select>'
            . '<label for="commentsShow-commentsPageDisplay">'),
            'commentsThreaded'      =>  _t('Bật tính năng trả lời nhận xét, sử dụng %s làm câu trả lời nhiều nhất cho mỗi nhận xét',
            '</label><input name="commentsMaxNestingLevels" type="text" class="text num text-s" value="' . $this->options->commentsMaxNestingLevels . '" id="commentsShow-commentsMaxNestingLevels" />
            <label for="commentsShow-commentsMaxNestingLevels">') . '</label></span><span class="multiline">'
            . _t('Hiển thị bình luận của %s lên phía trước', '<select id="commentsShow-commentsOrder" name="commentsOrder">
            <option value="DESC"' . ('DESC' == $this->options->commentsOrder ? ' selected="true"' : '') . '>' . _t('Mới hơn') . '</option>
            <option value="ASC"' . ('ASC' == $this->options->commentsOrder ? ' selected="true"' : '') . '>' . _t('Lớn hơn') . '</option></select><label for="commentsShow-commentsOrder">')
        );

        $commentsShowOptionsValue = array();
        if ($this->options->commentsShowCommentOnly) {
            $commentsShowOptionsValue[] = 'commentsShowCommentOnly';
        }

        if ($this->options->commentsMarkdown) {
            $commentsShowOptionsValue[] = 'commentsMarkdown';
        }

        if ($this->options->commentsShowUrl) {
            $commentsShowOptionsValue[] = 'commentsShowUrl';
        }

        if ($this->options->commentsUrlNofollow) {
            $commentsShowOptionsValue[] = 'commentsUrlNofollow';
        }
        
        if ($this->options->commentsAvatar) {
            $commentsShowOptionsValue[] = 'commentsAvatar';
        }

        if ($this->options->commentsPageBreak) {
            $commentsShowOptionsValue[] = 'commentsPageBreak';
        }

        if ($this->options->commentsThreaded) {
            $commentsShowOptionsValue[] = 'commentsThreaded';
        }

        $commentsShow = new Typecho_Widget_Helper_Form_Element_Checkbox('commentsShow', $commentsShowOptions,
        $commentsShowOptionsValue, _t('Hiển thị bình luận'));
        $form->addInput($commentsShow->multiMode());

        /** 评论提交 */
        $commentsPostOptions = array(
            'commentsRequireModeration'     =>  _t('Tất cả các bình luận phải được xem xét'),
            'commentsWhitelist'     =>  _t('Người đánh giá phải đã vượt qua bài đánh giá trước khi đánh giá'),
            'commentsRequireMail'           =>  _t('Email phải được điền vào'),
            'commentsRequireURL'            =>  _t('URL phải được điền vào'),
            'commentsCheckReferer'          =>  _t('Kiểm tra xem URL của trang nguồn nhận xét có phù hợp với liên kết bài viết hay không'),
            'commentsAntiSpam'              =>  _t('Bật tính năng bảo vệ chống thư rác'),
            'commentsAutoClose'             =>  _t('Nhận xét tự động đóng %s ngày sau khi bài viết được xuất bản',
            '</label><input name="commentsPostTimeout" type="text" class="text num text-s" value="' . intval($this->options->commentsPostTimeout / (24 * 3600)) . '" id="commentsPost-commentsPostTimeout" />
            <label for="commentsPost-commentsPostTimeout">'),
            'commentsPostIntervalEnable'    =>  _t('Khoảng thời gian để đăng nhận xét từ cùng một IP được giới hạn trong %s phút',
            '</label><input name="commentsPostInterval" type="text" class="text num text-s" value="' . round($this->options->commentsPostInterval / (60), 1) . '" id="commentsPost-commentsPostInterval" />
            <label for="commentsPost-commentsPostInterval">')
        );

        $commentsPostOptionsValue = array();
        if ($this->options->commentsRequireModeration) {
            $commentsPostOptionsValue[] = 'commentsRequireModeration';
        }

        if ($this->options->commentsWhitelist) {
            $commentsPostOptionsValue[] = 'commentsWhitelist';
        }

        if ($this->options->commentsRequireMail) {
            $commentsPostOptionsValue[] = 'commentsRequireMail';
        }

        if ($this->options->commentsRequireURL) {
            $commentsPostOptionsValue[] = 'commentsRequireURL';
        }

        if ($this->options->commentsCheckReferer) {
            $commentsPostOptionsValue[] = 'commentsCheckReferer';
        }

        if ($this->options->commentsAntiSpam) {
            $commentsPostOptionsValue[] = 'commentsAntiSpam';
        }

        if ($this->options->commentsAutoClose) {
            $commentsPostOptionsValue[] = 'commentsAutoClose';
        }

        if ($this->options->commentsPostIntervalEnable) {
            $commentsPostOptionsValue[] = 'commentsPostIntervalEnable';
        }

        $commentsPost = new Typecho_Widget_Helper_Form_Element_Checkbox('commentsPost', $commentsPostOptions,
        $commentsPostOptionsValue, _t('Gửi bình luận'));
        $form->addInput($commentsPost->multiMode());

        /** 允许使用的HTML标签和属性 */
        $commentsHTMLTagAllowed = new Typecho_Widget_Helper_Form_Element_Textarea('commentsHTMLTagAllowed', NULL,
        $this->options->commentsHTMLTagAllowed,
        _t('Các thẻ và thuộc tính HTML được phép'), _t('Bình luận của người dùng mặc định không cho phép điền bất kỳ thẻ HTML nào, bạn có thể điền các thẻ HTML được phép tại đây.') . '<br />'
            . _t('Ví dụ: %s', ': <code>&lt;a href=&quot;&quot;&gt; &lt;img src=&quot;&quot;&gt; &lt;blockquote&gt;</code>'));
        $commentsHTMLTagAllowed->input->setAttribute('class', 'mono');
        $form->addInput($commentsHTMLTagAllowed);

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
    public function updateDiscussionSettings()
    {
        /** 验证格式 */
        if ($this->form()->validate()) {
            $this->response->goBack();
        }

        $settings = $this->request->from('commentDateFormat', 'commentsListSize', 'commentsPageSize', 'commentsPageDisplay', 'commentsAvatar',
                'commentsOrder', 'commentsMaxNestingLevels', 'commentsUrlNofollow', 'commentsPostTimeout', 'commentsUniqueIpInterval', 'commentsWhitelist', 'commentsRequireMail', 'commentsAvatarRating',
                'commentsPostTimeout', 'commentsPostInterval', 'commentsRequireModeration', 'commentsRequireURL', 'commentsHTMLTagAllowed', 'commentsStopWords', 'commentsIpBlackList');
        $settings['commentsShow'] = $this->request->getArray('commentsShow');
        $settings['commentsPost'] = $this->request->getArray('commentsPost');

        $settings['commentsShowCommentOnly'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsShowCommentOnly');
        $settings['commentsMarkdown'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsMarkdown');
        $settings['commentsShowUrl'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsShowUrl');
        $settings['commentsUrlNofollow'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsUrlNofollow');
        $settings['commentsAvatar'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsAvatar');
        $settings['commentsPageBreak'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsPageBreak');
        $settings['commentsThreaded'] = $this->isEnableByCheckbox($settings['commentsShow'], 'commentsThreaded');

        $settings['commentsPageSize'] = intval($settings['commentsPageSize']);
        $settings['commentsMaxNestingLevels'] = min(7, max(2, intval($settings['commentsMaxNestingLevels'])));
        $settings['commentsPageDisplay'] = ('first' == $settings['commentsPageDisplay']) ? 'first' : 'last';
        $settings['commentsOrder'] = ('DESC' == $settings['commentsOrder']) ? 'DESC' : 'ASC';
        $settings['commentsAvatarRating'] = in_array($settings['commentsAvatarRating'], array('G', 'PG', 'R', 'X'))
            ? $settings['commentsAvatarRating'] : 'G';

        $settings['commentsRequireModeration'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsRequireModeration');
        $settings['commentsWhitelist'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsWhitelist');
        $settings['commentsRequireMail'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsRequireMail');
        $settings['commentsRequireURL'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsRequireURL');
        $settings['commentsCheckReferer'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsCheckReferer');
        $settings['commentsAntiSpam'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsAntiSpam');
        $settings['commentsAutoClose'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsAutoClose');
        $settings['commentsPostIntervalEnable'] = $this->isEnableByCheckbox($settings['commentsPost'], 'commentsPostIntervalEnable');

        $settings['commentsPostTimeout'] = intval($settings['commentsPostTimeout']) * 24 * 3600;
        $settings['commentsPostInterval'] = round($settings['commentsPostInterval'], 1) * 60;

        unset($settings['commentsShow']);
        unset($settings['commentsPost']);

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
        $this->on($this->request->isPost())->updateDiscussionSettings();
        $this->response->redirect($this->options->adminUrl);
    }
}
