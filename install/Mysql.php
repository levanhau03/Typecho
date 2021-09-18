<?php if(!defined('__TYPECHO_ROOT_DIR__')) exit; ?>

<?php
$engine = '';

if (defined('SAE_MYSQL_DB') && SAE_MYSQL_DB != "app_") {
    $engine = 'SAE';
} else if (!!getenv('HTTP_BAE_ENV_ADDR_SQL_IP')) {
    $engine = 'BAE';
} else if (ini_get('acl.app_id') && class_exists('Alibaba')) {
    $engine = 'ACE';
} else if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'],'Google App Engine') !== false) {
    $engine = 'GAE';
}
?>

<?php if (!empty($engine)): ?>
<h3 class="warning"><?php _e('Hệ thống sẽ tự động khớp các tùy chọn cài đặt của môi trường %s cho bạn', $engine); ?></h3>
<?php endif; ?>

<?php if ('SAE' == $engine): ?>
<!-- SAE -->
    <input type="hidden" name="config" value="array (
    'host'      =>  SAE_MYSQL_HOST_M,
    'user'      =>  SAE_MYSQL_USER,
    'password'  =>  SAE_MYSQL_PASS,
    'charset'   =>  '<?php _e('utf8'); ?>',
    'port'      =>  SAE_MYSQL_PORT,
    'database'  =>  SAE_MYSQL_DB
)" />
    <input type="hidden" name="dbHost" value="<?php echo SAE_MYSQL_HOST_M; ?>" />
    <input type="hidden" name="dbPort" value="<?php echo SAE_MYSQL_PORT; ?>" />
    <input type="hidden" name="dbUser" value="<?php echo SAE_MYSQL_USER; ?>" />
    <input type="hidden" name="dbPassword" value="<?php echo SAE_MYSQL_PASS; ?>" />
    <input type="hidden" name="dbDatabase" value="<?php echo SAE_MYSQL_DB; ?>" />
<?php elseif ('BAE' == $engine):
$baeDbUser = "getenv('HTTP_BAE_ENV_AK')";
$baeDbPassword = "getenv('HTTP_BAE_ENV_SK')";
?>
<!-- BAE -->
    <?php if (!getenv('HTTP_BAE_ENV_AK')): $baeDbUser = "'{user}'"; ?>
    <li>
        <label class="typecho-label" for="dbUser"><?php _e('Khóa API ứng dụng'); ?></label>
        <input type="text" class="text" name="dbUser" id="dbUser" value="<?php _v('dbUser'); ?>" />
    </li>
    <?php else: ?>
    <input type="hidden" name="dbUser" value="<?php echo getenv('HTTP_BAE_ENV_AK'); ?>" />
    <?php endif; ?>

    <?php if (!getenv('HTTP_BAE_ENV_SK')): $baeDbPassword = "'{password}'"; ?>
    <li>
        <label class="typecho-label" for="dbPassword"><?php _e('Áp dụng khóa bí mật'); ?></label>
        <input type="text" class="text" name="dbPassword" id="dbPassword" value="<?php _v('dbPassword'); ?>" />
    </li>
    <?php else: ?>
    <input type="hidden" name="dbPassword" value="<?php echo getenv('HTTP_BAE_ENV_SK'); ?>" />
    <?php endif; ?>

    <li>
        <label class="typecho-label" for="dbDatabase"><?php _e('Tên cơ sở dữ liệu'); ?></label>
        <input type="text" class="text" id="dbDatabase" name="dbDatabase" value="<?php _v('dbDatabase'); ?>" />
        <p class="description"><?php _e('Bạn có thể thấy tên cơ sở dữ liệu bạn đã tạo trên trang quản lý của dịch vụ MySQL'); ?></p>
    </li>
    <input type="hidden" name="config" value="array (
    'host'      =>  getenv('HTTP_BAE_ENV_ADDR_SQL_IP'),
    'user'      =>  <?php echo $baeDbUser; ?>,
    'password'  =>  <?php echo $baeDbPassword; ?>,
    'charset'   =>  '<?php _e('utf8'); ?>',
    'port'      =>  getenv('HTTP_BAE_ENV_ADDR_SQL_PORT'),
    'database'  =>  '{database}'
)" />
    <input type="hidden" name="dbHost" value="<?php echo getenv('HTTP_BAE_ENV_ADDR_SQL_IP'); ?>" />
    <input type="hidden" name="dbPort" value="<?php echo getenv('HTTP_BAE_ENV_ADDR_SQL_PORT'); ?>" />
<?php elseif ('ACE' == $engine): ?>
<!-- ACE -->

    <li>
        <label class="typecho-label" for="dbHost"><?php _e('Địa chỉ cơ sở dữ liệu'); ?></label>
        <input type="text" class="text" name="dbHost" id="dbHost" value="<?php _v('dbHost', 'localhost'); ?>"/>
        <p class="description"><?php _e('Bạn có thể truy cập bảng điều khiển RDS để biết thông tin chi tiết'); ?></p>
    </li>
    <li>
        <label class="typecho-label" for="dbPort"><?php _e('Cổng cơ sở dữ liệu'); ?></label>
        <input type="text" class="text" name="dbPort" id="dbPort" value="<?php _v('dbPort', 3306); ?>"/>
    </li>
    <li>
        <label class="typecho-label" for="dbUser"><?php _e('Người dùng cơ sở dữ liệu'); ?></label>
        <input type="text" class="text" name="dbUser" id="dbUser" value="<?php _v('dbUser'); ?>" />
    </li>
    <li>
        <label class="typecho-label" for="dbPassword"><?php _e('Mật khẩu cơ sở dữ liệu'); ?></label>
        <input type="password" class="text" name="dbPassword" id="dbPassword" value="<?php _v('dbPassword'); ?>" />
    </li>
    <li>
        <label class="typecho-label" for="dbDatabase"><?php _e('Tên cơ sở dữ liệu'); ?></label>
        <input type="text" class="text" name="dbDatabase" id="dbDatabase" value="<?php _v('dbDatabase', 'typecho'); ?>" />
    </li>

<?php elseif ('GAE' == $engine): ?>
<!-- GAE -->
    <h3 class="warning"><?php _e('Hệ thống sẽ tự động khớp các tùy chọn cài đặt của môi trường %s cho bạn', 'GAE'); ?></h3>
<?php if (0 === strpos($adapter, 'Pdo_')): ?>
    <li>
        <label class="typecho-label" for="dbHost"><?php _e('Tên phiên bản cơ sở dữ liệu'); ?></label>
        <input type="text" class="text" name="dbHost" id="dbHost" value="<?php _v('dbHost'); ?>"/>
        <p class="description"><?php _e('Vui lòng điền vào tên của phiên bản cơ sở dữ liệu bạn đã tạo trong bảng điều khiển Cloud SQL, ví dụ: %s', '<em class="warning">/cloudsql/typecho-gae:typecho</em>'); ?></p>
    </li>
<?php else: ?>
    <li>
        <label class="typecho-label" for="dbHost"><?php _e('Tên phiên bản cơ sở dữ liệu'); ?></label>
        <input type="text" class="text" name="dbHost" id="dbHost" value="<?php _v('dbHost'); ?>"/>
        <p class="description"><?php _e('Vui lòng điền vào tên của phiên bản cơ sở dữ liệu bạn đã tạo trong bảng điều khiển Cloud SQL, ví dụ: %s', '<em class="warning">:/cloudsql/typecho-gae:typecho</em>'); ?></p>
    </li>
<?php endif; ?>

    <li>
        <label class="typecho-label" for="dbUser"><?php _e('Người dùng cơ sở dữ liệu'); ?></label>
        <input type="text" class="text" name="dbUser" id="dbUser" value="<?php _v('dbUser'); ?>" />
    </li>
    <li>
        <label class="typecho-label" for="dbPassword"><?php _e('Mật khẩu cơ sở dữ liệu'); ?></label>
        <input type="password" class="text" name="dbPassword" id="dbPassword" value="<?php _v('dbPassword'); ?>" />
    </li>
    <li>
        <label class="typecho-label" for="dbDatabase"><?php _e('Tên cơ sở dữ liệu'); ?></label>
        <input type="text" class="text" name="dbDatabase" id="dbDatabase" value="<?php _v('dbDatabase', 'typecho'); ?>" />
        <p class="description"><?php _e('Vui lòng điền vào tên cơ sở dữ liệu bạn đã tạo trong phiên bản Cloud SQL'); ?></p>
    </li>

<?php if (0 === strpos($adapter, 'Pdo_')): ?>
    <input type="hidden" name="dbDsn" value="mysql:dbname={database};unix_socket={host};charset=<?php _e('utf8'); ?>" />
    <input type="hidden" name="config" value="array (
    'dsn'       =>  '{dsn}',
    'user'      =>  '{user}',
    'password'  =>  '{password}'
)" />
<?php else: ?>
    <input type="hidden" name="config" value="array (
    'host'      =>  '{host}',
    'database'  =>  '{database}',
    'user'      =>  '{user}',
    'password'  =>  '{password}'
)" />
<?php endif; ?>


<?php  else: ?>
    <li>
        <label class="typecho-label" for="dbHost"><?php _e('Địa chỉ cơ sở dữ liệu'); ?></label>
        <input type="text" class="text" name="dbHost" id="dbHost" value="<?php _v('dbHost', 'localhost'); ?>"/>
        <p class="description"><?php _e('Bạn có thể sử dụng "%s"', 'localhost'); ?></p>
    </li>
    <li>
        <label class="typecho-label" for="dbPort"><?php _e('Cổng cơ sở dữ liệu'); ?></label>
        <input type="text" class="text" name="dbPort" id="dbPort" value="<?php _v('dbPort', '3306'); ?>"/>
        <p class="description"><?php _e('Nếu bạn không biết ý nghĩa của tùy chọn này, vui lòng giữ cài đặt mặc định'); ?></p>
    </li>
    <li>
        <label class="typecho-label" for="dbUser"><?php _e('Người dùng cơ sở dữ liệu'); ?></label>
        <input type="text" class="text" name="dbUser" id="dbUser" value="<?php _v('dbUser', 'root'); ?>" />
        <p class="description"><?php _e('Bạn có thể sử dụng "%s"', 'root'); ?></p>
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

<?php  endif; ?>
<input type="hidden" name="dbCharset" value="<?php _e('utf8'); ?>" />

