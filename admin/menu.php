<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<div class="typecho-head-nav clearfix" role="navigation">
    <nav id="typecho-nav-list">
        <?php $menu->output(); ?>
    </nav>
    <div class="operate">
        <?php Typecho_Plugin::factory('admin/menu.php')->navBar(); ?>
        <a title="<?php
                    if ($user->logged > 0) {
                        $logged = new Typecho_Date($user->logged);
                        _e('Lần đăng nhập cuối: %s', $logged->word());
                    }
                    ?>" href="<?php $options->adminUrl('profile.php'); ?>" class="author"><?php $user->screenName(); ?></a><a class="exit" href="<?php $options->logoutUrl(); ?>"><?php _e('Đăng xuất'); ?></a><a href="<?php $options->siteUrl(); ?>"><?php _e('Trang web'); ?></a>
    </div>
</div>

