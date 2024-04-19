<?php

namespace Booqable\Controller;

use Booqable\Helper\Options;
use Booqable\Model\ProductGroup;

class Editor
{
    public function admin_head()
    {
        echo '<link rel="stylesheet" href="'.BOOQABLE_PLUGIN_URL.'/public/admin.css?v='.BOOQABLE_PLUGIN_VERSION.'">';
    }

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_head', [$this, 'admin_head']);
    }
}
