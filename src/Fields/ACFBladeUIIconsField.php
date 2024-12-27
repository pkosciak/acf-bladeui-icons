<?php
/**
* Defines the custom field type class.
*/

namespace pkosciak\ACFBladeUIIcons\Fields;

use BladeUI\Icons\Factory;
use BladeUI\Icons\IconsManifest;

if (!defined('ABSPATH')) {
    exit;
}

class ACFBladeUIIconsField extends \acf_field {

    public array $icon_sets;
    /**
     * This function will setup the field type data
     *
     * @type    function
     * @date    5/03/2014
     * @since   5.0.0
     *
     * @param   n/a
     * @return  n/a
     */
    function initialize() {

        // vars
        $this->name          = 'bladeui_icon';
        $this->label         = _x( 'BladeUI Icon', 'noun', 'acf-bladeui-icons' );
        $this->category      = 'choice';
        $this->description   = __( 'A dropdown list with a selection of icons.', 'acf-bladeui-icons' );
        $this->preview_image = acf_get_url() . '/assets/images/field-type-previews/field-preview-select.png';
        $this->defaults      = array(
            'allow_null'    => 0,
            'choices'       => array(),
            'default_value' => '',
            'ui'            => 0,
            'ajax'          => 0,
            'bladeui'       => 1,
            'placeholder'   => '',
            'return_format' => 'value',
        );

        add_action( 'wp_ajax_acf/fields/bladeui_icon/query', array( $this, 'ajax_query' ) );
        add_action( 'wp_ajax_nopriv_acf/fields/bladeui_icon/query', array( $this, 'ajax_query' ) );
    }

    /**
     * AJAX handler for getting Select field choices.
     *
     * @since 5.0.0
     *
     * @return void
     */
    public function ajax_query() {
        $nonce = acf_request_arg( 'nonce', '' );
        $key   = acf_request_arg( 'field_key', '' );

        $is_field_key = acf_is_field_key( $key );

        // Back-compat for field settings.
        if ( ! $is_field_key ) {
            if ( ! acf_current_user_can_admin() ) {
                die();
            }

            $nonce = '';
            $key   = '';
        }

        if ( ! acf_verify_ajax( $nonce, $key, $is_field_key ) ) {
            die();
        }

        acf_send_ajax_results( $this->get_ajax_query( $_POST ) );
    }

    /**
     * This function will return an array of data formatted for use in a select2 AJAX response
     *
     * @since   5.0.9
     *
     * @param array $options An array of options.
     * @return array A select2 compatible array of options.
     */
    public function get_ajax_query( $options = array() ) {
        $options = acf_parse_args(
            $options,
            array(
                'post_id'   => 0,
                's'         => '',
                'field_key' => '',
                'paged'     => 1,
            )
        );

        $shortcut = apply_filters( 'acf/fields/select/query', array(), $options );
        $shortcut = apply_filters( 'acf/fields/select/query/key=' . $options['field_key'], $shortcut, $options );
        if ( ! empty( $shortcut ) ) {
            return $shortcut;
        }



        // load field.
        $field = acf_get_field( $options['field_key'] );
        if ( ! $field ) {
            return false;
        }

        // get choices.
        $choices = $this->load_icons_as_choices($field['ui']);

        $results = array();
        $s       = null;

        // search.
        if ( $options['s'] !== '' ) {

            // strip slashes (search may be integer)
            $s = strval( $options['s'] );
            $s = wp_unslash( $s );
        }


        foreach ( $choices as $k => $v ) {

            // ensure $v is a string.
            $k = strval( $k );

            // if searching, but doesn't exist.
            if ( is_string( $s ) && stripos( $k, $s ) === false ) {
                continue;
            }

            // append results.
            $results[] = array(
                'id'   => $k,
                'text' => $v,
            );
        }

        $offset = ($options['paged'] - 1) * 40;

        $response = array(
            'results' => array_slice($results,$offset,40),
            'limit' => 40,
        );

        return $response;
    }

    /**
     * Create the HTML interface for your field
     *
     * @param   $field - an array holding all the field's data
     *
     * @type    action
     * @since   3.6
     * @date    23/01/13
     */
    function render_field( $field ) {

        // convert
        $value   = acf_get_array( $field['value'] );

        $choices = $this->load_icons_as_choices($field['ui']);

        // placeholder
        if ( empty( $field['placeholder'] ) ) {
            $field['placeholder'] = _x( 'Select', 'verb', 'acf' );
        }

        // add empty value (allows '' to be selected)
        if ( empty( $value ) ) {
            $value = array( '' );
        }

        // prepend empty choice
        // - only for single selects
        // - have tried array_merge but this causes keys to re-index if is numeric (post ID's)
        if ( $field['allow_null'] ) {
            $choices = array( '' => "- {$field['placeholder']} -" ) + $choices;
        }

        if ( $field['ui'] && $field['ajax'] ) {
            $minimal = array();
            foreach ( $value as $key ) {
                if ( isset( $choices[ $key ] ) ) {
                    $minimal[ $key ] = $choices[ $key ];
                }
            }
            $choices = $minimal;
        }

        // vars
        $select = array(
            'id'               => $field['id'],
            'class'            => $field['class'],
            'name'             => $field['name'],
            'data-ui'          => $field['ui'],
            'data-ajax'        => $field['ajax'],
            'data-bladeui'     => $field['bladeui'],
            'data-placeholder' => $field['placeholder'],
            'data-allow_null'  => $field['allow_null'],
        );


        // special atts
        if ( ! empty( $field['readonly'] ) ) {
            $select['readonly'] = 'readonly';
        }
        if ( ! empty( $field['disabled'] ) ) {
            $select['disabled'] = 'disabled';
        }
        if ( ! empty( $field['ajax_action'] ) ) {
            $select['data-ajax_action'] = $field['ajax_action'];
        }
        if ( ! empty( $field['nonce'] ) ) {
            $select['data-nonce'] = $field['nonce'];
        }
        if ( $field['ajax'] && empty( $field['nonce'] ) && acf_is_field_key( $field['key'] ) ) {
            $select['data-nonce'] = wp_create_nonce( 'acf_field_' . $this->name . '_' . $field['key'] );
        }
        if ( ! empty( $field['hide_search'] ) ) {
            $select['data-minimum-results-for-search'] = '-1';
        }

        // hidden input is needed to allow validation to see <select> element with no selected value
        if ( $field['ui'] ) {
            acf_hidden_input(
                array(
                    'id'   => $field['id'] . '-input',
                    'name' => $field['name'],
                )
            );
        }

        // append
        $select['value']   = $value;
        $select['choices'] = $choices;

        // render
        acf_select_input( $select );
    }

    function load_icons_as_choices($ui = 0){
        $icon_factory = app(Factory::class);

        $this->icon_sets = $icon_factory->all();

        $icons_manifest = app(IconsManifest::class);

        $icon_list = [];
        foreach($icons_manifest->getManifest($this->icon_sets) as $set => $paths) {
            foreach ($paths as $icons) {
                $prefixed_icons = array_map(function($string) use ($set) {
                    return $this->icon_sets[$set]['prefix'] . '-' . $string;
                }, $icons);
                $icon_list = array_merge($icon_list, $prefixed_icons);
            }
        }

        $icon_list = array_combine($icon_list,$icon_list);

        if($ui){
            foreach ($icon_list as &$value) {
                $value = '<div style="display: flex;"><div style="width: 22px; height: 22px">' . svg($value)->contents() . '</div><div style="margin-left:5px;">' . $value . '</div></div>';
            }
        }

        return $icon_list;
    }

    /**
     * Renders the field settings used in the "Validation" tab.
     *
     * @since 6.0
     *
     * @param array $field The field settings array.
     * @return void
     */
    function render_field_validation_settings( $field ) {
        acf_render_field_setting(
            $field,
            array(
                'label'        => __( 'Allow Null', 'acf' ),
                'instructions' => '',
                'name'         => 'allow_null',
                'type'         => 'true_false',
                'ui'           => 1,
            )
        );
    }

    /**
     * Renders the field settings used in the "Presentation" tab.
     *
     * @since 6.0
     *
     * @param array $field The field settings array.
     * @return void
     */
    function render_field_presentation_settings( $field ) {
        acf_render_field_setting(
            $field,
            array(
                'label'        => __( 'Stylized UI', 'acf' ),
                'instructions' => __( 'Use a stylized checkbox using select2', 'acf' ),
                'name'         => 'ui',
                'type'         => 'true_false',
                'ui'           => 1,
            )
        );
        acf_render_field_setting(
            $field,
            array(
                'label'        => __( 'Use AJAX to lazy load choices?', 'acf' ),
                'instructions' => '',
                'name'         => 'ajax',
                'type'         => 'true_false',
                'ui'           => 1,
                'conditions'   => array(
                    'field'    => 'ui',
                    'operator' => '==',
                    'value'    => 1,
                ),
            )
        );
    }
}

