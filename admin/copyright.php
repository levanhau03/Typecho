<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<div class="typecho-foot" role="contentinfo">
    <div class="copyright">
        <a href="http://typecho.org" class="i-logo-s">Typecho</a>
        <p><?php _e('Được hỗ trợ bởi <a href="http://typecho.org">%s</a>, phiên bản %s (%s)', $options->software, $prefixVersion, $suffixVersion); ?></p>
    </div>
    <nav class="resource">
        <a href="http://docs.typecho.org"><?php _e('Tài liệu trợ giúp'); ?></a> &bull;
        <a href="http://forum.typecho.org"><?php _e('Diễn đàn hỗ trợ'); ?></a> &bull;
        <a href="https://github.com/typecho/typecho/issues"><?php _e('Báo lỗi'); ?></a> &bull;
        <a href="http://typecho.org/download"><?php _e('Tải xuống'); ?></a>
    </nav>
</div>
