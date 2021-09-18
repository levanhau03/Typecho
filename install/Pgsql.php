<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<li>
<label class="typecho-label" for="dbHost"><?php _e('Địa chỉ cơ sở dữ liệu'); ?></label>
<input type="text" class="text" name="dbHost" id="dbHost" value="<?php _v('dbHost', 'localhost'); ?>"/>
<p class="description"><?php _e('Bạn có thể sử dụng "%s"', 'localhost'); ?></p>
</li>
<li>
<label class="typecho-label" for="dbPort"><?php _e('Cổng cơ sở dữ liệu'); ?></label>
<input type="text" class="text" name="dbPort" id="dbPort" value="<?php _v('dbPort', '5432'); ?>"/>
<p class="description"><?php _e('Nếu bạn không biết ý nghĩa của tùy chọn này, vui lòng giữ cài đặt mặc định'); ?></p>
</li>
<li>
<label class="typecho-label" for="dbUser"><?php _e('Người dùng cơ sở dữ liệu'); ?></label>
<input type="text" class="text" name="dbUser" id="dbUser" value="<?php _v('dbUser', 'postgres'); ?>" />
<p class="description"><?php _e('Bạn có thể sử dụng "%s"', 'postgres'); ?></p>
</li>
<li>
<label class="typecho-label" for="dbPassword"><?php _e('Mật khẩu cơ sở dữ liệu'); ?></label>
<input type="password" class="text" name="dbPassword" id="dbPassword" value="<?php _v('dbPassword'); ?>" />
</li>
<li>
<label class="typecho-label" for="dbDatabase"><?php _e('Tên cơ sở dữ liệu'); ?></label>
<input type="text" class="text" name="dbDatabase" id="dbDatabase" value="<?php _v('dbDatabase', 'typecho'); ?>" />
<p class="description"><?php _e('Vui lòng chỉ định tên cơ sở dữ liệu'); ?></p>
</li>
<input type="hidden" name="dbCharset" value="<?php _e('utf8'); ?>" />
