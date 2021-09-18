<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = Typecho_Widget::widget('Widget_Stat');
?>

<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main">
            <div class="col-mb-12 col-tb-3">
                <p><a href="http://gravatar.com/emails/" title="<?php _e('Sửa đổi hình đại diện trên Gravatar'); ?>"><?php echo '<img class="profile-avatar" src="' . Typecho_Common::gravatarUrl($user->mail, 220, 'X', 'mm', $request->isSecure()) . '" alt="' . $user->screenName . '" />'; ?></a></p>
                <h2><?php $user->screenName(); ?></h2>
                <p><?php $user->name(); ?></p>
                <p><?php _e('Hiện tại có <em>%s</em> bài đăng, <em>%s</em> nhận xét về bạn nằm trong <em>%s</em> chuyên mục.', 
                $stat->myPublishedPostsNum, $stat->myPublishedCommentsNum, $stat->categoriesNum); ?></p>
                <p><?php
                if ($user->logged > 0) {
                    $logged = new Typecho_Date($user->logged);
                    _e('Lần đăng nhập cuối: %s', $logged->word());
                }
                ?></p>
            </div>

            <div class="col-mb-12 col-tb-6 col-tb-offset-1 typecho-content-panel" role="form">
                <section>
                    <h3><?php _e('Thông tin cá nhân'); ?></h3>
                    <?php Typecho_Widget::widget('Widget_Users_Profile')->profileForm()->render(); ?>
                </section>

                <?php if($user->pass('contributor', true)): ?>
                <br>
                <section id="writing-option">
                    <h3><?php _e('Cài đặt soạn thảo'); ?></h3>
                    <?php Typecho_Widget::widget('Widget_Users_Profile')->optionsForm()->render(); ?>
                </section>
                <?php endif; ?>

                <br>

                <section id="change-password">
                    <h3><?php _e('Đổi mật khẩu'); ?></h3>
                    <?php Typecho_Widget::widget('Widget_Users_Profile')->passwordForm()->render(); ?>
                </section>

                <?php Typecho_Widget::widget('Widget_Users_Profile')->personalFormList(); ?>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
Typecho_Plugin::factory('admin/profile.php')->bottom();
include 'footer.php';
?>
