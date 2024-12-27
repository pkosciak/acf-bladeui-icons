<?php

namespace pkosciak\ACFBladeUIIcons\Models;

use pkosciak\ACFBladeUIIcons\Fields\ACFBladeUIIconsField;
use Roots\Acorn\Application;

class ACFBladeUIIconsModel
{
    /**
     * The application instance.
     *
     * @var \Roots\Acorn\Application
     */
    protected $app;

    /**
     * Create a new Example instance.
     *
     * @param  \Roots\Acorn\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        if ( ! function_exists( 'acf_register_field_type' ) ) {
            return;
        }

        acf_register_field_type( ACFBladeUIIconsField::class);

        add_action('acf/input/admin_footer', function(){
            ?>
            <script>
                jQuery(document).ready(function($){
                    if (typeof acf == 'undefined') { return; }

                    acf.add_filter('select2_ajax_data', function( data, args, $input, $field, instance ){
                        if(args.field.data?.bladeui){
                            data['action'] = 'acf/fields/bladeui_icon/query';
                        }
                        return data;
                    });

                    acf.add_filter('select2_args', function(args) {
                        args.templateSelection = function(selection) {
                            let $selection = jQuery('<span class="acf-selection"></span>');

                            $selection.html(acf.escHtml(selection.text));
                            $selection.data('element', selection.element);

                            return $selection;
                        }

                        args.templateResult = function(selection) {
                            let $selection = jQuery('<span class="acf-selection"></span>');

                            $selection.html(acf.escHtml(selection.text));
                            $selection.data('element', selection.element);

                            return $selection;
                        }

                        return args;
                    });
                });
            </script>
            <?php
        });

        add_filter('acf/load_field/type=bladeui_icon', function ( $field ) {
            $field['wrapper']['data-type'] = 'select';
            return $field;
        });
    }
}
