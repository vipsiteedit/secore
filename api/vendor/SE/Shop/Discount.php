<?php

namespace SE\Shop;

use SE\DB as DB;
use SE\Exception;

class Discount extends Base
{
    protected $tableName = "shop_discounts";
    protected $sortBy = "sort";
    protected $sortOrder = "asc";

    // получить натройки
    protected function getSettingsFetch()
    {
        return array(
            "select" => 'sd.*',
            "left" => array(
                "type" => "inner",
                "table" => 'shop_discount_links sdl',
                "condition" => 'sdl.discount_id = sd.id'
            ),
            "convertingValues" => array(
                "summFrom",
                "summTo"
            )
        );
    }
	
	public function info($id = NULL)
    {
        try {
            $u = new DB('shop_discounts', 'sb');
            $info = $u->getInfo($this->input["id"]);
            $info['imageFile'] = $info['picture'];
            /*if ($info['imageFile']) {
                if (strpos($info['imageFile'], "://") === false) {
                    $info['imageUrl'] = '//' . $this->hostname . "/images/rus/shopaction/" . $info['imageFile'];
                    $info['imageUrlPreview'] = "//{$this->hostname}/lib/image.php?size=64&img=images/rus/shopaction/" . $info['imageFile'];
                } else {
                    $info['imageUrl'] = $info['imageFile'];
                    $info['imageUrlPreview'] = $info['imageFile'];
                }
            }*/
            $this->result = $info;
        } catch (Exception $e) {
            $this->error = "Не удаётся получить информацию о скидке!";
        }
        return $this;
    }

    // добавить информацию
    protected function getAddInfo()
    {
        $result["dateFrom"] = (!empty($this->result["dateFrom"])) ? date('Y-m-d H:i', strtotime($this->result["dateFrom"])) : '';
		$result["dateTo"] = (!empty($this->result["dateTo"])) ? date('Y-m-d H:i', strtotime($this->result["dateTo"])) : '';
		$result["listGroupsProducts"] = $this->getListGroupsProducts($this->result["id"]);
        $result["listProducts"] = $this->getListProducts($this->result["id"]);
        $result['listContacts'] = $this->getListContacts($this->result["id"]);
        $result['listGroupsContacts'] = $this->getListGroupsContacts($this->result["id"]);
        return $result;
    }
	
	public function save($isTransactionMode = true)
	{
		//if (isset($this->input["imageFile"]))
        //        $this->input["picture"] = $this->input["imageFile"];
		$u = new DB('shop_discounts');
		$u->addField('action_price', "int(6)", "0");
		$u->addField('is_action', "int(6)", "0", 1);
		$u->addField('description', "text");
		$u->addField('picture', "varchar(255)");
		$u->addField('seo_header', "varchar(255)");
		$u->addField('seo_keywords', "varchar(255)");
		$u->addField('seo_description', "text");
		$u->addField('page_title', "varchar(255)");
		return parent::save($isTransactionMode);
	}

    // сохранить информацию
    protected function saveAddInfo()
    {
		DB::query("ALTER TABLE `shop_discounts` CHANGE `type_discount` `type_discount` ENUM('percent','absolute','optcorp','opt') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'percent';");
        return $this->saveProducts() && $this->saveGroupsProducts() && $this->saveContacts() && $this->saveGroupsContacts() ;
    }

    // получить список продуктов
    private function getListProducts($id)
    {
        try {
            $u = new DB('shop_discount_links', 'sdl');
            $u->select('sp.id, sp.code, sp.article, sp.name, sp.price, sp.curr');
            $u->innerJoin("shop_price sp", "sdl.id_price = sp.id");
            $u->where("sdl.discount_id = $id");
            $u->groupBy("sp.id");
            return $u->getList();
        } catch (Exception $e) {
            $this->error = "Не удаётся получить список товаров скидки!";
        }
    }

    // получить список групп продуктов
    private function getListGroupsProducts($id)
    {
        try {
            $u = new DB('shop_discount_links', 'sdl');
            $u->select('sg.id, sg.code_gr, sg.name');
            $u->innerJoin("shop_group sg", "sdl.id_group = sg.id");
            $u->where("sdl.discount_id = $id");
            $u->groupBy("sg.id");
            return $u->getList();
        } catch (Exception $e) {
            $this->error = "Не удаётся получить список групп товаров скидки!";
        }
    }

    // получить список контактов
    private function getListContacts($id)
    {
        try {
            $u = new DB('shop_discount_links', 'sdl');
            $u->select('p.id, p.first_name, p.sec_name, p.last_name, p.email');
            $u->innerJoin("person p", "sdl.id_user = p.id");
            $u->where("sdl.discount_id = $id");
            $u->groupBy("p.id");
            return $u->getList();
        } catch (Exception $e) {
            $this->error = "Не удаётся получить список контактов скидки!";
        }
    }

    // получить лист групп контактов
    private function getListGroupsContacts($id)
    {
        try {
            $u = new DB('shop_discount_links', 'sdl');
            $u->select('sg.id, sg.name, sg.title');
            $u->innerJoin("se_group sg", "sdl.id_usergroup = sg.id");
            $u->where("sdl.discount_id = $id");
            $u->groupBy("sg.id");
            return $u->getList();
        } catch (Exception $e) {
            $this->error = "Не удаётся получить список групп контактов скидки!";
        }
    }


    // сохранить продукты
    private function saveProducts()
    {
        if (!isset($this->input["listProducts"]))
            return true;

        try {
            foreach ($this->input["ids"] as $id) {
                //writeLog($this->input["listProducts"]); // сохраняемые в базу значения товара
                DB::saveManyToMany($id, $this->input["listProducts"],
                    array("table" => "shop_discount_links", "key" => "discount_id", "link" => "id_price"));
            }
            // перевод переключателя скидки (в товаре) в вкл
            foreach ($this->input["listProducts"] as $prod) {
                $data = array('id'=> $prod['id'], 'discount'=>'Y');
                $u = new DB('shop_price');
                $u->setValuesFields($data);
                $u->save();
            }


            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить товары для скидки!";
            throw new Exception($this->error);
        }

    }

    // сохранить группы продуктов
    private function saveGroupsProducts()
    {
        if (!isset($this->input["listGroupsProducts"]))
            return true;

        try {
            foreach ($this->input["ids"] as $id)
                DB::saveManyToMany($id, $this->input["listGroupsProducts"],
                    array("table" => "shop_discount_links", "key" => "discount_id", "link" => "id_group"));
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить группы для скидки!";
            throw new Exception($this->error);
        }
    }

    // сохранить контакты
    private function saveContacts()
    {
        if (!isset($this->input["listContacts"]))
            return true;

        try {
            foreach ($this->input["ids"] as $id)
                DB::saveManyToMany($id, $this->input["listContacts"],
                    array("table" => "shop_discount_links", "key" => "discount_id", "link" => "id_user"));
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить контакт для скидки!";
            throw new Exception($this->error);
        }
    }

    // сохранить группы контактов
    private function saveGroupsContacts()
    {
        if (!isset($this->input["listGroupsContacts"]))
            return true;

        try {
            foreach ($this->input["ids"] as $id)
                DB::saveManyToMany($id, $this->input["listGroupsContacts"],
                    array("table" => "shop_discount_links", "key" => "discount_id", "link" => "id_usergroup"));
            return true;
        } catch (Exception $e) {
            $this->error = "Не удаётся сохранить группы контакт для скидки!";
            throw new Exception($this->error);
        }
    }



}
