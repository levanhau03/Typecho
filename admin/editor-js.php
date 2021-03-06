<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php $content = !empty($post) ? $post : $page; if ($options->markdown): ?>
<script src="<?php $options->adminStaticUrl('js', 'hyperdown.js?v=' . $suffixVersion); ?>"></script>
<script src="<?php $options->adminStaticUrl('js', 'pagedown.js?v=' . $suffixVersion); ?>"></script>
<script src="<?php $options->adminStaticUrl('js', 'pagedown-extra.js?v=' . $suffixVersion); ?>"></script>
<script src="<?php $options->adminStaticUrl('js', 'diff.js?v=' . $suffixVersion); ?>"></script>
<script>
$(document).ready(function () {
    var textarea = $('#text'),
        toolbar = $('<div class="editor" id="wmd-button-bar" />').insertBefore(textarea.parent()),
        preview = $('<div id="wmd-preview" class="wmd-hidetab" />').insertAfter('.editor');

    var options = {}, isMarkdown = <?php echo intval($content->isMarkdown || !$content->have()); ?>;

    options.strings = {
        bold: '<?php _e('In đậm'); ?> <strong> Ctrl+B',
        boldexample: '<?php _e('Chữ in đậm'); ?>',
            
        italic: '<?php _e('In nghiêng'); ?> <em> Ctrl+I',
        italicexample: '<?php _e('Chữ in nghiêng'); ?>',

        link: '<?php _e('Liên kết'); ?> <a> Ctrl+L',
        linkdescription: '<?php _e('Vui lòng nhập mô tả liên kết'); ?>',

        quote:  '<?php _e('Trích dẫn'); ?> <blockquote> Ctrl+Q',
        quoteexample: '<?php _e('Các ký tự được trích dẫn'); ?>',

        code: '<?php _e('Mã'); ?> <pre><code> Ctrl+K',
        codeexample: '<?php _e('Vui lòng nhập mã'); ?>',

        image: '<?php _e('Hình ảnh'); ?> <img> Ctrl+G',
        imagedescription: '<?php _e('Vui lòng nhập mô tả hình ảnh'); ?>',

        olist: '<?php _e('Danh sách các số'); ?> <ol> Ctrl+O',
        ulist: '<?php _e('Danh sách bình thường'); ?> <ul> Ctrl+U',
        litem: '<?php _e('Liệt kê mục'); ?>',

        heading: '<?php _e('Tiêu đề'); ?> <h1>/<h2> Ctrl+H',
        headingexample: '<?php _e('Tiêu đề văn bản'); ?>',

        hr: '<?php _e('Đường phân chia'); ?> <hr> Ctrl+R',
        more: '<?php _e('Đường phân chia trừu tượng'); ?> <!--more--> Ctrl+M',

        undo: '<?php _e('Thu hồi'); ?> - Ctrl+Z',
        redo: '<?php _e('Làm lại'); ?> - Ctrl+Y',
        redomac: '<?php _e('Làm lại'); ?> - Ctrl+Shift+Z',

        fullscreen: '<?php _e('Toàn màn hình'); ?> - Ctrl+J',
        exitFullscreen: '<?php _e('Thoát toàn màn hình'); ?> - Ctrl+E',
        fullscreenUnsupport: '<?php _e('Trình duyệt này không hỗ trợ hoạt động toàn màn hình'); ?>',

        imagedialog: '<p><b><?php _e('Chèn ảnh'); ?></b></p><p><?php _e('Vui lòng nhập địa chỉ hình ảnh từ xa sẽ được chèn vào hộp nhập liệu bên dưới'); ?></p><p><?php _e('Bạn cũng có thể sử dụng chức năng đính kèm để chèn ảnh cục bộ đã tải lên'); ?></p>',
        linkdialog: '<p><b><?php _e('Chèn đường dẫn'); ?></b></p><p><?php _e('Vui lòng nhập địa chỉ liên kết sẽ được chèn vào ô nhập liệu bên dưới'); ?></p>',

        ok: '<?php _e('Ok'); ?>',
        cancel: '<?php _e('Hủy bỏ'); ?>',

        help: '<?php _e('Trợ giúp cú pháp Markdown'); ?>'
    };

    var converter = new HyperDown(),
        editor = new Markdown.Editor(converter, '', options),
        diffMatch = new diff_match_patch(), last = '', preview = $('#wmd-preview'),
        mark = '@mark' + Math.ceil(Math.random() * 100000000) + '@',
        span = '<span class="diff" />',
        cache = {};

    // 修正白名单
    converter.enableHtml(true);
    converter.commonWhiteList += '|img|cite|embed|iframe';
    converter.specialWhiteList = $.extend(converter.specialWhiteList, {
        'ol'            :  'ol|li',
        'ul'            :  'ul|li',
        'blockquote'    :  'blockquote',
        'pre'           :  'pre|code'
    });

    converter.hook('beforeParseInline', function (html) {
        return html.replace(/^\s*<!\-\-\s*more\s*\-\->\s*$/, function () {
            return converter.makeHolder('<!--more-->');
        });
    });

    // 自动跟随
    converter.hook('makeHtml', function (html) {
        html = html.replace('<p><!--more--></p>', '<!--more-->');
        
        if (html.indexOf('<!--more-->') > 0) {
            var parts = html.split(/\s*<\!\-\-more\-\->\s*/),
                summary = parts.shift(),
                details = parts.join('');

            html = '<div class="summary">' + summary + '</div>'
                + '<div class="details">' + details + '</div>';
        }


        var diffs = diffMatch.diff_main(last, html);
        last = html;

        if (diffs.length > 0) {
            var stack = [], markStr = mark;
            
            for (var i = 0; i < diffs.length; i ++) {
                var diff = diffs[i], op = diff[0], str = diff[1]
                    sp = str.lastIndexOf('<'), ep = str.lastIndexOf('>');

                if (op != 0) {
                    if (sp >=0 && sp > ep) {
                        if (op > 0) {
                            stack.push(str.substring(0, sp) + markStr + str.substring(sp));
                        } else {
                            var lastStr = stack[stack.length - 1], lastSp = lastStr.lastIndexOf('<');
                            stack[stack.length - 1] = lastStr.substring(0, lastSp) + markStr + lastStr.substring(lastSp);
                        }
                    } else {
                        if (op > 0) {
                            stack.push(str + markStr);
                        } else {
                            stack.push(markStr);
                        }
                    }
                    
                    markStr = '';
                } else {
                    stack.push(str);
                }
            }

            html = stack.join('');

            if (!markStr) {
                var pos = html.indexOf(mark), prev = html.substring(0, pos),
                    next = html.substr(pos + mark.length),
                    sp = prev.lastIndexOf('<'), ep = prev.lastIndexOf('>');

                if (sp >= 0 && sp > ep) {
                    html = prev.substring(0, sp) + span + prev.substring(sp) + next;
                } else {
                    html = prev + span + next;
                }
            }
        }

        // 替换img
        html = html.replace(/<(img)\s+([^>]*)\s*src="([^"]+)"([^>]*)>/ig, function (all, tag, prefix, src, suffix) {
            if (!cache[src]) {
                cache[src] = false;
            } else {
                return '<span class="cache" data-width="' + cache[src][0] + '" data-height="' + cache[src][1] + '" '
                    + 'style="background:url(' + src + ') no-repeat left top; width:'
                    + cache[src][0] + 'px; height:' + cache[src][1] + 'px; display: inline-block; max-width: 100%;'
                    + '-webkit-background-size: contain;-moz-background-size: contain;-o-background-size: contain;background-size: contain;" />';
            }

            return all;
        });

        // 替换block
        html = html.replace(/<(iframe|embed)\s+([^>]*)>/ig, function (all, tag, src) {
            if (src[src.length - 1] == '/') {
                src = src.substring(0, src.length - 1);
            }

            return '<div style="background: #ddd; height: 40px; overflow: hidden; line-height: 40px; text-align: center; font-size: 12px; color: #777">'
                + tag + ' : ' + $.trim(src) + '</div>';
        });

        return html;
    });

    function cacheResize() {
        var t = $(this), w = parseInt(t.data('width')), h = parseInt(t.data('height')),
            ow = t.width();

        t.height(h * ow / w);
    }

    var to;
    editor.hooks.chain('onPreviewRefresh', function () {
        var diff = $('.diff', preview), scrolled = false;

        if (to) {
            clearTimeout(to);
        }

        $('img', preview).load(function () {
            var t = $(this), src = t.attr('src');

            if (scrolled) {
                preview.scrollTo(diff, {
                    offset  :   - 50
                });
            }

            if (!!src && !cache[src]) {
                cache[src] = [this.width, this.height];
            }
        });

        $('.cache', preview).resize(cacheResize).each(cacheResize);
        
        var changed = $('.diff', preview).parent();
        if (!changed.is(preview)) {
            changed.css('background-color', 'rgba(255,230,0,0.5)');
            to = setTimeout(function () {
                changed.css('background-color', 'transparent');
            }, 4500);
        }

        if (diff.length > 0) {
            var p = diff.position(), lh = diff.parent().css('line-height');
            lh = !!lh ? parseInt(lh) : 0;

            if (p.top < 0 || p.top > preview.height() - lh) {
                preview.scrollTo(diff, {
                    offset  :   - 50
                });
                scrolled = true;
            }
        }
    });

    <?php Typecho_Plugin::factory('admin/editor-js.php')->markdownEditor($content); ?>

    var input = $('#text'), th = textarea.height(), ph = preview.height(),
        uploadBtn = $('<button type="button" id="btn-fullscreen-upload" class="btn btn-link">'
            + '<i class="i-upload"><?php _e('Đính kèm'); ?></i></button>')
            .prependTo('.submit .right')
            .click(function() {
                $('a', $('.typecho-option-tabs li').not('.active')).trigger('click');
                return false;
            });

    $('.typecho-option-tabs li').click(function () {
        uploadBtn.find('i').toggleClass('i-upload-active',
            $('#tab-files-btn', this).length > 0);
    });

    editor.hooks.chain('enterFakeFullScreen', function () {
        th = textarea.height();
        ph = preview.height();
        $(document.body).addClass('fullscreen');
        var h = $(window).height() - toolbar.outerHeight();
        
        textarea.css('height', h);
        preview.css('height', h);
    });

    editor.hooks.chain('enterFullScreen', function () {
        $(document.body).addClass('fullscreen');
        
        var h = window.screen.height - toolbar.outerHeight();
        textarea.css('height', h);
        preview.css('height', h);
    });

    editor.hooks.chain('exitFullScreen', function () {
        $(document.body).removeClass('fullscreen');
        textarea.height(th);
        preview.height(ph);
    });

    function initMarkdown() {
        editor.run();

        var imageButton = $('#wmd-image-button'),
            linkButton = $('#wmd-link-button');

        Typecho.insertFileToEditor = function (file, url, isImage) {
            var button = isImage ? imageButton : linkButton;

            options.strings[isImage ? 'imagename' : 'linkname'] = file;
            button.trigger('click');

            var checkDialog = setInterval(function () {
                if ($('.wmd-prompt-dialog').length > 0) {
                    $('.wmd-prompt-dialog input').val(url).select();
                    clearInterval(checkDialog);
                    checkDialog = null;
                }
            }, 10);
        };

        Typecho.uploadComplete = function (file) {
            Typecho.insertFileToEditor(file.title, file.url, file.isImage);
        };

        // 编辑预览切换
        var edittab = $('.editor').prepend('<div class="wmd-edittab"><a href="#wmd-editarea" class="active"><?php _e('Viết'); ?></a><a href="#wmd-preview"><?php _e('Xem trước'); ?></a></div>'),
            editarea = $(textarea.parent()).attr("id", "wmd-editarea");

        $(".wmd-edittab a").click(function() {
            $(".wmd-edittab a").removeClass('active');
            $(this).addClass("active");
            $("#wmd-editarea, #wmd-preview").addClass("wmd-hidetab");
        
            var selected_tab = $(this).attr("href"),
                selected_el = $(selected_tab).removeClass("wmd-hidetab");

            // 预览时隐藏编辑器按钮
            if (selected_tab == "#wmd-preview") {
                $("#wmd-button-row").addClass("wmd-visualhide");
            } else {
                $("#wmd-button-row").removeClass("wmd-visualhide");
            }

            // 预览和编辑窗口高度一致
            $("#wmd-preview").outerHeight($("#wmd-editarea").innerHeight());

            return false;
        });
    }

    if (isMarkdown) {
        initMarkdown();
    } else {
        var notice = $('<div class="message notice"><?php _e('Bài viết này không được tạo bởi Markdown văn phạm, tiếp tục sử dụng Markdown để chỉnh sửa nó?'); ?> '
            + '<button class="btn btn-xs primary yes"><?php _e('Có'); ?></button> ' 
            + '<button class="btn btn-xs no"><?php _e('Không'); ?></button></div>')
            .hide().insertBefore(textarea).slideDown();

        $('.yes', notice).click(function () {
            notice.remove();
            $('<input type="hidden" name="markdown" value="1" />').appendTo('.submit');
            initMarkdown();
        });

        $('.no', notice).click(function () {
            notice.remove();
        });
    }
});
</script>
<?php endif; ?>

