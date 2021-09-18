<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 typecho-list">
                <?php Typecho_Widget::widget('Widget_Plugins_List@activated', 'activated=1')->to($activatedPlugins); ?>
                <?php if ($activatedPlugins->have() || !empty($activatedPlugins->activatedPlugins)): ?>
                <h4 class="typecho-list-table-title"><?php _e('Các plugin đã bật'); ?></h4>
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="25%"/>
                            <col width="45%"/>
                            <col width="8%"/>
                            <col width="10%"/>
                            <col width=""/>
                        </colgroup>
                        <thead>
                            <tr>
                                <th><?php _e('Tên'); ?></th>
                                <th><?php _e('Mô tả'); ?></th>
                                <th><?php _e('Phiên bản'); ?></th>
                                <th><?php _e('Tác giả'); ?></th>
                                <th><?php _e('Hoạt động'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($activatedPlugins->next()): ?>
                            <tr id="plugin-<?php $activatedPlugins->name(); ?>">
                                <td><?php $activatedPlugins->title(); ?>
                                <?php if (!$activatedPlugins->dependence): ?>
                                <img src="<?php $options->adminUrl('images/notice.gif'); ?>" title="<?php _e('%s không hoạt động bình thường trong phiên bản Typecho này', $activatedPlugins->title); ?>" alt="<?php _e('%s không hoạt động bình thường trong phiên bản Typecho này', $activatedPlugins->title); ?>" class="tiny" />
                                <?php endif; ?>
                                </td>
                                <td><?php $activatedPlugins->description(); ?></td>
                                <td><?php $activatedPlugins->version(); ?></td>
                                <td><?php echo empty($activatedPlugins->homepage) ? $activatedPlugins->author : '<a href="' . $activatedPlugins->homepage
                                . '">' . $activatedPlugins->author . '</a>'; ?></td>
                                <td>
                                    <?php if ($activatedPlugins->activate || $activatedPlugins->deactivate || $activatedPlugins->config || $activatedPlugins->personalConfig): ?>
                                        <?php if ($activatedPlugins->config): ?>
                                            <a href="<?php $options->adminUrl('options-plugin.php?config=' . $activatedPlugins->name); ?>"><?php _e('Thiết lập'); ?></a>
                                            &bull;
                                        <?php endif; ?>
                                        <a lang="<?php _e('Bạn có chắc chắn muốn tắt plugin %s không?', $activatedPlugins->name); ?>" href="<?php $security->index('/action/plugins-edit?deactivate=' . $activatedPlugins->name); ?>"><?php _e('Vô hiệu'); ?></a>
                                    <?php else: ?>
                                        <span class="important"><?php _e('Plug and play'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if (!empty($activatedPlugins->activatedPlugins)): ?>
                            <?php foreach ($activatedPlugins->activatedPlugins as $key => $val): ?>
                            <tr>
                            <td><?php echo $key; ?></td>
                            <td colspan="3"><span class="warning"><?php _e('Tệp plugin này bị hỏng hoặc bị xóa không an toàn, bạn nên vô hiệu hóa nó'); ?></span></td>
                            <td><a lang="<?php _e('Bạn có chắc chắn muốn tắt plugin %s không?', $key); ?>" href="<?php $security->index('/action/plugins-edit?deactivate=' . $key); ?>"><?php _e('Vô hiệu'); ?></a></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php Typecho_Widget::widget('Widget_Plugins_List@unactivated', 'activated=0')->to($deactivatedPlugins); ?>
                <?php if ($deactivatedPlugins->have() || !$activatedPlugins->have()): ?>
                <h4 class="typecho-list-table-title"><?php _e('Các plugin bị vô hiệu hóa'); ?></h4>
                <div class="typecho-table-wrap">
                    <table class="typecho-list-table deactivate">
                        <colgroup>
                            <col width="25%"/>
                            <col width="45%"/>
                            <col width="8%"/>
                            <col width="10%"/>
                            <col width=""/>
                        </colgroup>
                        <thead>
                            <tr>
                                <th><?php _e('Tên'); ?></th>
                                <th><?php _e('Mô tả'); ?></th>
                                <th><?php _e('Phiên bản'); ?></th>
                                <th><?php _e('Tác giả'); ?></th>
                                <th class="typecho-radius-topright"><?php _e('Hoạt động'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($deactivatedPlugins->have()): ?>
                            <?php while ($deactivatedPlugins->next()): ?>
                            <tr id="plugin-<?php $deactivatedPlugins->name(); ?>">
                                <td><?php $deactivatedPlugins->title(); ?></td>
                                <td><?php $deactivatedPlugins->description(); ?></td>
                                <td><?php $deactivatedPlugins->version(); ?></td>
                                <td><?php echo empty($deactivatedPlugins->homepage) ? $deactivatedPlugins->author : '<a href="' . $deactivatedPlugins->homepage
                                . '">' . $deactivatedPlugins->author . '</a>'; ?></td>
                                <td>
                                    <a href="<?php $security->index('/action/plugins-edit?activate=' . $deactivatedPlugins->name); ?>"><?php _e('Cho phép'); ?></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                            	<td colspan="5"><h6 class="typecho-list-table-title"><?php _e('Không có plugin nào được cài đặt'); ?></h6></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
