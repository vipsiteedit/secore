<?php

/**
 * Базовый класс работы с таблицами базы данных
 * @filesource seShopPrice.class.php
 * @copyright EDGESTILE
 */
abstract class seBase
{

  protected $table_id = 0;
  public $data;
  protected $is_find = false;
  protected $datalist;
  protected $where = '';
  protected $ljoin = '';
  protected $rjoin = '';
  protected $ijoin = '';
  protected $select = '';
  protected $orderby = '';
  protected $groupby = '';
  protected $offset = 0;
  protected $limit = 0;
  protected $update = null;
  protected $is_table = false;


  protected $table_name;
  protected $table_alias;
  
  protected $fields = array(); // Имена полей
  protected $fieldalias = array(); // Алиас полей

  protected $crfield = array(); // Корректировщик имени полей

  public function __construct()
  {
    $this->table_id = 0;
    $this->configure();
    return $this;
  }

  public function getTable($name, $alias = '')
  {
    return $this->from($name, $alias);
  }

  public function from($name, $alias = '')
  {
    $this->table_id = 0;
    $this->table_name = $name;
    if ($alias == '') $alias = '__'.$name;
    $this->table_alias = $alias;
    return $this;
  }

  public function fetchOne()
  {
    $this->is_find = false;
    $this->table_id= 0;
   //unset($this->data);
    $this->data = array();
    if (empty($this->select))
    {
      $select = '`' . $this->table_alias . '`.*';
    }
    else
    {
      // Разбираем строку селект
	  $select = join(',', $this->select); 
    }

    if (!empty($this->where))
    {
      $where = ' WHERE ' . $this->where;
    } elseif ($this->table_id > 0)
    {
      $where = " WHERE $table.`id` = '$this->table_id'";
    }

    $sql = "select $select FROM `$this->table_name` `$this->table_alias` " . strval($this->ijoin) . strval($this->ljoin) . strval($this->rjoin) . $where . " LIMIT 1";

    $qr = se_db_query($sql);
    if (!empty($qr))
    {
      $this->data = se_db_fetch_assoc($qr);
      $this->is_find = !empty($this->data);

      if (isset($this->data['id'])){
        $this->table_id = $this->data['id'];
      } else {
        $this->where = '';
      }
      
    } else {
       $this->where = '';
    }
    return $this->data;
  }

  public function isFind()
  {
    return $this->is_find;
  }


  // Возвращаем массив полей
  public function getRow()
  {
    return $this->data;
  }

  public function insert($fields = '')
  {
    $this->table_id = 0;
    $this->data = array();
    $this->update = null;
    $this->is_find = false;
    if (!empty($fields))
    {
      $this->fields = explode(',', str_replace(' ', '', $fields));
    }
    return $this;
  }

  public function update($field, $value = '')
  {
    $this->update = array();
    if (!empty($value))
    {
      $this->update[$field] = $value;
    }
    return $this;
  }

  public function addupdate($field, $value = '')
  {
    if (!empty($value))
    {
      $this->update[$field] = $value;
    }
    return $this;
  }


  public function find($id)
  {
    $this->table_id = $id;
    $this->where($this->table_alias.'.id=?', $id);
    return $this->fetchOne();
  }

  public function addField($fieldname, $type = 'string'){
    if (!isset($this->fields[$fieldname])) {
	    $this->fields[$fieldname] = $type;
    	    if (!se_db_is_field($this->table_name, $fieldname)){
        	    se_db_add_field($this->table_name, $fieldname, $type);
	}
    }
  }

  private function validate()
  {

    foreach ($this->data as $name => $value)
    {
      if (isset($this->fields[$name]) && strpos($this->fields[$name], 'int') !== false)
      {
    	    $this->data[$name] = intval($value);
      } 
      elseif (isset($this->fields[$name]) && strpos($this->fields[$name], 'key') !== false)
      {
        if (intval($value))
    	    $this->data[$name] = intval($value);
    	else $this->data[$name] = 'null';
      } 
	  elseif (isset($this->fields[$name]) && strpos($this->fields[$name], 'str') !== false)
      {
        $this->data[$name] = strval($value);
      } 
	  elseif (isset($this->fields[$name]) && strpos($this->fields[$name], 'float') !== false)
      {
        $this->data[$name] = str_replace(',', '.', $value);
      } 
	  elseif (isset($this->fields[$name]) && strpos($this->fields[$name], 'bool') !== false)
      {
        if ($value > 0)
        {
          $this->data[$name] = 1;
        }
        else
        {
          $this->data[$name] = 0;
        }
      } elseif (isset($this->fields[$name]) && strpos($this->fields[$name], 'datetime') !== false)
      {
        if (preg_match("/[\d]{4}\-[\d]{1,2}\-[\d]{1,2}(\s\d{2}:\d{2}:\d{2})?$/", $value))
          $this->data[$name] = $value;
        else
          unset($this->data[$name]);
      
      } elseif (isset($this->fields[$name]) && strpos($this->fields[$name], 'date') !== false)
      {
        if (preg_match("/[\d]{4}\-[\d]{1,2}\-[\d]{1,2}$/", $value))
          $this->data[$name] = $value;
        else
          unset($this->data[$name]);
      }
      elseif ($this->is_table == false)
      {
        unset($this->data[$name]);
      }

    }
  }

  public function save()
  {
    if (empty($this->update))
    {
      $this->validate();
	  
      if ($this->table_id > 0 || $this->is_find)
      {
	    $this->data['updated_at'] = date('Y-m-d H:i:s', time());
        
        if ($this->table_id) $where = "`id` = " . $this->table_id;
        else $where = $this->where;
        return se_db_perform($this->table_name, $this->data, 'update', $where);
      }
      else
      {
        $this->data['created_at'] = date('Y-m-d H:i:s', time());
        $this->table_id = se_db_perform($this->table_name, $this->data);
        return $this->table_id;
      }
    }
    else
    {
      // update
      if (!empty($this->where))
        $where = ' where ' . $this->where;
      $sql = 'update `' . $this->table_name . '` set ';
      foreach ($this->update as $field => $value)
      {
        $sql .= $field . '=' . $value . ',';
      }
      $sql .= "`updated_at`='" . date('Y-m-d H:i:s', time()) . "'";
      $sql .= ' ' . $where;
      se_db_query($sql);
      $id = se_db_fields_item($this->table_name, $this->where, 'id');
      log_update($this->table_name, $id, 'u');
    }
  }


  public function delete($id = 0)
  {
    if (is_array($id))
    {
      $id = join(',', $id);
    }
    else
      if ($id == 0)
      {
        $id = $this->table_id;
      }
    se_db_delete($this->table_name, "id IN ($id)");
    return $this;
  }

  public function deletelist()
  {
    se_db_delete($this->table_name, $this->where);
    return $this;
  }
  // Выводит список записей
  public function findlist($findtext = '')
  {
    if (is_numeric($findtext))
      $this->where = "`id` = '$findtext'";
    else
      if (!empty($findtext))
        $this->where = $findtext;
      else
      {
        $this->where = '';
      }
      return $this;
  }

  // Выводим число записей в списке
  public function getListCount()
  {
    $count = 0;
	if (!empty($this->where))
      $where = ' WHERE ' . $this->where;
    if ((strval($this->ijoin) . strval($this->ljoin) . strval($this->rjoin)) != '')
        $select = 'COUNT(DISTINCT `'.$this->table_alias.'`.id)';
    else $select = 'COUNT(*)';
    $sql = "SELECT $select FROM `" . $this->table_name . "` `" .$this->table_alias . "` " . 
	strval($this->ijoin) . strval($this->ljoin) . strval($this->rjoin) . $where . ' LIMIT 1';

    $qr = se_db_query($sql);
    if (!empty($qr))
      list($count) = se_db_fetch_row($qr);
    return intval($count); 
  }

  public function getSql()
  {
    $groupby = $orderby = $where = '';
    $this->datalist = array();

    if ($this->offset >= 0 && $this->limit > 0)
    {
       $limitstr = se_db_limit($this->offset, $this->limit);
    }
    else
      $limitstr = '';

 //print_r($this->select);
    if (empty($this->select))
    {
      $select = '`' . trim($this->table_alias) . '`.*';
    }
    else
    {
      $select = join(',', $this->select);
    }

    if (!empty($this->orderby))
      $orderby = ' ORDER BY ' . $this->orderby;

    if (!empty($this->groupby))
      $groupby = ' GROUP BY ' . $this->groupby;

    if (!empty($this->where))
      $where = ' WHERE ' . $this->where;

    //$select = $this->setCov($select);

    return "SELECT $select FROM `" . $this->table_name . '` `' . $this->table_alias . '` ' .strval($this->ijoin) . strval($this->ljoin) . strval($this->rjoin) . $where . $groupby . $orderby . $limitstr;

  }

  public function getList($offset = 0, $limit = 0)
  {
    if ($limit > 0)
    {
      $this->offset = $offset;
      $this->limit = $limit;
    }

    $qr = se_db_query($this->getSQL());
    if (!empty($qr))
      while ($line = se_db_fetch_assoc($qr))
      {
        $this->datalist[] =$line;
      }
    return $this->datalist;
  }


  private function setCov($text)
  {
		
		if (strpos($text, '.')===false)
		{
			//$text = trim(str_replace(array('`','"'), array('',''), $text));
			//$text = preg_replace("/([\w\d_]+)(\sAS\s)([\w\d_]+)/m", "`$1`$2`$3`", $text);
			//$text = '`'.$this->alias . '`.`' . $text.'`';
		} else
		{
			//$text = trim(str_replace(array('`','"'), array('',''), $text));
			//$text = preg_replace("/([\w\d_]+)\.([\w\d_]+)(\sAS\s)([\w\d_]+)/m", "`$1`.`$2`$3`$4`", $text);
			//$text = '`'. $text . '`';
		}
      return $text;
  }

  function __set($name, $value)
  {
    if (!empty($this->fieldalias[$name]))
	{
		$this->data[$this->fieldalias[$name]] = $value;
	} else 
	{
		$this->data[$name] = $value;
	}
  }

  function __get($name)
  {
    	if (isset($this->fieldalias[$name]) && isset($this->data[$this->fieldalias[$name]]))
    	{
    		return $this->data[$this->fieldalias[$name]];
   	}
	   elseif(isset($this->data[$name])) 
  	{
    
   		return $this->data[$name];
   	}
  }

  public function select($select = '')
  {
	$this->select = array();
	if (!empty($select)) 
	{
	    $selects = explode(',', $select); 
	    foreach($selects as $item)
	    {
		$this->select[] = $this->setCov($item);
	    }
	}
    $this->ljoin = $this->rjoin = $this->ijoin = '';
    return $this;
  }

  public function leftjoin($table, $withselect = '')
  {
    if (strpos($withselect, '=') === false)
      $withselect = $withselect . '=' . $table . '.id';
      
    $this->ljoin .= ' LEFT JOIN ' . $table . ' ON (' . $withselect . ' )';
    return $this;
  }

  public function rightjoin($table, $withselect = '')
  {
    //list(,$wtable) = explode('_', $this->table_name);
    //if ($withselect == '') $withselect = $table.'.'.$wtable.'_id = '.$this->table_name.'.id';
    if (strpos($withselect, '=') === false)
      $withselect = $withselect . '=' . $table . '.id';
    $this->rjoin .= ' RIGHT JOIN ' . $table . ' ON (' . $withselect . ')';
    return $this;
  }

  public function innerjoin($table, $withselect = '')
  {
    //list(,$wtable) = explode('_', $this->table_name);
    //if ($withselect == '') $withselect = $table.'.'.$wtable.'_id = '.$this->table_name.'.id';

    if (strpos($withselect, '=') === false)
      $withselect = $withselect . '=' . $table . '.id';
    $this->ijoin .= ' INNER JOIN ' . $table . ' ON (' . $withselect . ' )';
    return $this;
  }

  public function where($where = '', $value = '')
  {
    $this->where = str_replace('?', $value, $where);
    return $this;
  }

  public function addWhere($where, $value = '')
  {
    $this->where = str_replace('?', $value, $where);
    return $this;
  }

  public function andWhere($where, $value = '')
  {
    $this->where .= ' AND ' . str_replace('?', $value, $where);
    return $this;
  }

  public function orWhere($where, $value = '')
  {
    $this->where .= ' OR ' . str_replace('?', $value, $where);
    return $this;
  }

  public function orderby($orderby = 'id', $sort = 0)
  {
    if ($sort == 0)
      $sort = 'ASC';
    else
      $sort = 'DESC';

    $this->orderby = '`'.$orderby .'` ' . $sort;
    return $this;
  }

  public function addorderby($orderby = 'id', $sort = 0)
  {
    if ($sort == 0)
      $sort = 'ASC';
    else
      $sort = 'DESC';

	if (strpos($orderby,'.')===false && strpos($orderby, '`')===false) {
			$orderby = ',`'.$orderby .'`';
	}

    if (empty($this->orderby)){
		$this->orderby = $orderby .' ' . $sort;
    } else {
			$this->orderby .= ','.$orderby .' ' . $sort;
    }

    return $this;
  }

  public function groupby($groupby = 'id')
  {
    $this->groupby = $groupby;
    return $this;
  }

  public function pageNavigator($limit = 30)
  {
    if ($limit > 0)
    {
      $this->limit = $limit;
    }


    $cnrowfull = $this->getListCount();
    $r = '';

    $cnpage = ceil($cnrowfull / $limit);
    if ($cnpage > 1)
    {
      if (empty($L_VARS['get']))
      {
        // выдаем все переменные, переданные в $GET без $remove
        $link = array();
        $remove = array('page', 'sheet','db');
        foreach ($_GET as $k => $v)
          if (!in_array($k, $remove))
            $link[$k] = $k . '=' . $v;
        $link['db'] = 'db='.$this->table_alias;
        $link['sheet'] = 'sheet=';
        $L_VARS['get'] = join('&', $link);
      }
      // -------------------------------

      if (isRequest('sheet') && getRequest('db')==$this->table_alias)
        $sheet = getRequest('sheet', VAR_INT);
      else
        $sheet = 1;

      $this->offset = ($sheet - 1) * $this->limit;


      $r .= '<table border="0" class="seNavigator" cellspacing="0" cellpadding="0">';
      $r .= '<form style="margin:0px" onSubmit="if ((this.elements[0].value)>' . $cnpage . ' || this.elements[0].value < 1) {	return false; }	location.href=\'?' . $L_VARS['get'] . '\'+(this.elements[0].value);	return false;" 
			method="get" enctype="multipart/form-data">';
      //$r .= '<tr><td colspan="9" align="center">Записей: <b>'.$cnrowfull.'</b>; Страниц: <b>'.$cnpage.'</b></td></tr>';
      $r .= "<tr>";
      $r_left = '';
      $r_right = '';
      $cnpw = 11;
      $in = 1;
      $ik = $cnpage;
      if ($cnpage > $cnpw)
      {
        $in = $sheet - floor($cnpw / 2);
        $ik = $sheet + floor($cnpw / 2);
        if ($in <= 1)
        {
          $in = 1;
          $ik = $sheet + ($cnpw - $sheet);
        }

        if ($ik > $cnpage)
        {
          $in = $sheet - (($cnpw - 1) - ($cnpage - $sheet));
          $ik = $cnpage;
        }
        if ($in > 1)
        {
          $in = $in + 3;

          $r_left .= '<td width="20px" align="center" class="pagen"><a href="?' . $L_VARS['get'] . '1">1</a></td>
                            <td width="20px" align="center" class="pagen"><a href="?' . $L_VARS['get'] . '2">2</a></td>';

          $r_left .= '<td width="20px" align="center" class="pagen">...</td>';
        }
        if ($ik < $cnpage)
        {
          $ik = $ik - 3;
          $r_right = '<td width="20px" align="center" class="pagen">...</td>';

          $r_right .= '<td width="20px" align="center" class="pagen"><a href="?' . $L_VARS['get'] . '' . ($cnpage - 1) . '">' . ($cnpage - 1) . '</a></td>';
          $r_right .= '<td width="20px" align="center" class="pagen"><a href="?' . $L_VARS['get'] . '' . $cnpage . '">' . $cnpage . '</a></td>';
        }
      }
      $r .= $r_left;
      for ($i = $in; $i <= $ik; $i++)
      {
        if ($i == $sheet)
          $r .= '<td width="20px" align="center">
                       <input class="pagenactive" title="' . $helptitle . '"
                        name="sheet" type="text" size="2" maxlength="' . strlen($cnpage) . '" value="' . $i . '"></td>';
        else
          $r .= '<td width="20px" align="center" class="pagen"><a href=\'?' . $L_VARS['get'] . '' . $i . '\'>' . $i . '</a></td>';
      }
      $r .= $r_right;
      $r .= "</tr>";
      $r .= "</form></table>";
    }
    return $r;
  }

}

?>