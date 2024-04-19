<?php

namespace Booqable\Model;

use Booqable\Helper\Request;

class Property
{
    /**
     * @param $data
     * @return false
     * @throws \Exception
     */
    public static function create($data)
    {
        $properties = Request::post('/properties',  [
            "data" => [
                "type" => "properties",
                "attributes" => $data
            ]
        ]);

        return $properties['data']??false;
    }

    /**
     * @param $id
     * @param $data
     * @return false
     * @throws \Exception
     */
    public static function update($id, $data)
    {
        $properties = Request::put('/properties/'.$id,  [
            "data" => [
                "id"=>$id,
                "type" => "properties",
                "attributes" => $data
            ]
        ]);

        return $properties['data']??false;
    }
}