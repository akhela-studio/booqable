<?php

namespace Booqable\Helper;

class Options
{
	public static $data;

	public static function setLastUpdate($type, $date){

		$last_update = Options::get('last_update');

		if( !is_array($last_update) )
			$last_update =[];

		$last_update[$type] = $date->getTimestamp();

		Options::set('last_update', $last_update);
	}

	public static function getLastUpdate($type=false){

		$last_update = Options::get('last_update');

		if( !$type )
			return $last_update;

		return $last_update[$type]??0;
	}

	public static function set($option, $value){

        $data = self::get();
        $data[$option] = $value;

		return update_option('booqable', $data);
	}

	public static function delete($option){

        $data = self::get();
        unset($data[$option]);

        return update_option('booqable', $data);
	}

    /**
     * @param $option
     * @param $force
     * @return false|array
     */
    public static function get($option=false, $force=false){

        if( !self::$data || $force ){

            $data = get_option('booqable');

            self::$data = $data;
        }

		if( $option ){

			return self::$data[$option]??false;
		}
		else{

			return self::$data;
		}
	}
}
