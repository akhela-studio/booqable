<?php

namespace Booqable\Helper;

class Notice
{
    private static $errors = [];

    public static function addError($error) {
        self::$errors[] = $error;
        update_option('booqable_errors', self::$errors);
    }

    public static function displayErrors() {

        $errors = get_option('booqable_errors', false);

        if ( $errors && count($errors) ) {
            echo '<div class="notice notice-error is-dismissible">';
            foreach ($errors as $error) {
                echo "<p>Booqable : $error</p>";
            }
            echo '</div>';
        }
        update_option('booqable_errors', '');
    }

    public static function hasErrors() {
        return !empty(self::$errors);
    }
}
