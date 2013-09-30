<?php

    /*
     * Subscription class
     *
     * Copyrights: Jean-Christophe Cuvelier - 2011
     *
     */

    class DSubscription
    {
        const DB_NAME = 'module_digest_subscriptions';

        // Vars

        protected $id;
        protected $vars = array();
        protected $is_modified;

        protected static $fields = array(
            'title'       => array('name' => 'title', 'ado' => 'C(255)'),
            'module_name' => array('name' => 'module_name', 'ado' => 'C(255)'),
            'params'      => array('name' => 'params', 'ado' => 'C(255)'),
            'template'    => array('name' => 'template', 'ado' => 'C(255)'),
        );

        public function __toString()
        {
            return (string)$this->title;
        }

        public function getId()
        {
            return $this->id;
        }

        private function setId($id)
        {
            $this->id = $id;
        }

        public function __set($var, $val)
        {
            $this->is_modified = true;
            $this->vars[$var]  = $val;
        }

        public function __get($var)
        {
            if (method_exists($this, $var)) {
                return $this->$var();
            } elseif (array_key_exists($var, $this->vars)) {
                return $this->vars[$var];
            } else {
                return false;
            }
        }

        public function PopulateFromDb($row)
        {
            $this->id = $row['id'];
            foreach (self::$fields as $field) {
                $this->vars[$field['name']] = $row[$field['name']];
            }
        }


        public function save()
        {
            // Upgrade or Insert ?
            if ($this->id != NULL) {
                $this->update();
            } else {
                $this->insert();
            }

        }

        protected function prepareSave()
        {
            $set = array();
            $get = array();
            foreach (self::$fields as $field) {
                $set[] = ' ' . $field['name'] . ' = ?';
                $get[] = $this->$field['name'];
            }

            return array('set' => $set, 'get' => $get);
        }

        protected function update()
        {
            $db = cms_utils::get_db();

            $query = 'UPDATE  ' . cms_db_prefix() . self::DB_NAME . '
		SET ';

            $f = $this->prepareSave();
            $query .= implode(',', $f['set']);
            // $query .= ' module_name = ?, timestamp = ?, title = ?, announcement = ?';
            $query .= ' WHERE id = ? ';

            $f['get'][] = $this->getId();

            $result = $db->Execute($query, $f['get']);

            /*FIXME: Test the $db status; */

            return true;
        }

        protected function insert()
        {
            $db = cms_utils::get_db();

            $query = 'INSERT INTO ' . cms_db_prefix() . self::DB_NAME . '
    SET ';
            $f     = $this->prepareSave();
            $query .= implode(',', $f['set']);
            //$query .= ' module_name = ?, timestamp = ?, title = ?, announcement = ?';

            $result   = $db->Execute($query,
                $f['get']
            // array(
            //    $this->getModuleName(), $this->getTimestamp(), $this->getTitle(), $this->getAnnouncement()
            // )
            );
            $this->id = $db->Insert_ID();

            return true;
        }

        public static function retrieveByPk($id)
        {
            return self::doSelectOne(array('where' => array('id' => $id)));
        }

        public static function doSelectOne($params = array())
        {
            $items = self::doSelect($params);
            if ($items) {
                return $items[0];
            } else {
                return NULL;
            }
        }

        public static function doSelect($params = array())
        {
            $db = cms_utils::get_db();

            $query = 'SELECT * FROM ' . cms_db_prefix() . self::DB_NAME;

            $values = array();

            $fields = array();

            if (isset($params['where'])) {
                foreach ($params['where'] as $field => $value) {
                    $fields[] = $field . ' =  ?';
                    $values[] = $value;
                }
            }


            if (isset($params['where_adv'])) {
                foreach ($params['where_adv'] as $field => $value) {
                    $fields[] = $field . ' ' . $value[1] . ' ?';
                    $values[] = $value[0];
                }
            }

            if (!empty($fields)) {
                $query .= ' WHERE ' . implode(' AND ', $fields);
            }

            if (isset($params['order_by'])) {
                $query .= ' ORDER BY ';
                if (is_array($params['order_by'])) {
                    $query .= implode(', ', $params['order_by']);
                } else {
                    $query .= $params['order_by'];
                }
            } elseif (!isset($params['group_by'])) {
                $query .= ' ORDER BY module_name';
            }

            if (isset($params['group_by'])) {
                $query .= ' GROUP BY ?';
                $values[] = $params['group_by'];
            }

            if (isset($params['limit'])) {
                $query .= ' LIMIT ' . (int)$params['limit'];
            }

            $dbresult = $db->Execute($query, $values);

            $items = array();
            if ($dbresult && $dbresult->RecordCount() > 0) {
                while ($dbresult && $row = $dbresult->FetchRow()) {
                    $item = new self();
                    $item->PopulateFromDb($row);
                    $items[] = $item;
                }
            }

            return $items;
        }

        public function delete()
        {
            $db    = cms_utils::get_db();
            $query = 'DELETE FROM ' . cms_db_prefix() . self::DB_NAME;
            $query .= ' WHERE id = ?';
            $db->Execute($query, array($this->id));
        }

        // SPECIFIC LOGIC

        public static function getCompatibleModules()
        {
            $compatible_modules = array();

            $modules = cmsms()->GetModuleOperations()->get_modules_with_capability('digest_export');
            foreach ($modules as $module_name) {
                $module                                              = cms_utils::get_module($module_name);
                $compatible_modules[$module->GetName()]['name']      = $module->GetFriendlyName();
                $compatible_modules[$module->GetName()]['templates'] = $module->ListTemplates();
            }

            return $compatible_modules;
        }

        public static function getModulesTemplates($modules)
        {
            $templates = array();
            foreach ($modules as $module => $name) {
                $templates[$module] = cms_utils::get_module('Digest')->ListTemplates($module);
            }

            return $templates;
        }

        public static function getTimeForPeriod($period)
        {
            return DSubscriber::getTimeForPeriod($period);
        }

        public function getParamsAsArray()
        {
            $params = array();

            $xml = simplexml_load_string('<vars><params ' . $this->params . ' /></vars>');

            if (isset($xml->params)) {
                foreach ($xml->params[0]->attributes() as $param => $value) {
                    $params[$param] = '' . $value;
                }
            }

            return $params;
        }

        /**
         * Check if the subscription have content for a given period
         *
         * @param $period
         *
         * @return bool
         */

        public function haveContent($period)
        {
            $content = $this->getContent($period);
            $content .= $this->getAnnounces($period);

            return (bool)strlen($content);
        }

        public function getContent($period)
        {
            if (!$this->content) {
                // echo 'Fetch content for ' . $this->module_name.'<br/>';
                $module             = cms_utils::get_module($this->module_name);
                $params             = $this->getParamsAsArray();
                $params['template'] = $this->template;
                $this->content      = $module->Digest(DSubscriber::getTimeForPeriod($period), $params);
            }

            return $this->content;
        }

        public function getAnnounces($period)
        {
            // TODO: Implement get Announces

            return NULL;
        }

        public function getModuleItemsForDirectUpdate()
        {
            $module = cms_utils::get_module($this->module_name);
            $params = $this->getParamsAsArray();

            if (method_exists($module, 'GetObjectName')) {
                $module_object = $module->GetObjectName();
                $c             = new MCFCriteria();
                $c->add('published', 1);
                $c->add('send_update_immediately', 1);
                // $module_object::buildFrontendFilters($c, $params);
                $mod = new $module_object();
                $mod->buildFrontendFilters($c, $params);
                // $items = $module_object::doSelect($c);
                $items = $mod->doSelect($c);

                return $items;
            }

            return NULL;
        }

        public function getSubscribers($period)
        {
            return DSubscriber::doSelect(array(
                'where' => array(
                    'subscription_id' => $this->getId(),
                    'period'          => $period
                )
            ));
        }

        /**
         * @param $subscriptions DSubscription[]
         * @return array
         */

        public static function getIds($subscriptions)
        {
            $ids = array();
            foreach($subscriptions as $subscription)
            {
                $ids[] = $subscription->getId();
            }
            return $ids;
        }
    }