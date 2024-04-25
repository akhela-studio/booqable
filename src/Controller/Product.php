<?php

namespace Booqable\Controller;

use Booqable\Helper\Notice;
use Booqable\Helper\Options;
use Booqable\Model\ProductGroup;

class Product
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    public static function get_group_id($post_id)
    {
        return get_post_meta($post_id, 'booqable_product_group_id', true);
    }

    public static function set_group_id($post_id, $booqable_id)
    {
        return update_post_meta($post_id, 'booqable_product_group_id', $booqable_id);
    }

    public static function get_id($post_id)
    {
        return get_post_meta($post_id, 'booqable_product_id', true);
    }

    public static function set_id($post_id, $booqable_id)
    {
        return update_post_meta($post_id, 'booqable_product_id', $booqable_id);
    }

    public static function get_slug($post_id)
    {
        return get_post_meta($post_id, 'booqable_product_slug', true);
    }

    public static function set_slug($post_id, $booqable_slug)
    {
        return update_post_meta($post_id, 'booqable_product_slug', $booqable_slug);
    }

    /**
     * Add edit in Booqable link
     *
     * @param \WP_Post $post
     * @return void
     */
    public function booqable_meta_box_callback($post){

        $booqable_product_group_id = self::get_group_id($post->ID);

        echo '<div class="booqable-meta-box">';

        if( $booqable_product_group_id ){

            echo '🟢 '.__('Connected', 'booqable');
            echo '<a class="button button-shopify" target="_blank" href="https://'.$this->options['domain'].'/product_groups/'.$booqable_product_group_id.'">'.__('View in Booqable', 'booqable').'</a>';
        }
        else{

            echo '🔴 '.__('Disconnected', 'booqable');
        }

        echo '</div>';
    }

    /**
     * @return void
     */
    public function add_booqable_meta_box(){

        add_meta_box(
            'booqable_status',
            __( 'Booqable', 'booqable' ),
            [$this, 'booqable_meta_box_callback'],
            'product',
            'side'
        );
    }

    /**
     * @param $post_id
     * @param \WP_Post $post
     * @param $update
     * @return void
     * @throws \Exception
     */
    public function save_product($post_id, $post, $update){

        if( !$update || wp_is_post_revision( $post_id ) || (defined( 'DOING_AUTOSAVE' ) and DOING_AUTOSAVE) )
            return;

        if( $post->post_type !== 'product' )
            return;

        $booqable_product_group_id = self::get_group_id($post_id);
        $booqable_thumbnail_id = get_post_meta($post_id, 'booqable_thumbnail_id', true);

        $categories = get_the_terms($post_id, 'product_cat');
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        $brands = get_the_terms($post_id, 'brand');
        $sizes = get_the_terms($post_id, 'product_size');
        $photo_base64 = '';

        $brand_name = implode(', ', wp_list_pluck($brands, 'name'));

        if( $thumbnail = wp_get_attachment_image_src( $thumbnail_id ) ) {

            $uploads = wp_upload_dir();
            $thumbnail = str_replace($uploads['baseurl'], $uploads['basedir'], $thumbnail[0]);

            $photo_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($thumbnail));
        }


        $data = [
            "name" => $brand_name.' - '.$post->post_title,
            "extra_information" => strip_tags($post->post_content),
            "sku" => get_field('reference', $post_id),
            "product_type" => "rental",
            "tracking_type" => "bulk",
            "trackable" => true,
            "has_variations" => false,
            "price_type" => "none",
            "price_period" => "day",
            "tag_list" => wp_list_pluck($categories, 'name'),
        ];

        if( $booqable_thumbnail_id != $thumbnail_id && $photo_base64)
            $data["photo_base64"] = $photo_base64;

        if( $brands && count($brands) ){

            $data["properties_attributes"][] = [
                "identifier"=>'brand',
                "value"=> implode(', ', wp_list_pluck($brands, 'name'))
            ];
        }

        if( $sizes && count($sizes) ){

            $data["properties_attributes"][] = [
                "identifier"=>'size',
                "value"=> implode(', ', wp_list_pluck($sizes, 'name'))
            ];
        }

        foreach (["price", "dimensions"] as $field) {

            $value = get_field($field, $post_id);

            if( $field == 'price' )
                $value .= '€';

            $data["properties_attributes"][] = [
                "identifier"=>$field,
                "value"=> $value
            ];
        }

        try {

            if( $booqable_product_group_id ){

                $product_group = ProductGroup::update($booqable_product_group_id, $data);

                if( $product_group['attributes']['archived'] ){

                    if( $photo_base64)
                        $data["photo_base64"] = $photo_base64;

                    $product_group = ProductGroup::create($data);
                }
            }
            else{

                $product_group = ProductGroup::create($data);
            }

            $booqable_product_group_id = $product_group['id'];

            $product = ProductGroup::get($booqable_product_group_id);

            if( $product_id = $product['id']??false ){

                self::set_id($post_id, $product_id);
                self::set_group_id($post_id, $booqable_product_group_id);
                self::set_slug($post_id, $product['slug']);

                update_post_meta($post_id, 'booqable_thumbnail_id', $thumbnail_id);
            }
            else{

                Notice::addError('Unable to get product');
            }
        }
        catch (\Throwable $t){

            Notice::addError($t->getMessage());
        }
    }

    public function manage_product_posts_custom_column( $column, $post_id )
    {
        if( $column == 'booqable' ) {

            $booqable_product_group_id = self::get_group_id($post_id);

            if( $booqable_product_group_id )
                echo '<a target="_blank" href="https://'.$this->options['domain'].'/product_groups/'.$booqable_product_group_id.'" title="'.__('Connected', 'booqable').'">🟢</a>';
            else
                echo '<a title="'.__('Disconnected', 'booqable').'">🔴</a>';
        }
    }

    public function manage_product_posts_columns($columns)
    {
        $columns['booqable'] = 'Booqable';

        return $columns;
    }

    /**
     * Start up
     */
    public function __construct()
    {
        $this->options = Options::get();

        add_filter ( 'manage_product_posts_columns', [$this, 'manage_product_posts_columns']);
        add_action ( 'manage_product_posts_custom_column', [$this, 'manage_product_posts_custom_column'], 10, 2 );

        add_action( 'add_meta_boxes', [$this, 'add_booqable_meta_box'] );
        add_action( 'save_post', [$this, 'save_product'], 10, 3 );
    }
}
