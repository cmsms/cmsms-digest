<?php

    /*
     * Subscriber class
     *
     * Copyrights: Jean-Christophe Cuvelier - 2011
     *
     */

    class DSubscriber
    {
        const DB_NAME = 'module_digest_subscribers';

        // Vars

        protected $id;
        protected $vars = array();
        protected $is_modified;
        protected $subscription; // UNIQUE
        protected $subscriptions;
        protected $user;

        protected static $fields = array(
            'user_id'         => array('name' => 'user_id', 'ado' => 'I'),
            'subscription_id' => array('name' => 'subscription_id', 'ado' => 'I'),
            'period'          => array('name' => 'period', 'ado' => 'C(255)'),
            'timestamp'       => array('name' => 'timestamp', 'ado' => 'I'),
        );

        public static $periods = array(
            'Never'   => 'Never',
            // 'Directly' => 'Directly', // TODO: TO IMPLEMENT LATER --> Make it an event
            'Daily'   => 'Daily',
            'Weekly'  => 'Weekly',
            'Monthly' => 'Monthly'
        );

        public static $week = array(
            '1' => 'Monday',
            '2' => 'Tuesday',
            '3' => 'Wednesday',
            '4' => 'Thursday',
            '5' => 'Friday',
            '6' => 'Saturday',
            '7' => 'Sunday'
        );

        public static $month = array(
            'first' => 'First day of the month',
            'last'  => 'Last day of the month',
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
                return NULL;
            }
        }

        public function getSubscription()
        {
            if(empty($this->subscription)) $this->subscription = DSubscription::retrieveByPk($this->subscription_id);
            return $this->subscription;
        }

        public function getSubscriptions($period = null)
        {
            if(empty($this->subscriptions)) $this->subscriptions = $this->retrieveSubscriptions();
            if(isset($period)) if(isset($this->subscriptions[$period])) return $this->subscriptions[$period];

            $subscriptions = array();
            foreach($this->subscriptions as $subs)
            {
                if(is_array($subs))
                {
                    $subscriptions += $subs;
                }
            }
            return $subscriptions;
        }

        private function retrieveSubscriptions($period = null)
        {
            $where = array();
            if(!is_null($period)) $where['period'] = $period;
            $subscriber = self::doSelect(array('where' => $where));
            $subscribtions = array();
            foreach($subscriber as $subscription)
            {
                $subscribtions[$subscription->period][$subscription->subscription_id] = $subscription->getSubscription();
            }
            return $subscribtions;
        }

        public function injectSubscription(DSubscription $subscription, $period)
        {
            $this->subscriptions[$period][$subscription->getId()] = $subscription;
        }

        public function hasContent($period = null)
        {
            $has_content = false;
            foreach($this->subscriptions as $subscription_period => $subscriptions)
            {
                if(is_null($period) || ($period == $subscription_period))
                {
                    foreach($subscriptions as $subscription)
                    {
                        if($subscription->hasContent())
                        {
                            $has_content = true;
                        }
                    }
                }
            }
            return $has_content;
        }

        public function getContent($period = null)
        {
            $content = array();

            foreach($this->subscriptions as $subscription_period => $subscriptions)
            {
                if(is_null($period) || ($period == $subscription_period))
                {
                    foreach($subscriptions as $subscription)
                    {
                        /** @var $subscription DSubscription */
                        if($subscription->hasContent($period))
                        {
                            $content[$subscription->getId()]['title'] = $subscription->title;
                            $content[$subscription->getId()]['module_name'] = $subscription->module_name;
                            $content[$subscription->getId()]['announces'] = $subscription->getAnnounces($period);
                            $content[$subscription->getId()]['content'] = $subscription->getContent($period);
                        }
                    }
                }
            }

            return $content;
        }

        /**
         * @return CMSUser|null
         */
        public function getUser()
        {
            if(empty($this->user)) $this->user = CMSUser::retrieveByPk($this->user_id);
            return $this->user;
        }

        public function setUser(CMSUser $user)
        {
            $this->user = $user;
            if($user->getId() != '')
            {
                $this->user_id = $user->getId();
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
                $set[$field['name']] = ' ' . $field['name'] . ' = ?';
                $get[$field['name']] = $this->$field['name'];
            }

            $array = $this->prepareSaveCustom(array('set' => $set, 'get' => $get));

            return $array;
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

        /**
         * @param array $params Filters and options
         *
         * @return DSubscriber[]
         */

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
                $query .= ' ORDER BY user_id';
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

            $items = self::cleanSubscribers($items);

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

        protected function prepareSaveCustom($array)
        {
            $array['get']['timestamp'] = time();

            return $array;
        }

        public static function getSubscriptionsForUser($user_id)
        {
            $selection     = self::doSelect(array(
                'where' => array(
                    'user_id' => $user_id
                )
            ));
            $subscriptions = array();
            foreach ($selection as $subscription) {
                $subscriptions[$subscription->subscription_id] = $subscription;
            }

            return $subscriptions;
        }

        public static function updateSubscriptions($user_id, $subscriptions)
        {
            $to_delete = self::doSelect(array('where' => array('user_id' => $user_id)));
            foreach ($subscriptions as $subscription_id => $period) {
                if ($period != 'Never') {
                    $subscriber                  = new self();
                    $subscriber->user_id         = $user_id;
                    $subscriber->subscription_id = $subscription_id;
                    $subscriber->period          = $period;
                    $subscriber->save();
                }
            }
            foreach ($to_delete as $item) {
                $item->delete();
            }
        }



        public static function getSubscriptionsByUsers($query_params = array())
        {
            if (is_array($query_params['order_by'])) {
                $query_params['order_by'][] = 'user_id ASC';
            } else {
                $query_params['order_by'] .= ', user_id ASC';
            }
            $subscriptions = self::doSelect($query_params);
            $subscribers   = array();

            foreach ($subscriptions as $subscription) {
                if (!isset($subscribers[$subscription->user_id])) {
                    $subscribers[$subscription->user_id]['user'] = $subscription->getUser()->getAsArray();
                }
                $subscribers[$subscription->user_id]['subscriptions'][$subscription->getId()] = $subscription;
            }

            return $subscribers;
        }

        /**
         * @param $period
         *
         * @return array|null
         * @deprecated
         */

        public static function getDatasForPeriod($period)
        {
            if (in_array($period, self::$periods) && $period != 'None') {
                $entries = self::doSelect(array(
                    'where' => array(
                        'period' => $period
                    )
                ));

                $subscribers = array();
                $subscriptions = array();

                foreach ($entries as $entry) {
                    $subscribers[$entry->user_id]['subscriber']      = $entry;
                    $subscribers[$entry->user_id]['subscriptions'][] = $entry->subscription_id;

                    if (!isset($subscriptions[$entry->subscription_id])) {
                        $subscriptions[$entry->subscription_id] = $entry->getSubscription();
                    }
                }

                return array('subscribers' => $subscribers, 'subscriptions' => $subscriptions);
            }

            return NULL;
        }

        protected static function cleanSubscribers($subscribers)
        {
            $clean = array();
            foreach ($subscribers as $subscriber) {
                if (is_null($subscriber->getUser())) {
                    $subscriber->delete();
                } else {
                    $clean[] = $subscriber;
                }
            }

            return $clean;
        }

        /**
         * @param $period
         *
         * @return int Timestamp in the past from now
         */

        public static function getTimeForPeriod($period)
        {
            switch ($period) {
                case 'Daily':
                    return strtotime('-1 day');
                case 'Weekly':
                    return strtotime('-1 week');
                case 'Monthly':
                    return strtotime('-1 month');
                case 'Yearly':
                    return strtotime('-1 year');
                case 'Centennially':
                    return strtotime('-100 year');  // Yeah, yeah, just in case of. It wont work until 2070 though...
                default:
                    return strtotime('+1 year');    // We should not be here. In case of, we'll give tomorrow date. TCP over time do not exists... yet.
            }
        }

        public static function getPeriodsForSend()
        {
            $periods = array('Daily');
            $digest  = cms_utils::get_module('Digest');
            $weekly  = $digest->GetPreference('send_weekly_on');
            $monthly = $digest->GetPreference('send_monthly_on');
            // Weekly
            if ($weekly == date('N')) {
                $periods[] = 'Weekly';
            }
            // Monthly
            if (
                (($monthly == 'first') && (date('j') == 1))
                OR
                (($monthly == 'last') && (date('j') == date('t')))
            ) {
                $periods[] = 'Monthly';
            }

            $periods[] = 'Monthly';


            return $periods;
        }
    }