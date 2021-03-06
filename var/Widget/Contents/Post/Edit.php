<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
class Widget_Contents_Post_Edit extends Widget_Abstract_Contents implements Widget_Interface_Do
{
    protected $themeCustomFieldsHook = 'themePostFields';

    protected function ___tags()
    {
        if ($this->have()) {
            return $this->db->fetchAll($this->db
            ->select()->from('table.metas')
            ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
            ->where('table.relationships.cid = ?', $this->cid)
            ->where('table.metas.type = ?', 'tag'), array($this->widget('Widget_Abstract_Metas'), 'filter'));
        }

        return array();
    }

    protected function ___date()
    {
        return new Typecho_Date();
    }

    protected function ___draft()
    {
        if ($this->have()) {
            if ('post_draft' == $this->type) {
                return $this->row;
            } else {
                return $this->db->fetchRow($this->widget('Widget_Abstract_Contents')->select()
                ->where('table.contents.parent = ? AND (table.contents.type = ? OR table.contents.type = ?)',
                    $this->cid, 'post_draft', 'page_draft')
                ->limit(1), array($this->widget('Widget_Abstract_Contents'), 'filter'));
            }
        }

        return NULL;
    }

    protected function getFields()
    {
        $fields = array();
        $fieldNames = $this->request->getArray('fieldNames');

        if (!empty($fieldNames)) {
            $data = array(
                'fieldNames'    =>  $this->request->getArray('fieldNames'),
                'fieldTypes'    =>  $this->request->getArray('fieldTypes'),
                'fieldValues'   =>  $this->request->getArray('fieldValues')
            );
            foreach ($data['fieldNames'] as $key => $val) {
                if (empty($val)) {
                    continue;
                }

                $fields[$val] = array($data['fieldTypes'][$key], $data['fieldValues'][$key]);
            }
        }

        $customFields = $this->request->getArray('fields');
        if (!empty($customFields)) {
            $fields = array_merge($fields, $customFields);
        }

        return $fields;
    }

    protected function getCreated()
    {
        $created = $this->options->time;
        if (!empty($this->request->created)) {
            $created = $this->request->created;
        } else if (!empty($this->request->date)) {
            $dstOffset = !empty($this->request->dst) ? $this->request->dst : 0;
            $timezoneOffset = isset($this->request->timezone) ? intval($this->request->timezone) : $this->options->timezone;
            $created = strtotime($this->request->date) - $timezoneOffset + $this->options->serverTimezone - $dstOffset;
        } else if (!empty($this->request->year) && !empty($this->request->month) && !empty($this->request->day)) {
            $second = intval($this->request->get('sec', date('s')));
            $min = intval($this->request->get('min', date('i')));
            $hour = intval($this->request->get('hour', date('H')));

            $year = intval($this->request->year);
            $month = intval($this->request->month);
            $day = intval($this->request->day);

            $created = mktime($hour, $min, $second, $month, $day, $year) - $this->options->timezone + $this->options->serverTimezone;
        } else if ($this->request->is('cid')) {
            //?????????????????????
            $created = $this->created;
        }

        return $created;
    }

    protected function attach($cid)
    {
        $attachments = $this->request->getArray('attachment');
        if (!empty($attachments)) {
            foreach ($attachments as $key => $attachment) {
                $this->db->query($this->db->update('table.contents')->rows(array('parent' => $cid, 'status' => 'publish',
                'order' => $key + 1))->where('cid = ? AND type = ?', $attachment, 'attachment'));
            }
        }
    }

    protected function unAttach($cid)
    {
        $this->db->query($this->db->update('table.contents')->rows(array('parent' => 0, 'status' => 'publish'))
                ->where('parent = ? AND type = ?', $cid, 'attachment'));
    }

    protected function getPageOffsetQuery($created, $status = NULL)
    {
        return 'page=' . $this->getPageOffset('created', $created, 'post', $status,
        'on' == $this->request->__typecho_all_posts ? 0 : $this->user->uid);
    }

    protected function deleteDraft($cid)
    {
        $this->delete($this->db->sql()->where('cid = ?', $cid));

        /** ?????????????????? */
        $this->setCategories($cid, array(), false, false);

        /** ???????????? */
        $this->setTags($cid, NULL, false, false);
    }

    protected function publish(array $contents)
    {
        /** ????????????, ??????????????????????????????????????? */
        if ($this->user->pass('editor', true)) {
            if (empty($contents['visibility'])) {
                $contents['status'] = 'publish';
            } else if ('password' == $contents['visibility'] || !in_array($contents['visibility'], array('private', 'waiting', 'publish', 'hidden'))) {
                if (empty($contents['password']) || 'password' != $contents['visibility']) {
                    $contents['password'] = '';
                }
                $contents['status'] = 'publish';
            } else {
                $contents['status'] = $contents['visibility'];
                $contents['password'] = '';
            }
        } else {
            $contents['status'] = 'waiting';
            $contents['password'] = '';
        }

        /** ???????????????id */
        $realId = 0;
        
        /** ?????????????????????????????? */
        $isDraftToPublish = ('post_draft' == $this->type);

        $isBeforePublish = ('publish' == $this->status);
        $isAfterPublish = ('publish' == $contents['status']);

        /** ???????????????????????? */
        if ($this->have()) {

            /** ???????????????????????????, ????????????????????? */
            if (!$isDraftToPublish && $this->draft) {
                $this->deleteDraft($this->draft['cid']);
                $this->deleteFields($this->draft['cid']);
            }

            /** ??????????????????????????? */
            if ($this->update($contents, $this->db->sql()->where('cid = ?', $this->cid))) {
                $realId = $this->cid;
            }

        } else {
            /** ????????????????????? */
            $realId = $this->insert($contents);
        }

        if ($realId > 0) {
            /** ???????????? */
            if (array_key_exists('category', $contents)) {
                $this->setCategories($realId, !empty($contents['category']) && is_array($contents['category']) ?
                $contents['category'] : array($this->options->defaultCategory), !$isDraftToPublish && $isBeforePublish, $isAfterPublish);
            }

            /** ???????????? */
            if (array_key_exists('tags', $contents)) {
                $this->setTags($realId, $contents['tags'], !$isDraftToPublish && $isBeforePublish, $isAfterPublish);
            }

            /** ???????????? */
            $this->attach($realId);

            /** ????????????????????? */
            $this->applyFields($this->getFields(), $realId);
        
            $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $realId)->limit(1), array($this, 'push'));
        }
    }

    protected function save(array $contents)
    {
        /** ????????????, ??????????????????????????????????????? */
        if ($this->user->pass('editor', true)) {
            if (empty($contents['visibility'])) {
                $contents['status'] = 'publish';
            } else if ('password' == $contents['visibility'] || !in_array($contents['visibility'], array('private', 'waiting', 'publish', 'hidden'))) {
                if (empty($contents['password']) || 'password' != $contents['visibility']) {
                    $contents['password'] = '';
                }
                $contents['status'] = 'publish';
            } else {
                $contents['status'] = $contents['visibility'];
                $contents['password'] = '';
            }
        } else {
            $contents['status'] = 'waiting';
            $contents['password'] = '';
        }

        /** ???????????????id */
        $realId = 0;

        /** ???????????????????????? */
        if ($this->draft) {
        
            /** ??????????????????????????? */
            if ($this->update($contents, $this->db->sql()->where('cid = ?', $this->draft['cid']))) {
                $realId = $this->draft['cid'];
            }

        } else {
            if ($this->have()) {
                $contents['parent'] = $this->cid;
            }

            /** ????????????????????? */
            $realId = $this->insert($contents);

            if (!$this->have()) {
                $this->db->fetchRow($this->select()->where('table.contents.cid = ?', $realId)->limit(1), array($this, 'push'));
            }
        }

        if ($realId > 0) {
            //$this->db->fetchRow($this->select()->where('table.contents.cid = ?', $realId)->limit(1), array($this, 'push'));

            /** ???????????? */
            if (array_key_exists('category', $contents)) {
                $this->setCategories($realId, !empty($contents['category']) && is_array($contents['category']) ?
                $contents['category'] : array($this->options->defaultCategory), false, false);
            }

            /** ???????????? */
            if (array_key_exists('tags', $contents)) {
                $this->setTags($realId, $contents['tags'], false, false);
            }

            /** ???????????? */
            $this->attach($this->cid);
            
            /** ????????????????????? */
            $this->applyFields($this->getFields(), $realId);
        }
    }

    public function execute()
    {
        /** ?????????????????????????????? */
        $this->user->pass('contributor');

        /** ??????N???i dung b??i vi???t */
        if (!empty($this->request->cid) && 'delete' != $this->request->do) {
            $this->db->fetchRow($this->select()
            ->where('table.contents.type = ? OR table.contents.type = ?', 'post', 'post_draft')
            ->where('table.contents.cid = ?', $this->request->filter('int')->cid)
            ->limit(1), array($this, 'push'));

            if ('post_draft' == $this->type && $this->parent) {
                $this->response->redirect(Typecho_Common::url('write-post.php?cid=' . $this->parent, $this->options->adminUrl));
            }

            if (!$this->have()) {
                throw new Typecho_Widget_Exception(_t('B??i vi???t kh??ng t???n t???i'), 404);
            } else if ($this->have() && !$this->allow('edit')) {
                throw new Typecho_Widget_Exception(_t('Kh??ng c?? quy???n ch???nh s???a'), 403);
            }
        }
    }

    public function filter(array $value)
    {
        if ('post' == $value['type'] || 'page' == $value['type']) {
            $draft = $this->db->fetchRow($this->widget('Widget_Abstract_Contents')->select()
            ->where('table.contents.parent = ? AND table.contents.type = ?',
                $value['cid'], $value['type'] . '_draft')
            ->limit(1));

            if (!empty($draft)) {
                $draft['slug'] = ltrim($draft['slug'], '@');
                $draft['type'] = $value['type'];

                $draft = parent::filter($draft);

                $draft['tags'] = $this->db->fetchAll($this->db
                ->select()->from('table.metas')
                ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid = ?', $draft['cid'])
                ->where('table.metas.type = ?', 'tag'), array($this->widget('Widget_Abstract_Metas'), 'filter'));
                $draft['cid'] = $value['cid'];

                return $draft;
            }
        }

        return parent::filter($value);
    }

    public function date($format = NULL)
    {
        if (isset($this->created)) {
            parent::date($format);
        } else {
            echo date($format, $this->options->time + $this->options->timezone - $this->options->serverTimezone);
        }
    }

    public function allow()
    {
        $permissions = func_get_args();
        $allow = true;

        foreach ($permissions as $permission) {
            $permission = strtolower($permission);

            if ('edit' == $permission) {
                $allow &= ($this->user->pass('editor', true) || $this->authorId == $this->user->uid);
            } else {
                $permission = 'allow' . ucfirst(strtolower($permission));
                $optionPermission = 'default' . ucfirst($permission);
                $allow &= (isset($this->{$permission}) ? $this->{$permission} : $this->options->{$optionPermission});
            }
        }

        return $allow;
    }

    public function getMenuTitle()
    {
        return _t('Ch???nh s???a %s', $this->title);
    }

    public function getDefaultFieldItems()
    {
        $defaultFields = array();
        $configFile = $this->options->themeFile($this->options->theme, 'functions.php');
        $layout = new Typecho_Widget_Helper_Layout();
        $fields = new Typecho_Config();

        if ($this->have()) {
            $fields = $this->fields;
        }

        $this->pluginHandle()->getDefaultFieldItems($layout);

        if (file_exists($configFile)) {
            require_once $configFile;
            
            if (function_exists('themeFields')) {
                themeFields($layout); 
            }

            if (function_exists($this->themeCustomFieldsHook)) {
                call_user_func($this->themeCustomFieldsHook, $layout);
            }
        }

        $items = $layout->getItems(); 
        foreach ($items as $item) {
            if ($item instanceof Typecho_Widget_Helper_Form_Element) {
                $name = $item->input->getAttribute('name');

                $isFieldReadOnly = $this->pluginHandle('Widget_Abstract_Contents')
                    ->trigger($plugged)->isFieldReadOnly($name);
                if ($plugged && $isFieldReadOnly) {
                    continue;
                }

                if (preg_match("/^fields\[(.+)\]$/", $name, $matches)) {
                    $name = $matches[1];
                } else {
                    foreach ($item->inputs as $input) {
                        $input->setAttribute('name', 'fields[' . $name . ']');
                    }
                }

                $item->value($fields->{$name});

                $elements = $item->container->getItems();
                array_shift($elements);
                $div = new Typecho_Widget_Helper_Layout('div');

                foreach ($elements as $el) {
                    $div->addItem($el);
                }
                
                $defaultFields[$name] = array($item->label, $div);
            }
        }

        return $defaultFields;
    }

    public function getFieldItems()
    {
        $fields = array();
        
        if ($this->have()) {
            $defaultFields = $this->getDefaultFieldItems();
            $rows = $this->db->fetchAll($this->db->select()->from('table.fields')
                ->where('cid = ?', $this->cid));

            foreach ($rows as $row) {
                $isFieldReadOnly = $this->pluginHandle('Widget_Abstract_Contents')
                    ->trigger($plugged)->isFieldReadOnly($row['name']);

                if ($plugged && $isFieldReadOnly) {
                    continue;
                }

                if (!isset($defaultFields[$row['name']])) {
                    $fields[] = $row;
                }
            }
        }

        return $fields;
    }

    public function setTags($cid, $tags, $beforeCount = true, $afterCount = true)
    {
        $tags = str_replace('???', ',', $tags);
        $tags = array_unique(array_map('trim', explode(',', $tags)));
        $tags = array_filter($tags, array('Typecho_Validate', 'xssCheck'));

        /** ????????????tag */
        $existTags = Typecho_Common::arrayFlatten($this->db->fetchAll(
        $this->db->select('table.metas.mid')
        ->from('table.metas')
        ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
        ->where('table.relationships.cid = ?', $cid)
        ->where('table.metas.type = ?', 'tag')), 'mid');

        /** ????????????tag */
        if ($existTags) {
            foreach ($existTags as $tag) {
                if (0 == strlen($tag)) {
                    continue;
                }

                $this->db->query($this->db->delete('table.relationships')
                ->where('cid = ?', $cid)
                ->where('mid = ?', $tag));

                if ($beforeCount) {
                    $this->db->query($this->db->update('table.metas')
                    ->expression('count', 'count - 1')
                    ->where('mid = ?', $tag));
                }
            }
        }

        /** ????????????tag */
        $insertTags = $this->widget('Widget_Abstract_Metas')->scanTags($tags);

        /** ??????tag */
        if ($insertTags) {
            foreach ($insertTags as $tag) {
                if (0 == strlen($tag)) {
                    continue;
                }

                $this->db->query($this->db->insert('table.relationships')
                ->rows(array(
                    'mid'  =>   $tag,
                    'cid'  =>   $cid
                )));

                if ($afterCount) {
                    $this->db->query($this->db->update('table.metas')
                    ->expression('count', 'count + 1')
                    ->where('mid = ?', $tag));
                }
            }
        }
    }

    public function setCategories($cid, array $categories, $beforeCount = true, $afterCount = true)
    {
        $categories = array_unique(array_map('trim', $categories));

        /** ????????????category */
        $existCategories = Typecho_Common::arrayFlatten($this->db->fetchAll(
        $this->db->select('table.metas.mid')
        ->from('table.metas')
        ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
        ->where('table.relationships.cid = ?', $cid)
        ->where('table.metas.type = ?', 'category')), 'mid');

        /** ????????????category */
        if ($existCategories) {
            foreach ($existCategories as $category) {
                $this->db->query($this->db->delete('table.relationships')
                ->where('cid = ?', $cid)
                ->where('mid = ?', $category));

                if ($beforeCount) {
                    $this->db->query($this->db->update('table.metas')
                    ->expression('count', 'count - 1')
                    ->where('mid = ?', $category));
                }
            }
        }

        /** ??????category */
        if ($categories) {
            foreach ($categories as $category) {
                /** ????????????????????? */
                if (!$this->db->fetchRow($this->db->select('mid')
                ->from('table.metas')
                ->where('mid = ?', $category)
                ->limit(1))) {
                    continue;
                }

                $this->db->query($this->db->insert('table.relationships')
                ->rows(array(
                    'mid'  =>   $category,
                    'cid'  =>   $cid
                )));

                if ($afterCount) {
                    $this->db->query($this->db->update('table.metas')
                    ->expression('count', 'count + 1')
                    ->where('mid = ?', $category));
                }
            }
        }
    }

    public function writePost()
    {
        $contents = $this->request->from('password', 'allowComment',
            'allowPing', 'allowFeed', 'slug', 'tags', 'text', 'visibility');

        $contents['category'] = $this->request->getArray('category');
        $contents['title'] = $this->request->get('title', _t('T??i li???u kh??ng c?? ti??u ?????'));
        $contents['created'] = $this->getCreated();

        if ($this->request->markdown && $this->options->markdown) {
            $contents['text'] = '<!--markdown-->' . $contents['text'];
        }

        $contents = $this->pluginHandle()->write($contents, $this);

        if ($this->request->is('do=publish')) {
            /** ????????????????????????????????? */
            $contents['type'] = 'post';
            $this->publish($contents);

            // ????????????????????????
            $this->pluginHandle()->finishPublish($contents, $this);

            /** ??????ping */
            $trackback = array_unique(preg_split("/(\r|\n|\r\n)/", trim($this->request->trackback)));
            $this->widget('Widget_Service')->sendPing($this->cid, $trackback);

            /** ?????????????????? */
            $this->widget('Widget_Notice')->set('post' == $this->type ?
            _t('B??i vi???t "<a href="%s">%s</a>" ???? ???????c xu???t b???n', $this->permalink, $this->title) :
            _t('B??i vi???t "%s" ??ang ch??? xem x??t', $this->title), 'success');

            /** ???????????? */
            $this->widget('Widget_Notice')->highlight($this->theId);

            /** ?????????????????? */
            $pageQuery = $this->getPageOffsetQuery($this->created);

            /** ???????????? */
            $this->response->redirect(Typecho_Common::url('manage-posts.php?' . $pageQuery, $this->options->adminUrl));
        } else {
            /** ???????????? */
            $contents['type'] = 'post_draft';
            $this->save($contents);

            // ????????????????????????
            $this->pluginHandle()->finishSave($contents, $this);

            if ($this->request->isAjax()) {
                $created = new Typecho_Date();
                $this->response->throwJson(array(
                    'success'   =>  1,
                    'time'      =>  $created->format('H:i:s A'),
                    'cid'       =>  $this->cid
                ));
            } else {
                /** ?????????????????? */
                $this->widget('Widget_Notice')->set(_t('B???n nh??p "%s" ???? ???????c l??u', $this->title), 'success');

                /** ??????????????? */
                $this->response->redirect(Typecho_Common::url('write-post.php?cid=' . $this->cid, $this->options->adminUrl));
            }
        }
    }

    public function deletePost()
    {
        $posts = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($posts as $post) {
            // ??????????????????
            $this->pluginHandle()->delete($post, $this);

            $condition = $this->db->sql()->where('cid = ?', $post);
            $postObject = $this->db->fetchObject($this->db->select('status', 'type')
                ->from('table.contents')->where('cid = ? AND type = ?', $post, 'post'));

            if ($this->isWriteable($condition) &&
                $postObject &&
                $this->delete($condition)) {

                /** ???????????? */
                $this->setCategories($post, array(), 'publish' == $postObject->status
                    && 'post' == $postObject->type);

                /** ???????????? */
                $this->setTags($post, NULL, 'publish' == $postObject->status
                    && 'post' == $postObject->type);

                /** ???????????? */
                $this->db->query($this->db->delete('table.comments')
                    ->where('cid = ?', $post));

                /** ?????????????????? */
                $this->unAttach($post);

                /** ???????????? */
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?',
                        $post, 'post_draft')
                    ->limit(1));

                /** ????????????????????? */
                $this->deleteFields($post);

                if ($draft) {
                    $this->deleteDraft($draft['cid']);
                    $this->deleteFields($draft['cid']);
                }

                // ????????????????????????
                $this->pluginHandle()->finishDelete($post, $this);

                $deleteCount ++;
            }

            unset($condition);
        }

        // ????????????
        if ($deleteCount > 0) {
            $this->widget('Widget_Abstract_Metas')->clearTags();
        }

        /** ?????????????????? */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('B??i vi???t ???? b??? x??a') : _t('Kh??ng c?? b??i vi???t n??o b??? x??a'),
        $deleteCount > 0 ? 'success' : 'notice');

        /** ??????????????? */
        $this->response->goBack();
    }
    
    public function deletePostDraft()
    {
        $posts = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($posts as $post) {
            /** ???????????? */
            $draft = $this->db->fetchRow($this->db->select('cid')
                ->from('table.contents')
                ->where('table.contents.parent = ? AND table.contents.type = ?',
                    $post, 'post_draft')
                ->limit(1));

            if ($draft) {
                $this->deleteDraft($draft['cid']);
                $this->deleteFields($draft['cid']);
                $deleteCount ++;
            }
        }

        /** ?????????????????? */
        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('B???n nh??p ???? b??? x??a') : _t('Kh??ng c?? b???n nh??p n??o b??? x??a'),
        $deleteCount > 0 ? 'success' : 'notice');
        
        /** ??????????????? */
        $this->response->goBack();
    }

    public function preview()
    {
        $this->response->throwJson($this->markdown($this->request->text));
    }

    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=publish') || $this->request->is('do=save'))->writePost();
        $this->on($this->request->is('do=delete'))->deletePost();
        $this->on($this->request->is('do=deleteDraft'))->deletePostDraft();
        $this->on($this->request->is('do=preview'))->preview();

        $this->response->redirect($this->options->adminUrl);
    }
}

