<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 编辑用户
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 编辑用户组件
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Users_Edit extends Widget_Abstract_Users implements Widget_Interface_Do
{
    /**
     * 获取页面偏移的URL Query
     *
     * @access protected
     * @param integer $uid 用户id
     * @return string
     */
    protected function getPageOffsetQuery($uid)
    {
        return 'page=' . $this->getPageOffset('uid', $uid);
    }

    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        /** 管理员以上权限 */
        $this->user->pass('administrator');

        /** 更新模式 */
        if (($this->request->uid && 'delete' != $this->request->do) || 'update' == $this->request->do) {
            $this->db->fetchRow($this->select()
            ->where('uid = ?', $this->request->uid)->limit(1), array($this, 'push'));

            if (!$this->have()) {
                throw new Typecho_Widget_Exception(_t('Người dùng không tồn tại'), 404);
            }
        }
    }

    /**
     * 获取菜单标题
     *
     * @access public
     * @return string
     */
    public function getMenuTitle()
    {
        return _t('Chỉnh sửa người dùng %s', $this->name);
    }

    /**
     * 判断用户是否存在
     *
     * @access public
     * @param integer $uid 用户主键
     * @return boolean
     */
    public function userExists($uid)
    {
        $user = $this->db->fetchRow($this->db->select()
        ->from('table.users')
        ->where('uid = ?', $uid)->limit(1));

        return !empty($user);
    }

    /**
     * 生成表单
     *
     * @access public
     * @param string $action 表单动作
     * @return Typecho_Widget_Helper_Form
     */
    public function form($action = NULL)
    {
        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/action/users-edit'),
        Typecho_Widget_Helper_Form::POST_METHOD);

        /** 用户名称 */
        $name = new Typecho_Widget_Helper_Form_Element_Text('name', NULL, NULL, _t('Tên tài khoản *'), _t('Tên người dùng này sẽ là tên được sử dụng khi người dùng đăng nhập.')
            . '<br />' . _t('Vui lòng không trùng lặp tên người dùng hiện có trong hệ thống.'));
        $form->addInput($name);

        /** 电子邮箱地址 */
        $mail = new Typecho_Widget_Helper_Form_Element_Text('mail', NULL, NULL, _t('Địa chỉ email *'), _t('Địa chỉ email sẽ là phương thức liên hệ chính của người dùng này.')
            . '<br />' . _t('Vui lòng không trùng lặp địa chỉ email hiện có trong hệ thống.'));
        $form->addInput($mail);

        /** 用户昵称 */
        $screenName = new Typecho_Widget_Helper_Form_Element_Text('screenName', NULL, NULL, _t('Biệt hiệu của người dùng'), _t('Biệt hiệu của người dùng có thể khác với tên người dùng, được sử dụng để hiển thị nền trước.')
            . '<br />' . _t('Nếu bạn để trống, tên người dùng sẽ được sử dụng theo mặc định.'));
        $form->addInput($screenName);

        /** 用户密码 */
        $password = new Typecho_Widget_Helper_Form_Element_Password('password', NULL, NULL, _t('Mật khẩu người dùng'), _t('Gán mật khẩu cho người dùng này.')
            . '<br />' . _t('Nên sử dụng kiểu hỗn hợp các ký tự đặc biệt, chữ cái và số để tăng tính bảo mật cho hệ thống.'));
        $password->input->setAttribute('class', 'w-60');
        $form->addInput($password);

        /** 用户密码确认 */
        $confirm = new Typecho_Widget_Helper_Form_Element_Password('confirm', NULL, NULL, _t('Xác nhận mật khẩu người dùng'), _t('Vui lòng xác nhận mật khẩu của bạn, mật khẩu này phù hợp với mật khẩu đã nhập ở trên.'));
        $confirm->input->setAttribute('class', 'w-60');
        $form->addInput($confirm);

        /** 个人主页地址 */
        $url = new Typecho_Widget_Helper_Form_Element_Text('url', NULL, NULL, _t('Địa chỉ trang chủ cá nhân'), _t('Địa chỉ trang chủ cá nhân của người dùng này, vui lòng bắt đầu bằng <code>http://</code>.'));
        $form->addInput($url);

        /** 用户组 */
        $group =  new Typecho_Widget_Helper_Form_Element_Select('group', array('subscriber' => _t('Người theo dõi'),
                'contributor' => _t('Người đóng góp'), 'editor' => _t('Biên tập'), 'administrator' => _t('Quản lý')),
                NULL, _t('Nhóm người dùng'), _t('Các nhóm người dùng khác nhau có các quyền khác nhau.')
            . '<br />' . _t('Vui lòng <a href="http://docs.typecho.org/develop/acl">tham khảo tại đây</a>.'));
        $form->addInput($group);

        /** 用户动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
        $form->addInput($do);

        /** 用户主键 */
        $uid = new Typecho_Widget_Helper_Form_Element_Hidden('uid');
        $form->addInput($uid);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        if (NULL != $this->request->uid) {
            $submit->value(_t('Sửa người dùng'));
            $name->value($this->name);
            $screenName->value($this->screenName);
            $url->value($this->url);
            $mail->value($this->mail);
            $group->value($this->group);
            $do->value('update');
            $uid->value($this->uid);
            $_action = 'update';
        } else {
            $submit->value(_t('Thêm người dùng'));
            $do->value('insert');
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** 给表单增加规则 */
        if ('insert' == $action || 'update' == $action) {
            $screenName->addRule(array($this, 'screenNameExists'), _t('Biệt danh đã tồn tại'));
            $screenName->addRule('xssCheck', _t('Vui lòng không sử dụng các ký tự đặc biệt trong biệt hiệu của bạn'));
            $url->addRule('url', _t('Lỗi định dạng địa chỉ trang chủ cá nhân'));
            $mail->addRule('required', _t('Email phải được điền vào'));
            $mail->addRule(array($this, 'mailExists'), _t('Địa chỉ email đã tồn tại'));
            $mail->addRule('email', _t('Lỗi định dạng email'));
            $password->addRule('minLength', _t('Để đảm bảo bảo mật tài khoản, vui lòng nhập mật khẩu có ít nhất sáu chữ số'), 6);
            $confirm->addRule('confirm', _t('Hai mật khẩu đã nhập không nhất quán'), 'password');
        }

        if ('insert' == $action) {
            $name->addRule('required', _t('Tên người dùng phải được điền vào'));
            $name->addRule('xssCheck', _t('Vui lòng không sử dụng các ký tự đặc biệt trong tên người dùng'));
            $name->addRule(array($this, 'nameExists'), _t('Tên đăng kí đã được sử dụng'));
            $password->label(_t('Mật khẩu người dùng *'));
            $confirm->label(_t('Xác nhận mật khẩu người dùng *'));
            $password->addRule('required', _t('Mật khẩu phải được điền vào'));
        }

        if ('update' == $action) {
            $name->input->setAttribute('disabled', 'disabled');
            $uid->addRule('required', _t('Khóa chính của người dùng không tồn tại'));
            $uid->addRule(array($this, 'userExists'), _t('Người dùng không tồn tại'));
        }

        return $form;
    }

    /**
     * 增加用户
     *
     * @access public
     * @return void
     */
    public function insertUser()
    {
        if ($this->form('insert')->validate()) {
            $this->response->goBack();
        }

        $hasher = new PasswordHash(8, true);

        /** 取出数据 */
        $user = $this->request->from('name', 'mail', 'screenName', 'password', 'url', 'group');
        $user['screenName'] = empty($user['screenName']) ? $user['name'] : $user['screenName'];
        $user['password'] = $hasher->HashPassword($user['password']);
        $user['created'] = $this->options->time;

        /** 插入数据 */
        $user['uid'] = $this->insert($user);

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight('user-' . $user['uid']);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('Người dùng %s đã được thêm', $user['screenName']), 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('manage-users.php', $this->options->adminUrl));
    }

    /**
     * 更新用户
     *
     * @access public
     * @return void
     */
    public function updateUser()
    {
        if ($this->form('update')->validate()) {
            $this->response->goBack();
        }

        /** 取出数据 */
        $user = $this->request->from('mail', 'screenName', 'password', 'url', 'group');
        $user['screenName'] = empty($user['screenName']) ? $user['name'] : $user['screenName'];
        if (empty($user['password'])) {
            unset($user['password']);
        } else {
            $hasher = new PasswordHash(8, true);
            $user['password'] = $hasher->HashPassword($user['password']);
        }

        /** 更新数据 */
        $this->update($user, $this->db->sql()->where('uid = ?', $this->request->uid));

        /** 设置高亮 */
        $this->widget('Widget_Notice')->highlight('user-' . $this->request->uid);

        /** 提示信息 */
        $this->widget('Widget_Notice')->set(_t('Người dùng %s đã được cập nhật', $user['screenName']), 'success');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('manage-users.php?' .
        $this->getPageOffsetQuery($this->request->uid), $this->options->adminUrl));
    }

    /**
     * 删除用户
     *
     * @access public
     * @return void
     */
    public function deleteUser()
    {
        $users = $this->request->filter('int')->getArray('uid');
        $masterUserId = $this->db->fetchObject($this->db->select(array('MIN(uid)' => 'num'))->from('table.users'))->num;
        $deleteCount = 0;

        foreach ($users as $user) {
            if ($masterUserId == $user || $user == $this->user->id) {
                continue;
            }

            if ($this->delete($this->db->sql()->where('uid = ?', $user))) {
                $deleteCount ++;
            }
        }

        /** 提示信息 */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('Người dùng đã bị xóa') : _t('Không có người dùng nào bị xóa'),
        $deleteCount > 0 ? 'success' : 'notice');

        /** 转向原页 */
        $this->response->redirect(Typecho_Common::url('manage-users.php', $this->options->adminUrl));
    }

    /**
     * 入口函数
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->is('do=insert'))->insertUser();
        $this->on($this->request->is('do=update'))->updateUser();
        $this->on($this->request->is('do=delete'))->deleteUser();
        $this->response->redirect($this->options->adminUrl);
    }
}
