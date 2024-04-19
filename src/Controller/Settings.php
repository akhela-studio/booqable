<?php

namespace Booqable\Controller;

use Booqable\Helper\Options;

class Settings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    private $fields;
    private $connected=false;
    private $configured=false;

    /**
     * Start up
     */
    public function __construct()
    {
        $this->options = Options::get();

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
    }

    public function getFields(){
        
        $this->fields = [
            'enable'=>[
                'label'=>__('Enable features', 'booqable'),
                'fields'=>[
                ]
            ]
        ];

        if( !$this->configured || !$this->connected){

            $this->fields = array_merge(['booqable'=>[
                'label'=>__('Booqable app', 'booqable'),
                'fields'=>[
                    'employee-id'=>['label'=>__('Employee id', 'booqable'), 'type'=>'text'],
                    'company-id'=>['label'=>__('Company id', 'booqable'), 'type'=>'text'],
                    'single-use-token'=>['label'=>__('Single use Token RS256', 'booqable'), 'type'=>'text'],
                    'token'=>['label'=>__('Token', 'booqable'), 'type'=>'text'],
                    'private-key'=>['label'=>__('Private key', 'booqable'), 'type'=>'textarea'],
                    'domain'=>['label'=>__('Domain', 'booqable'), 'type'=>'text', 'placeholder'=>'mybrand.booqable.com'],
                ]
            ]], $this->fields);
        }
    }

    /**
     * Add options page
     */
    public function admin_menu()
    {
        // This page will be under "Settings"
        add_options_page(
            __('Settings Admin', 'booqable'),
            __('Booqable', 'booqable'),
            'manage_options',
            'booqable',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        ?>
        <div class="wrap <?=$this->connected?'connected':'no-connected'?>">
            <h1><?=__('Booqable', 'booqable')?></h1>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'booqable' );
                do_settings_sections( 'booqable-admin' );
                submit_button(__('Save'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function admin_init()
    {
        global $pagenow;

        if( ($_GET['page']??'') !== 'booqable' && $pagenow != 'options.php' )
            return;

        $this->connected = \Booqable::isConnected();
        $this->configured = \Booqable::isConfigured();

        $this->getFields();

        register_setting(
            'booqable', // Option group
            'booqable', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section('booqable', $this->connected?__('Connected', 'booqable'):__('Connect', 'booqable'), function(){

            if( $this->connected )
                echo __('Installed using partner app. <a href="?page=booqable&disconnect" style="color:#d63638">[disconnect]</a>', 'booqable');
            elseif( $this->configured )
                echo __('Modify your <a href="https://help.shopify.com/en/manual/apps/private-apps" target="_blank">Booqable credentials</a> below.', 'booqable');
            else
                echo __('Enter your <a href="https://help.booqable.com/en/articles/4325485-how-to-create-an-api-key" target="_blank">Booqable credentials</a> below.', 'booqable');

        },'booqable-admin');

        foreach ($this->fields as $section=>$data){

            add_settings_section( 'booqable_'.$section, $data['label'], function() use ($data, $section){

                foreach ($data['fields'] as $key=>$field){

                    if( $field['type'] == 'hidden' ){

                        $name = ($field['namespace']??true) ? 'booqable['.$key.']' : $key;
                        $value = $field['value'] ?? (($field['namespace']??true) ? $this->options[$key]??'' : get_option($key));

                        echo '<input type="hidden" name="'.$name.'" value="'.$value.'"/>';
                    }
                    else{

                        add_settings_field('booqable_'.$key, __($field['label']), function() use($key, $field)
                        {
                            $name = ($field['namespace']??true) ? 'booqable['.$key.']' : $key;
                            $value = $field['value'] ?? (($field['namespace']??true) ? $this->options[$key]??'' : get_option($key));

                            if( $field['type'] == 'checkbox' ){

                                echo '<input type="checkbox" name="'.$name.'" '.(($field['required']??false)?'required':'').' value="1" '.($value?'checked':'').'/>';
                            }
                            elseif( $field['type'] == 'select' ){

                                echo '<select name="'.$name.'" '.(($field['required']??false)?'required':'').'>';
                                foreach ($field['options'] as $option)
                                    echo '<option value="'.$option['value'].'" '.($option['value']==$value?'selected':'').'>'.$option['name'].'</option>';

                                echo '</select>';
                            }
                            elseif($field['type'] == 'password'){

                                printf('<input type="password" placeholder="'.($field['placeholder']??'').'" '.($field['read_only']??false?'readonly':'').' name="'.$name.'" '.($field['required']??false?'required':'').' value="%s"/>', esc_attr($value));
                            }
                            elseif( $field['type'] == 'textarea'){

                                printf('<textarea placeholder="'.($field['placeholder']??'').'" '.($field['read_only']??false?'readonly':'').' name="'.$name.'" '.($field['required']??false?'required':'').'>%s</textarea>', esc_attr($value));
                            }
                            else{

                                printf('<input type="'.$field['type'].'" placeholder="'.($field['placeholder']??'').'" '.($field['read_only']??false?'readonly':'').' name="'.$name.'" '.($field['required']??false?'required':'').' value="%s"/>', esc_attr($value));
                            }
                        },'booqable-admin','booqable_'.$section);
                    }
                }
            },'booqable-admin');
        }
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @return array
     */
    public function sanitize( $input )
    {
        $new_input = array();

        foreach ($this->fields as $section=>$data){

            foreach ($data['fields'] as $key=>$field){

                if( isset( $input[$key] ) ){

                    if( $key == 'token' && empty($input[$key]) )
                        $input[$key] = $this->options[$key];

                    if( $key == 'single-use-token' && empty($input[$key]) )
                        $input[$key] = $this->options[$key];

                    $new_input[$key] = sanitize_text_field( $input[$key] );
                }
            }
        }

        return $new_input;
    }
}
