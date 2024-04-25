<?php

namespace Booqable\Model;

use Booqable\Helper\Request;

class ProductGroup
{
    /**
     * @param $data
     * @return false
     * @throws \Exception
     */
    public static function create($data)
    {
        $product_group = Request::post('/product_groups',  [
            "data" => [
                "type" => "product_groups",
                "attributes" => $data
            ]
        ]);

        return $product_group['data']??false;
    }

    /**
     * @param $id
     * @param $data
     * @return false
     * @throws \Exception
     */
    public static function update($id, $data)
    {
        $product_group = Request::put('/product_groups/'.$id,  [
            "data" => [
                "id" => $id,
                "type" => "product_groups",
                "attributes" => $data
            ]
        ]);

        return $product_group['data']??false;
    }

    /**
     * @param $id
     * @param array $fields
     * @return false
     * @throws \Exception
     */
    public static function get($id, $fields=[])
    {
        $product_group = Request::get('/product_groups/'.$id, $fields, 1);

        return $product_group['product_group']??($product_group['product_groups'][0]??false);
    }
}