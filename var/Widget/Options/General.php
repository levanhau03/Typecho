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
class Widget_Options_General extends Widget_Abstract_Options implements Widget_Interface_Do
{
    /**
     * 获取语言列表
     * 
     * @access private
     * @return array
     */
    public static function getLangs()
    {
        $dir = defined('__TYPECHO_LANG_DIR__') ? __TYPECHO_LANG_DIR__ : __TYPECHO_ROOT_DIR__ . '/usr/langs';
        $files = glob($dir . '/*.mo');
        $langs = array('zh_CN' => '简体中文');

        if (!empty($files)) {
            foreach ($files as $file) {
                $getText = new Typecho_I18n_GetText($file, false);
                list ($name) = explode('.', basename($file));
                $title = $getText->translate('lang', $count);
                $langs[$name] = $count > -1 ? $title : $name;
            }
            
            ksort($langs);
        }

        return $langs;
    }

    /**
     * 检查是否在语言列表中 
     * 
     * @param mixed $lang 
     * @access public
     * @return bool
     */
    public function checkLang($lang)
    {
        $langs = self::getLangs();
        return isset($langs[$lang]);
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
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/action/options-general'),
        Typecho_Widget_Helper_Form::POST_METHOD);

        /** 站点名称 */
        $title = new Typecho_Widget_Helper_Form_Element_Text('title', NULL, $this->options->title, _t('Tên trang web'), _t('Tên của trang web sẽ được hiển thị trong tiêu đề của trang web.'));
        $title->input->setAttribute('class', 'w-100');
        $form->addInput($title->addRule('required', _t('Vui lòng điền vào tên trang web'))
            ->addRule('xssCheck', _t('Vui lòng không sử dụng các ký tự đặc biệt trong tên trang web')));

        /** 站点地址 */
        if (!defined('__TYPECHO_SITE_URL__')) {
            $siteUrl = new Typecho_Widget_Helper_Form_Element_Text('siteUrl', NULL, $this->options->originalSiteUrl, _t('Địa chỉ trang web'), _t('Địa chỉ trang web chủ yếu được sử dụng để tạo liên kết vĩnh viễn đến nội dung.') . ($this->options->originalSiteUrl == $this->options->rootUrl ? 
                    '' : '</p><p class="message notice mono">' . _t('Địa chỉ hiện tại <strong>%s</strong> không phù hợp với cài đặt ở trên',
                    $this->options->rootUrl)));
            $siteUrl->input->setAttribute('class', 'w-100 mono');
            $form->addInput($siteUrl->addRule('required', _t('Vui lòng điền vào địa chỉ trang web'))
                ->addRule('url', _t('Vui lòng điền vào một địa chỉ URL hợp pháp')));
        }

        /** 站点描述 */
        $description = new Typecho_Widget_Helper_Form_Element_Text('description', NULL, $this->options->description, _t('Mô tả trang web'), _t('Mô tả trang web sẽ được hiển thị ở phần đầu của mã trang web.'));
        $form->addInput($description->addRule('xssCheck', _t('Vui lòng không sử dụng các ký tự đặc biệt trong mô tả trang web')));

        /** 关键词 */
        $keywords = new Typecho_Widget_Helper_Form_Element_Text('keywords', NULL, $this->options->keywords, _t('Từ khóa'), _t('Vui lòng phân tách nhiều từ khóa bằng dấu phẩy.'));
        $form->addInput($keywords->addRule('xssCheck', _t('Vui lòng không sử dụng các ký tự đặc biệt trong từ khóa')));

        /** 注册 */
        $allowRegister = new Typecho_Widget_Helper_Form_Element_Radio('allowRegister', array('0' => _t('Không cho phép'), '1' => _t('Cho phép')), $this->options->allowRegister, _t('Có cho phép đăng ký hay không'),
        _t('Cho phép khách truy cập đăng ký vào trang web của bạn, người dùng đã đăng ký mặc định không có bất kỳ quyền ghi nào.'));
        $form->addInput($allowRegister);
 
        /** XMLRPC */
        $allowXmlRpc = new Typecho_Widget_Helper_Form_Element_Radio('allowXmlRpc', array('0' => _t('Đóng'), '1' => _t('Chỉ đóng giao diện Pingback'), '2' => _t('Mở')), $this->options->allowXmlRpc, _t('Giao diện XMLRPC'));
        $form->addInput($allowXmlRpc);

        /** 语言项 */
        // hack 语言扫描
        _t('lang');

        $langs = self::getLangs();

        if (count($langs) > 1) {
            $lang = new Typecho_Widget_Helper_Form_Element_Select('lang', $langs, $this->options->lang, _t('Ngôn ngữ'));
            $form->addInput($lang->addRule(array($this, 'checkLang'), _t('Gói ngôn ngữ đã chọn không tồn tại')));
        }

        /** 时区 */
        $timezoneList = array(
            "0"         => _t('格林威治(子午线)标准时间 (GMT)'),
            "3600"      => _t('中欧标准时间 阿姆斯特丹,荷兰,法国 (GMT +1)'),
            "7200"      => _t('东欧标准时间 布加勒斯特,塞浦路斯,希腊 (GMT +2)'),
            "10800"     => _t('莫斯科时间 伊拉克,埃塞俄比亚,马达加斯加 (GMT +3)'),
            "14400"     => _t('第比利斯时间 阿曼,毛里塔尼亚,留尼汪岛 (GMT +4)'),
            "18000"     => _t('新德里时间 巴基斯坦,马尔代夫 (GMT +5)'),
            "21600"     => _t('科伦坡时间 孟加拉 (GMT +6)'),
            "25200"     => _t('曼谷雅加达 柬埔寨,苏门答腊,老挝 (GMT +7)'),
            "28800"     => _t('北京时间 香港,新加坡,越南 (GMT +8)'),
            "32400"     => _t('东京平壤时间 西伊里安,摩鹿加群岛 (GMT +9)'),
            "36000"     => _t('悉尼关岛时间 塔斯马尼亚岛,新几内亚 (GMT +10)'),
            "39600"     => _t('所罗门群岛 库页岛 (GMT +11)'),
            "43200"     => _t('惠灵顿时间 新西兰,斐济群岛 (GMT +12)'),
            "-3600"     => _t('佛德尔群岛 亚速尔群岛,葡属几内亚 (GMT -1)'),
            "-7200"     => _t('大西洋中部时间 格陵兰 (GMT -2)'),
            "-10800"    => _t('布宜诺斯艾利斯 乌拉圭,法属圭亚那 (GMT -3)'),
            "-14400"    => _t('智利巴西 委内瑞拉,玻利维亚 (GMT -4)'),
            "-18000"    => _t('纽约渥太华 古巴,哥伦比亚,牙买加 (GMT -5)'),
            "-21600"    => _t('墨西哥城时间 洪都拉斯,危地马拉,哥斯达黎加 (GMT -6)'),
            "-25200"    => _t('美国丹佛时间 (GMT -7)'),
            "-28800"    => _t('美国旧金山时间 (GMT -8)'),
            "-32400"    => _t('阿拉斯加时间 (GMT -9)'),
            "-36000"    => _t('夏威夷群岛 (GMT -10)'),
            "-39600"    => _t('东萨摩亚群岛 (GMT -11)'),
            "-43200"    => _t('艾尼威托克岛 (GMT -12)')
        );

        $timezone = new Typecho_Widget_Helper_Form_Element_Select('timezone', $timezoneList, $this->options->timezone, _t('Múi giờ'));
        $form->addInput($timezone);

        /** 扩展名 */
        $attachmentTypesOptionsResult = (NULL != trim($this->options->attachmentTypes)) ? 
        array_map('trim', explode(',', $this->options->attachmentTypes)) : array();
        $attachmentTypesOptionsValue = array();
        
        if (in_array('@image@', $attachmentTypesOptionsResult)) {
            $attachmentTypesOptionsValue[] = '@image@';
        }
        
        if (in_array('@media@', $attachmentTypesOptionsResult)) {
            $attachmentTypesOptionsValue[] = '@media@';
        }
        
        if (in_array('@doc@', $attachmentTypesOptionsResult)) {
            $attachmentTypesOptionsValue[] = '@doc@';
        }
        
        $attachmentTypesOther = array_diff($attachmentTypesOptionsResult, $attachmentTypesOptionsValue);
        $attachmentTypesOtherValue = '';
        if (!empty($attachmentTypesOther)) {
            $attachmentTypesOptionsValue[] = '@other@';
            $attachmentTypesOtherValue = implode(',', $attachmentTypesOther);
        }
        
        $attachmentTypesOptions = array(
            '@image@'    =>  _t('Tệp hình ảnh') . ' <code>(gif jpg jpeg png tiff bmp)</code>',
            '@media@'    =>  _t('Tệp đa phương tiện') . ' <code>(mp3 wmv wma rmvb rm avi flv)</code>',
            '@doc@'      =>  _t('Các tệp lưu trữ phổ biến') . ' <code>(txt doc docx xls xlsx ppt pptx zip rar pdf)</code>',
            '@other@'    =>  _t('Định dạng khác %s', ' <input type="text" class="w-50 text-s mono" name="attachmentTypesOther" value="' . htmlspecialchars($attachmentTypesOtherValue) . '" />'),
        );
        
        $attachmentTypes = new Typecho_Widget_Helper_Form_Element_Checkbox('attachmentTypes', $attachmentTypesOptions,
        $attachmentTypesOptionsValue, _t('Các loại tệp được phép tải lên'), _t('Phân tách các tên hậu tố bằng dấu phẩy, ví dụ: %s', '<code>cpp, h, mak</code>'));
        $form->addInput($attachmentTypes->multiMode());

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit('submit', NULL, _t('Lưu các thiết lập'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * 过滤掉可执行的后缀名
     *
     * @param string $ext
     * @return boolean
     */
    public function removeShell($ext)
    {
        return !preg_match("/^(php|php4|php5|sh|asp|jsp|rb|py|pl|dll|exe|bat)$/i", $ext);
    }

    /**
     * 执行更新动作
     *
     * @access public
     * @return void
     */
    public function updateGeneralSettings()
    {
        /** 验证格式 */
        if ($this->form()->validate()) {
            $this->response->goBack();
        }

        $settings = $this->request->from('title','description', 'keywords', 'allowRegister', 'allowXmlRpc', 'lang', 'timezone');
        $settings['attachmentTypes'] = $this->request->getArray('attachmentTypes');

        if (!defined('__TYPECHO_SITE_URL__')) {
            $settings['siteUrl'] = rtrim($this->request->siteUrl, '/');
        }

        $attachmentTypes = array();
        if ($this->isEnableByCheckbox($settings['attachmentTypes'], '@image@')) {
            $attachmentTypes[] = '@image@';
        }
        
        if ($this->isEnableByCheckbox($settings['attachmentTypes'], '@media@')) {
            $attachmentTypes[] = '@media@';
        }
        
        if ($this->isEnableByCheckbox($settings['attachmentTypes'], '@doc@')) {
            $attachmentTypes[] = '@doc@';
        }
        
        $attachmentTypesOther = $this->request->filter('trim', 'strtolower')->attachmentTypesOther;
        if ($this->isEnableByCheckbox($settings['attachmentTypes'], '@other@') && !empty($attachmentTypesOther)) {
            $types = implode(',', array_filter(array_map('trim',
                explode(',', $attachmentTypesOther)), array($this, 'removeShell')));

            if (!empty($types)) {
                $attachmentTypes[] = $types;
            }
        }
        
        $settings['attachmentTypes'] = implode(',', $attachmentTypes);
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
        $this->on($this->request->isPost())->updateGeneralSettings();
        $this->response->redirect($this->options->adminUrl);
    }
}
