<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $defaultDir = dirname($_SERVER['SCRIPT_FILENAME']) . '/usr/' . uniqid() . '.db'; ?>
<li>
<label class="typecho-label" for="dbFile"><?php _e('Đường dẫn tệp cơ sở dữ liệu'); ?></label>
<input type="text" class="text" name="dbFile" id="dbFile" value="<?php _v('dbFile', $defaultDir); ?>"/>
<p class="description"><?php _e('"%s" là địa chỉ chúng tôi tự động tạo cho bạn', $defaultDir); ?></p>
</li>
