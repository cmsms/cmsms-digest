<?php
  
  /*
   * Subscriber class
   * 
   * Copyrights: Jean-Christophe Cuvelier - 2011
   * 
   */

class DLog
{
	const DB_NAME  = 'module_digest_logs';
	
	// Vars

	protected $id;
	protected $vars = array();
	protected $is_modified;

  protected static $fields = array(  	
  	'title' => array('name' => 'title', 'ado' => 'C(255)'),
  	'timestamp' => array('name' => 'timestamp', 'ado' => 'I'),
  	'period' => array('name' => 'period', 'ado' => 'C(255)'),
  	'summary' => array('name' => 'summary', 'ado' => 'XL'),
  	'details' => array('name' => 'details', 'ado' => 'XL'),
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
      $this->vars[$var] = $val;
  }

  public function __get($var)
	{
		if(method_exists($this, $var))
		{
			return $this->$var();
		}
	  elseif (array_key_exists($var, $this->vars))
		{
	  	return $this->vars[$var];
	  } 
		else 
		{
			return null;
		}
  }

	public function PopulateFromDb($row)
	{
		  $this->id = $row['id'];
			foreach(self::$fields as $field)
			{
				$this->vars[$field['name']] = $row[$field['name']];
			}
	}


  public function save()
  {
    // Upgrade or Insert ?
    if ($this->id != null)
    {
      $this->update();
    }
    else 
    {     
      $this->insert();
    }   

  }

	protected function prepareSave()
	{
		$set = array();
		$get = array();
		foreach(self::$fields as $field)
		{
			$set[$field['name']] = ' ' . $field['name'] . ' = ?';
			$get[$field['name']] = $this->$field['name'];
		}
		
		$array = $this->prepareSaveCustom(array('set' => $set, 'get' => $get));
		
		return $array;
	}

  protected function update()
  {
    $db = cms_utils::get_db();

    $query = 'UPDATE  ' . cms_db_prefix() .  self::DB_NAME . ' 
		SET ';

		$f = $this->prepareSave();	
		$query .= implode(',',$f['set']);
		// $query .= ' module_name = ?, timestamp = ?, title = ?, announcement = ?';
    $query .= ' WHERE id = ? ';

		$f['get'][] = $this->getId();

    $result = $db->Execute($query,$f['get']);        

    /*FIXME: Test the $db status; */
    
		return true;
  }

  protected function insert()
  {
    $db = cms_utils::get_db();

    $query = 'INSERT INTO ' . cms_db_prefix() .  self::DB_NAME .  ' 
    SET ';
		$f = $this->prepareSave();	
		$query .= implode(',',$f['set']);
    //$query .= ' module_name = ?, timestamp = ?, title = ?, announcement = ?';

		$result = $db->Execute($query,
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
    $params['limit'] = 1;
    $items = self::doSelect($params);
    if ($items)
    {
      return $items[0];
    }
    else 
    {
      return null;
    }   
  }

  public static function doSelect($params = array())
  {
    $db = cms_utils::get_db();

    $query = 'SELECT * FROM ' . cms_db_prefix() . self::DB_NAME;

    $values = array();

   	$fields = array();

    if (isset($params['where']))
    {
      foreach ($params['where'] as $field => $value) 
      {
        $fields[] = $field . ' =  ?';
        $values[] = $value;
      }
    } 
  

		if (isset($params['where_adv']))
		{
			foreach($params['where_adv'] as $field => $value)
			{
				$fields[] = $field . ' ' . $value[1] . ' ?';
				$values[] = $value[0];
			}
		}
		
		if (!empty($fields))
		{	
	  	$query .= ' WHERE ' . implode(' AND ', $fields);
		}

    if(isset($params['order_by']))
    {
    	$query .= ' ORDER BY ';
			if(is_array($params['order_by']))
			{
				$query .= implode(', ' , $params['order_by']);
			}
			else
			{
				$query .= $params['order_by'];
			}
    }
    elseif (!isset($params['group_by']))
    {
    	$query .= ' ORDER BY user_id';
    }

		if (isset($params['group_by']))
		{
			$query .= ' GROUP BY ?';
			$values[] = $params['group_by'];
		}

		if(isset($params['limit']))
		{
			$query .= ' LIMIT '. (int)$params['limit'];
		}

//      var_dump($query);

    $dbresult = $db->Execute($query, $values);

    $items = array();
   	if ($dbresult && $dbresult->RecordCount() > 0)
    {
    	while ($dbresult && $row = $dbresult->FetchRow())
      {
        $item = new self();
        $item->PopulateFromDb($row);
        $items[] = $item;
      }
    }

    return  $items;   
  }

   public function delete()
   {           
   	$db = cms_utils::get_db();
   	$query = 'DELETE FROM '. cms_db_prefix() . self::DB_NAME;
   	$query .= ' WHERE id = ?';
   	$db->Execute($query, array($this->id));   
  }  

	// SPECIFIC LOGIC
	
	protected function prepareSaveCustom($array)
	{
    if ($this->id == null)  $array['get']['timestamp'] = time();
	  return $array;
	}

    /**
     * @return DLog
     */

    public static function getLatestLog()
	{
	  return self::doSelectOne(array('order_by' => 'timestamp DESC', 'where_adv' => array('period' => array('DEMO%', 'NOT LIKE')) ));
	}

    public function getDateTime()
    {
        $start = new DateTime();
        $start->setTimestamp(strtotime($this->timestamp));
    }

	
}