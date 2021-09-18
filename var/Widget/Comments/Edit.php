<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id$
 */

/**
 * 评论编辑组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Widget_Comments_Edit extends Widget_Abstract_Comments implements Widget_Interface_Do
{
    /**
     * 标记评论状态
     *
     * @access private
     * @param integer $coid 评论主键
     * @param string $status 状态
     * @return boolean
     */
    private function mark($coid, $status)
    {
        $comment = $this->db->fetchRow($this->select()
        ->where('coid = ?', $coid)->limit(1), array($this, 'push'));

        if ($comment && $this->commentIsWriteable()) {
            /** 增加评论编辑插件接口 */
            $this->pluginHandle()->mark($comment, $this, $status);

            /** 不必更新的情况 */
            if ($status == $comment['status']) {
                return false;
            }

            /** 更新评论 */
            $this->db->query($this->db->update('table.comments')
            ->rows(array('status' => $status))->where('coid = ?', $coid));

            /** 更新相关内容的评论数 */
            if ('approved' == $comment['status'] && 'approved' != $status) {
                $this->db->query($this->db->update('table.contents')
                ->expression('commentsNum', 'commentsNum - 1')->where('cid = ? AND commentsNum > 0', $comment['cid']));
            } else if ('approved' != $comment['status'] && 'approved' == $status) {
                $this->db->query($this->db->update('table.contents')
                ->expression('commentsNum', 'commentsNum + 1')->where('cid = ?', $comment['cid']));
            }

            return true;
        }

        return false;
    }

    /**
     * 标记为待审核
     *
     * @access public
     * @return void
     */
    public function waitingComment()
    {
        $comments = $this->request->filter('int')->getArray('coid');
        $updateRows = 0;

        foreach ($comments as $comment) {
            if ($this->mark($comment, 'waiting')) {
                $updateRows ++;
            }
        }

        /** 设置提示信息 */
        $this->widget('Widget_Notice')->set($updateRows > 0 ? _t('Nhận xét đã được đánh dấu là đang chờ xử lý') : _t('Không có bình luận nào được đánh dấu là đang chờ xử lý'),
        $updateRows > 0 ? 'success' : 'notice');

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 标记为垃圾
     *
     * @access public
     * @return void
     */
    public function spamComment()
    {
        $comments = $this->request->filter('int')->getArray('coid');
        $updateRows = 0;

        foreach ($comments as $comment) {
            if ($this->mark($comment, 'spam')) {
                $updateRows ++;
            }
        }

        /** 设置提示信息 */
        $this->widget('Widget_Notice')->set($updateRows > 0 ? _t('Nhận xét đã bị đánh dấu là spam') : _t('Không có bình luận nào bị đánh dấu là spam'),
        $updateRows > 0 ? 'success' : 'notice');

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 标记为展现
     *
     * @access public
     * @return void
     */
    public function approvedComment()
    {
        $comments = $this->request->filter('int')->getArray('coid');
        $updateRows = 0;

        foreach ($comments as $comment) {
            if ($this->mark($comment, 'approved')) {
                $updateRows ++;
            }
        }

        /** 设置提示信息 */
        $this->widget('Widget_Notice')->set($updateRows > 0 ? _t('Nhận xét đã được thông qua') : _t('Không có bình luận nào được thông qua'),
        $updateRows > 0 ? 'success' : 'notice');

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 删除评论
     *
     * @access public
     * @return void
     */
    public function deleteComment()
    {
        $comments = $this->request->filter('int')->getArray('coid');
        $deleteRows = 0;

        foreach ($comments as $coid) {
            $comment = $this->db->fetchRow($this->select()
            ->where('coid = ?', $coid)->limit(1), array($this, 'push'));

            if ($comment && $this->commentIsWriteable()) {
                $this->pluginHandle()->delete($comment, $this);

                /** 删除评论 */
                $this->db->query($this->db->delete('table.comments')->where('coid = ?', $coid));

                /** 更新相关内容的评论数 */
                if ('approved' == $comment['status']) {
                    $this->db->query($this->db->update('table.contents')
                    ->expression('commentsNum', 'commentsNum - 1')->where('cid = ?', $comment['cid']));
                }
                
                $this->pluginHandle()->finishDelete($comment, $this);

                $deleteRows ++;
            }
        }
        
        if ($this->request->isAjax()) {
            
            if ($deleteRows > 0) {
                $this->response->throwJson(array(
                    'success'   => 1,
                    'message'   => _t('Đã xóa bình luận thành công')
                ));
            } else {
                $this->response->throwJson(array(
                    'success'   => 0,
                    'message'   => _t('Không xóa được bình luận')
                ));
            }
            
        } else {
            /** 设置提示信息 */
            $this->widget('Widget_Notice')->set($deleteRows > 0 ? _t('Bình luận đã bị xóa') : _t('Không có bình luận nào bị xóa'),
            $deleteRows > 0 ? 'success' : 'notice');

            /** 返回原网页 */
            $this->response->goBack();
        }
    }

    /**
     * 删除所有垃圾评论
     *
     * @access public
     * @return string
     */
    public function deleteSpamComment()
    {
        $deleteQuery = $this->db->delete('table.comments')->where('status = ?', 'spam');
        if (!$this->request->__typecho_all_comments || !$this->user->pass('editor', true)) {
            $deleteQuery->where('ownerId = ?', $this->user->uid);
        }

        if (isset($this->request->cid)) {
            $deleteQuery->where('cid = ?', $this->request->cid);
        }

        $deleteRows = $this->db->query($deleteQuery);

        /** 设置提示信息 */
        $this->widget('Widget_Notice')->set($deleteRows > 0 ?
        _t('Tất cả các bình luận spam đã bị xóa') : _t('Không có bình luận spam nào bị xóa'),
        $deleteRows > 0 ? 'success' : 'notice');

        /** 返回原网页 */
        $this->response->goBack();
    }

    /**
     * 获取可编辑的评论
     *
     * @access public
     * @return void
     */
    public function getComment()
    {
        $coid = $this->request->filter('int')->coid;
        $comment = $this->db->fetchRow($this->select()
            ->where('coid = ?', $coid)->limit(1), array($this, 'push'));

        if ($comment && $this->commentIsWriteable()) {

            $this->response->throwJson(array(
                'success'   => 1,
                'comment'   => $comment
            ));

        } else {

            $this->response->throwJson(array(
                'success'   => 0,
                'message'   => _t('Không nhận được bình luận')
            ));

        }
    }

    /**
     * 编辑评论
     *
     * @access public
     * @return void
     */
    public function editComment()
    {
        $coid = $this->request->filter('int')->coid;
        $commentSelect = $this->db->fetchRow($this->select()
            ->where('coid = ?', $coid)->limit(1), array($this, 'push'));

        if ($commentSelect && $this->commentIsWriteable()) {
        
            $comment['text'] = $this->request->text;
            $comment['author'] = $this->request->filter('strip_tags', 'trim', 'xss')->author;
            $comment['mail'] = $this->request->filter('strip_tags', 'trim', 'xss')->mail;
            $comment['url'] = $this->request->filter('url')->url;
            
            /** 评论插件接口 */
            $this->pluginHandle()->edit($comment, $this);

            /** 更新评论 */
            $this->update($comment, $this->db->sql()->where('coid = ?', $coid));

            $updatedComment = $this->db->fetchRow($this->select()
                ->where('coid = ?', $coid)->limit(1), array($this, 'push'));
            $updatedComment['content'] = $this->content;
            
            /** 评论插件接口 */
            $this->pluginHandle()->finishEdit($this);

            $this->response->throwJson(array(
                'success'   => 1,
                'comment'   => $updatedComment
            ));
        }

        $this->response->throwJson(array(
            'success'   => 0,
            'message'   => _t('Không sửa được nhận xét')
        ));
    }
    
    /**
     * 回复评论
     *
     * @access public
     * @return void
     */
    public function replyComment()
    {
        $coid = $this->request->filter('int')->coid;
        $commentSelect = $this->db->fetchRow($this->select()
            ->where('coid = ?', $coid)->limit(1), array($this, 'push'));

        if ($commentSelect && $this->commentIsWriteable()) {
        
            $comment = array(
                'cid'       =>  $commentSelect['cid'],
                'created'   =>  $this->options->time,
                'agent'     =>  $this->request->getAgent(),
                'ip'        =>  $this->request->getIp(),
                'ownerId'   =>  $commentSelect['ownerId'],
                'authorId'  =>  $this->user->uid,
                'type'      =>  'comment',
                'author'    =>  $this->user->screenName,
                'mail'      =>  $this->user->mail,
                'url'       =>  $this->user->url,
                'parent'    =>  $coid,
                'text'      =>  $this->request->text,
                'status'    =>  'approved'
            );
            
            /** 评论插件接口 */
            $this->pluginHandle()->comment($comment, $this);

            /** 回复评论 */
            $commentId = $this->insert($comment);

            $insertComment = $this->db->fetchRow($this->select()
                ->where('coid = ?', $commentId)->limit(1), array($this, 'push'));
            $insertComment['content'] = $this->content;
            
            /** 评论完成接口 */
            $this->pluginHandle()->finishComment($this);

            $this->response->throwJson(array(
                'success'   => 1,
                'comment'   => $insertComment
            ));
        }

        $this->response->throwJson(array(
            'success'   => 0,
            'message'   => _t('Trả lời bình luận không thành công')
        ));
    }

    /**
     * 初始化函数
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->user->pass('contributor');
        $this->security->protect();
        $this->on($this->request->is('do=waiting'))->waitingComment();
        $this->on($this->request->is('do=spam'))->spamComment();
        $this->on($this->request->is('do=approved'))->approvedComment();
        $this->on($this->request->is('do=delete'))->deleteComment();
        $this->on($this->request->is('do=delete-spam'))->deleteSpamComment();
        $this->on($this->request->is('do=get&coid'))->getComment();
        $this->on($this->request->is('do=edit&coid'))->editComment();
        $this->on($this->request->is('do=reply&coid'))->replyComment();

        $this->response->redirect($this->options->adminUrl);
    }
}
