<?php

/**
* @package VertiMenu
*/
/*
Plugin Name: VertiMenu
Plugin URI: http://www.vertimenu.com
Description: Multilevel mobile menu with optional third-party menu item image support.
Version: 1.1.1
Author: SAGAIO
Author URI: http://www.sagaio.com
License: GPL 2.0

*/

defined( 'ABSPATH' ) or die( 'Forbidden.' );


/* Add settings link in plugins page */
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );

function add_action_links ( $links ) {
   $mylinks = array(
       '<a href="' . admin_url( '/customize.php?autofocus[panel]=sagaio_vertimenu' ) . '">Settings</a>',
   );
   return array_merge( $links, $mylinks );
}


/**
 * Customizer: Add Control: Custom: Radio Image
 *
 * This file demonstrates how to add a custom radio-image control to the Customizer.
 *
 * @package code-examples
 * @copyright Copyright (c) 2015, WordPress Theme Review Team
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, v2 (or newer)
 */

/**
* Create a Radio-Image control
*
* This class incorporates code from the Kirki Customizer Framework and from a tutorial
* written by Otto Wood.
*
* The Kirki Customizer Framework, Copyright Aristeides Stathopoulos (@aristath),
* is licensed under the terms of the GNU GPL, Version 2 (or later).
*
* @link https://github.com/reduxframework/kirki/
* @link http://ottopress.com/2012/making-a-custom-control-for-the-theme-customizer/
*/

function custom_radio_control( $wp_customize ) {

    /*
     * Failsafe is safe
     */
    if ( ! isset( $wp_customize ) ) {
        return;
    }

    class Theme_Slug_Custom_Radio_Image_Control extends WP_Customize_Control {

        /**
         * Declare the control type.
         *
         * @access public
         * @var string
         */
        public $type = 'radio-image';

        /**
         * Enqueue scripts and styles for the custom control.
         *
         * Scripts are hooked at {@see 'customize_controls_enqueue_scripts'}.
         *
         * Note, you can also enqueue stylesheets here as well. Stylesheets are hooked
         * at 'customize_controls_print_styles'.
         *
         * @access public
         */
        public function enqueue() {
            wp_enqueue_script( 'jquery-ui-button' );
        }

        /**
         * Render the control to be displayed in the Customizer.
         */
        public function render_content() {
            if ( empty( $this->choices ) ) {
                return;
            }

            $name = '_customize-radio-' . $this->id;
            ?>
            <span class="customize-control-title">
                <?php echo esc_attr( $this->label ); ?>
                <?php if ( ! empty( $this->description ) ) : ?>
                    <span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
                <?php endif; ?>
            </span>
            <div id="input_<?php echo $this->id; ?>" class="image">
                <?php foreach ( $this->choices as $value => $label ) : ?>
                    <input class="image-select" type="radio" value="<?php echo esc_attr( $value ); ?>" id="<?php echo $this->id . $value; ?>" name="<?php echo esc_attr( $name ); ?>" <?php $this->link(); checked( $this->value(), $value ); ?>>
                    <label for="<?php echo $this->id . $value; ?>">
                        <img src="<?php echo esc_html( $label ); ?>" alt="<?php echo esc_attr( $value ); ?>" title="<?php echo esc_attr( $value ); ?>">
                    </label>
                </input>
            <?php endforeach; ?>
        </div>
        <script>jQuery(document).ready(function($) { $( '[id="input_<?php echo $this->id; ?>"]' ).buttonset(); });</script>
        <?php
    }
}
}

add_action( 'customize_register', 'custom_radio_control' );


class VertiMenu_Walker extends Walker_Nav_Menu
{

    /* Start of the <ul> */
    function start_lvl(&$output, $depth = 0, $args = array())
    {
        $tabs = str_repeat("\t", $depth);
        // If we are about to start the first submenu, we need to give it a dropdown-menu class
        if ($depth == 0 || $depth == 1 || $depth == 2) { //really, level-1 or level-2, because $depth is misleading
            $output .= "{$tabs}<ul class=\"vertimenu-submenu\">";
        } else {
            $output .= "{$tabs}<ul class=\"vertimenu-main-menu\"><li>";
        }
    }

    /* End of the <ul>
     *
     * Note on $depth: Counterintuitively, $depth here means the "depth right before we start this menu".
     *                   So basically add one to what you'd expect it to be
     */
    function end_lvl(&$output, $depth = 0, $args = array())
    {
        $tabs = str_repeat("\t", $depth);
        $output .= "{$tabs}</ul>";
    }

    /* Output the <li> and the containing <a>
     * Note: $depth is "correct" at this level
     */
    function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0)
    {
        global $wp_query;
        $indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

        /* Output thumbnail if it exists and otherwise icon (if not disabled) */
        $term = get_term( $item->object_id );

        // Check if Plugin: Menu Image is used
        if ( isset($item->thumbnail_id) && isset($item->image_size) ) {

            $thumbnail = wp_get_attachment_image_src( $item->thumbnail_id, 'thumbnail');

        } else if($term) {
            // If it's native WooCommerce category
            if($term->taxonomy == 'product_cat') {
                $thumbnail_id = get_woocommerce_term_meta( $term->term_id, 'thumbnail_id', true );
                $thumbnail = wp_get_attachment_image_src( $thumbnail_id, 'thumbnail' );
            }
            // if Plugin: Taxonomy Images is used
            if(get_option( 'taxonomy_image_plugin' ) && $term->taxonomy != 'product_cat') {

                $related_id = 0;
                if ( isset( $term->term_taxonomy_id ) ) {
                    $related_id = (int) $term->term_taxonomy_id;
                }

                $attachment_id = 0;
                $associations = get_option( 'taxonomy_image_plugin' );
                if ( isset( $associations[ $related_id ] ) ) {
                    $attachment_id = (int) $associations[ $related_id ];
                }

                $thumbnail = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
            }
        } else {
            $thumbnail = [];
        }

        // If it has a thumbnail
        if (isset($thumbnail[0])) {

            $has_thumbnail = true;

        } else {
            $has_thumbnail = false;
            // get icon instead
            $indicator = get_option('sagaio_vertimenu_menu_item_indicator_icon_right', 'ion-ios-arrow-right');
        }

        $has_thumbnail_class = $has_thumbnail ? ' vertimenu-menu-item-has-image' : '';
        /* This is the stock Wordpress code that builds the <li> with all of its attributes */
        $output .= $indent . '<li class="vertimenu-menu-item'.$has_thumbnail_class.'">';
        $attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
        $attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
        $attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
        $attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';
        $item_output = $args->before;


        $itemClickable = get_option('sagaio_vertimenu_item_with_subitems_clickable', 'yes');

        /* If this item has a dropdown menu, make clicking on this link toggle it */
        if ($item->hasChildren && $itemClickable == 'yes') {
            $item_output .= '<a href="#">';
        } else {
            $item_output .= '<a'. $attributes .'>';
        }

        $item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;


        if ($item->hasChildren) {
            if($has_thumbnail) {
                $item_output .= '</a><img class="vertimenu-category-image" src="'.$thumbnail[0].'"/>';
            } else {
                $item_output .= '</a><b class="vertimenu-indicator-right '.$indicator.'"></b>';
            }
        } else {
            if($has_thumbnail) {
                $item_output .= '</a><img class="vertimenu-category-image" src="'.$thumbnail[0].'"/>';
            } else {
                $item_output .= '</a>';
            }
        }

        $item_output .= $args->after;
        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
    }

    /* Close the <li>
     * Note: the <a> is already closed
     * Note 2: $depth is "correct" at this level
     */
    function end_el (&$output, $item, $depth = 0, $args = array())
    {
        $output .= '</li></li>';
    }

    /* Add a 'hasChildren' property to the item
     * Code from: http://wordpress.org/support/topic/how-do-i-know-if-a-menu-item-has-children-or-is-a-leaf#post-3139633
     */
    function display_element ($element, &$children_elements, $max_depth, $depth = 0, $args, &$output)
    {
        // check whether this item has children, and set $item->hasChildren accordingly
        $element->hasChildren = isset($children_elements[$element->ID]) && !empty($children_elements[$element->ID]);

        // continue with normal behavior
        return parent::display_element($element, $children_elements, $max_depth, $depth, $args, $output);
    }
}

class VertiMenu {

    static function init() {
        add_action( 'init', array(__CLASS__, 'vertimenu_add_menu_location' ));
        add_action( 'init', array(__CLASS__, 'vertimenu_register_styles' ));
        add_action( 'init', array(__CLASS__, 'vertimenu_register_scripts' ));

        add_action( 'customize_register', array(__CLASS__, 'sagaio_vertimenu_customize_register' ));
        add_action( 'customize_controls_print_styles', array(__CLASS__, 'theme_slug_customizer_custom_control_css' ));

        add_action( 'wp_footer', array(__CLASS__, 'load_vertimenu_in_footer' ), 0);
        add_action( 'wp_footer', array(__CLASS__, 'kill_menu_image_menu_filter' ), 0);
    }
    /**
     * Remove an anonymous object filter.
     *
     * @param  string $tag    Hook name.
     * @param  string $class  Class name
     * @param  string $method Method name
     * @return void
     */
    static function remove_menu_image_filter( $tag, $class, $method )
    {
        $filters = $GLOBALS['wp_filter'][ $tag ];

        if ( empty ( $filters ) )
        {
            return;
        }

        foreach ( $filters as $priority => $filter )
        {
            foreach ( $filter as $identifier => $function )
            {
                if ( is_array( $function)
                    and is_a( $function['function'][0], $class )
                    and $method === $function['function'][1]
                )
                {
                    remove_filter(
                        $tag,
                        array ( $function['function'][0], $method ),
                        $priority
                    );
                }
            }
        }
    }

    static function kill_menu_image_menu_filter()
    {
        if(class_exists('Menu_Image_Plugin')) {
            // Remove filter applied by Menu Images - otherwise conflict
            self::remove_menu_image_filter(
                'walker_nav_menu_start_el',
                'Menu_Image_Plugin',
                'menu_image_nav_menu_item_filter'
            );
        } else {
            return;
        }

    }

    static function vertimenu_add_menu_location() {
        register_nav_menus( array(
            'sagaio_vertimenu' => 'VertiMenu'
        ) );
    }

    /**
     * Add CSS for custom controls
     *
     * This function incorporates CSS from the Kirki Customizer Framework
     *
     * The Kirki Customizer Framework, Copyright Aristeides Stathopoulos (@aristath),
     * is licensed under the terms of the GNU GPL, Version 2 (or later)
     *
     * @link https://github.com/reduxframework/kirki/
     */
    static function theme_slug_customizer_custom_control_css() {
        ?>
        <style>
        .customize-control-radio-image .image.ui-buttonset input[type=radio] {
            height: auto;
        }
        .customize-control-radio-image .image.ui-buttonset label {
            display: inline-block;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .customize-control-radio-image .image.ui-buttonset label.ui-state-active {
            background: none;
        }
        .customize-control-radio-image .customize-control-radio-buttonset label {
            padding: 5px 10px;
            background: #f7f7f7;
            border-left: 1px solid #dedede;
            line-height: 35px;
        }
        .customize-control-radio-image label img {
            border: 1px solid #bbb;
            opacity: 0.5;
        }
        #customize-controls .customize-control-radio-image label img {
            max-width: 50px;
            height: auto;
        }
        .customize-control-radio-image label.ui-state-active img {
            background: #dedede;
            border-color: #000;
            opacity: 1;
        }
        .customize-control-radio-image label.ui-state-hover img {
            opacity: 0.9;
            border-color: #999;
        }
        .customize-control-radio-buttonset label.ui-corner-left {
            border-radius: 3px 0 0 3px;
            border-left: 0;
        }
        .customize-control-radio-buttonset label.ui-corner-right {
            border-radius: 0 3px 3px 0;
        }
    </style>
    <?php
}

static function sagaio_vertimenu_customize_register( $wp_customize ) {

    /* Add panel for plugin settings */
    $wp_customize->add_panel( 'sagaio_vertimenu' , array(
        'title' => __( 'VertiMenu', 'sagaio-vertimenu' ),
        'description' => __( 'Settings for SAGAIO VertiMenu', 'sagaio-vertimenu' ),
        'priority' => 90,
    ) );

    /* Add section for general */
    $wp_customize->add_section( 'sagaio_vertimenu_general' , array(
        'title' => __( 'General settings', 'sagaio-vertimenu' ),
        'description' => __( 'Hide/display certain elements', 'sagaio-vertimenu' ),
        'priority' => 10,
        'panel' => 'sagaio_vertimenu',
    ) );

    /* Add section for icon */
    $wp_customize->add_section( 'sagaio_vertimenu_icon' , array(
        'title' => __( 'Icon settings', 'sagaio-vertimenu' ),
        'description' => __( 'Settings for the icon', 'sagaio-vertimenu' ),
        'priority' => 20,
        'panel' => 'sagaio_vertimenu',
    ) );

    /* Add section for menu container */
    $wp_customize->add_section( 'sagaio_vertimenu_menu' , array(
        'title' => __( 'Menu settings', 'sagaio-vertimenu' ),
        'description' => __( 'Settings for the menu and its items', 'sagaio-vertimenu' ),
        'priority' => 30,
        'panel' => 'sagaio_vertimenu',
    ) );

    /* Add section for menu container colors */
    $wp_customize->add_section( 'sagaio_vertimenu_menu_colors' , array(
        'title' => __( 'Menu colors', 'sagaio-vertimenu' ),
        'description' => __( 'Color settings for the menu', 'sagaio-vertimenu' ),
        'priority' => 30,
        'panel' => 'sagaio_vertimenu',
    ) );

    /* Add section for menu background */
    $wp_customize->add_section( 'sagaio_vertimenu_menu_background' , array(
        'title' => __( 'Menu background', 'sagaio-vertimenu' ),
        'description' => __( 'Background image settings for the menu', 'sagaio-vertimenu' ),
        'priority' => 30,
        'panel' => 'sagaio_vertimenu',
    ) );


    /* General: Show/hide (media) settings */
    $general_show_hide = [];
    $general_show_hide[] = array( 'slug'=>'sagaio_vertimenu_hide_over_px', 'default' => '960', 'label' => __( 'Hide menu over pixel width', 'sagaio-vertimenu' ), 'description' => __('Define over what px width the menu should be hidden (default is 960px). Do NOT write "px" in the field and set this field to empty to always display the menu.','sagaio-vertimenu') );
    $general_show_hide[] = array( 'slug'=>'sagaio_vertimenu_hidden_elements', 'default' => '', 'label' => __( 'Hide other elements when VertiMenu is visible (id, classes), comma-separated', 'sagaio-vertimenu' ), 'description' => __('Other elements that should be hidden when VertiMenu is visible, only applies when the above setting is not 0.','sagaio-vertimenu') );
    $general_show_hide[] = array( 'slug'=>'sagaio_vertimenu_content_in_nav_header', 'default' => '', 'label' => __( 'HTML or shortcode that should be processed and displayed next to the bar icon when the menu is open.', 'sagaio-vertimenu' ), 'description' => __('For example a shopping cart. You will have to modify the CSS to show your content properly.','sagaio-vertimenu') );

    foreach($general_show_hide as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( $setting['slug'], array( 'type' => 'input', 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_general', 'description' => $setting['description'] ));
    }

    /* General: Yes/No */
    $general_yes_no = [];

    $general_yes_no[] = array( 'slug'=>'sagaio_vertimenu_enabled', 'default' => 'yes', 'label' => __( 'Enable VertiMenu? Enabled by default', 'sagaio-vertimenu' ) );
    $general_yes_no[] = array( 'slug'=>'sagaio_vertimenu_load_ionicons', 'default' => 'yes', 'label' => __( 'Enqueue Ionicons icon library? Disable if already loading in your theme or other plugins.', 'sagaio-vertimenu' ) );
    $general_yes_no[] = array( 'slug'=>'sagaio_vertimenu_item_with_subitems_clickable', 'default' => 'yes', 'label' => __( 'Should menu links with sublinks be clickable and lead to the submenu? If "no" then only the indicator is clickable.', 'sagaio-vertimenu' ) );
    $general_yes_no[] = array( 'slug'=>'sagaio_vertimenu_category_images', 'default' => 'yes', 'label' => __( 'Show images for categories? (works with WooCommerce and plugin Taxonomy Images) - Also replaces indicators that lead to next submenu (down one level)', 'sagaio-vertimenu' ) );
    $general_yes_no[] = array( 'slug'=>'sagaio_vertimenu_category_images_circled', 'default' => 'yes', 'label' => __( 'Show round (circle) images?', 'sagaio-vertimenu' ) );

    foreach($general_yes_no as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( $setting['slug'], array( 'type' => 'select', 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_general', 'choices' => array( 'yes' => 'Yes', 'no' => 'No') ));
    }

    /* Icon: ID */
    $icon_id = [];

    $icon_id[] = array( 'slug'=>'sagaio_vertimenu_icon_id', 'default' => '1', 'label' => __( 'Icon ID', 'sagaio-vertimenu' ), 'description' => 'Default is 1"' );

    foreach($icon_id as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control(
            new Theme_Slug_Custom_Radio_Image_Control(
                // $wp_customize object
                $wp_customize,
                // $id
                'sagaio_vertimenu_icon_id',
                // $args
                array(
                    'settings'      => 'sagaio_vertimenu_icon_id',
                    'section'       => 'sagaio_vertimenu_icon',
                    'label'         => __( 'Icon animation', 'sagaio-vertimenu' ),
                    'description'   => __( 'Select the opening animation for the icon.', 'sagaio-vertimenu' ),
                    'choices'       => array(
                        '1'        => plugins_url('/images/icon/1.png', __FILE__),
                        '2'        => plugins_url('/images/icon/2.png', __FILE__),
                        '3'        => plugins_url('/images/icon/3.png', __FILE__),
                        '4'        => plugins_url('/images/icon/4.png', __FILE__),
                        '5'        => plugins_url('/images/icon/5.png', __FILE__),
                        '6'        => plugins_url('/images/icon/6.png', __FILE__),
                        '7'        => plugins_url('/images/icon/7.png', __FILE__),
                    )
                )
            )
        );
    }

    /* Icon: color settings */
    $icon_colors = [];

    $icon_colors[] = array( 'slug'=>'sagaio_vertimenu_icon_bar_color', 'default' => '#1b1b1b', 'label' => __( 'Icon bars color', 'sagaio-vertimenu' ) );
    foreach($icon_colors as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $setting['slug'], array( 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_icon', 'settings' => $setting['slug'] )));
    }
    /* Icon: height, widths, margins and paddings */

    $icon_bar = [];

    $icon_bar[] = array( 'slug'=>'sagaio_vertimenu_icon_bar_height', 'default' => '4', 'label' => __( 'Icon bar height', 'sagaio-vertimenu' ) );
    $icon_bar[] = array( 'slug'=>'sagaio_vertimenu_icon_bar_border_radius', 'default' => '0', 'label' => __( 'Icon bar border-radius', 'sagaio-vertimenu' ) );
    $icon_bar[] = array( 'slug'=>'sagaio_vertimenu_icon_bar_transition_time', 'default' => '.25', 'label' => __( 'Icon bar transition time in seconds (ex: .25)', 'sagaio-vertimenu' ) );

    foreach($icon_bar as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( $setting['slug'], array( 'type' => 'number', 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_icon', 'input_attrs' => array( 'min' => 0, 'max' => 10) ));
    }

    $icon_hwmp = [];

    $icon_hwmp[] = array( 'slug'=>'sagaio_vertimenu_icon_bar_width', 'default' => '45', 'label' => __( 'Icon bar width', 'sagaio-vertimenu' ) );
    $icon_hwmp[] = array( 'slug'=>'sagaio_vertimenu_icon_bar_height', 'default' => '4', 'label' => __( 'Icon bar height', 'sagaio-vertimenu' ) );
    $icon_hwmp[] = array( 'slug'=>'sagaio_vertimenu_icon_bar_border_radius', 'default' => '0', 'label' => __( 'Icon bar border-radius', 'sagaio-vertimenu' ) );
    $icon_hwmp[] = array( 'slug'=>'sagaio_vertimenu_icon_width', 'default' => '55', 'label' => __( 'Icon width', 'sagaio-vertimenu' ) );
    $icon_hwmp[] = array( 'slug'=>'sagaio_vertimenu_icon_height', 'default' => '50', 'label' => __( 'Icon height', 'sagaio-vertimenu' ) );
    $icon_hwmp[] = array( 'slug'=>'sagaio_vertimenu_icon_margin_top', 'default' => '0', 'label' => __( 'Icon margin-top', 'sagaio-vertimenu' ) );
    $icon_hwmp[] = array( 'slug'=>'sagaio_vertimenu_icon_margin_right', 'default' => '0', 'label' => __( 'Icon margin-right', 'sagaio-vertimenu' ) );
    $icon_hwmp[] = array( 'slug'=>'sagaio_vertimenu_icon_margin_bottom', 'default' => '0', 'label' => __( 'Icon margin-bottom', 'sagaio-vertimenu' ) );
    $icon_hwmp[] = array( 'slug'=>'sagaio_vertimenu_icon_margin_left', 'default' => '0', 'label' => __( 'Icon margin-left', 'sagaio-vertimenu' ) );

    foreach($icon_hwmp as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( $setting['slug'], array( 'type' => 'number', 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_icon', 'input_attrs' => array( 'min' => 0, 'max' => 100) ));
    }


    /* Icon: position */
    $button_position = [];

    $button_position[] = array( 'slug'=>'sagaio_vertimenu_button_position', 'default' => 'fixed', 'label' => __( 'Button position', 'sagaio-vertimenu' ), 'description' => 'Default is "fixed" which makes the menu button present during scroll. Set to "absolute" to keep it at the top of your page.' );
    $button_position[] = array( 'slug'=>'sagaio_vertimenu_button_top', 'default' => '0', 'label' => __( 'Top', 'sagaio-vertimenu' ), 'description' => 'Default is "0", you can add "px" or any other suffix to the end of the number you choose.' );
    $button_position[] = array( 'slug'=>'sagaio_vertimenu_button_right', 'default' => 'unset', 'label' => __( 'Right', 'sagaio-vertimenu' ), 'description' => 'Default is unset, you can add "px" or any other suffix to the end of the number you choose.' );
    $button_position[] = array( 'slug'=>'sagaio_vertimenu_button_bottom', 'default' => 'unset', 'label' => __( 'Bottom', 'sagaio-vertimenu' ), 'description' => 'Default is unset, you can add "px" or any other suffix to the end of the number you choose.' );
    $button_position[] = array( 'slug'=>'sagaio_vertimenu_button_left', 'default' => '0', 'label' => __( 'Left', 'sagaio-vertimenu' ), 'description' => 'Default is "0", you can add "px" or any other suffix to the end of the number you choose.' );

    foreach($button_position as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( $setting['slug'], array( 'type' => 'input', 'label' => $setting['label'], 'description' => $setting['description'], 'section' => 'sagaio_vertimenu_menu' ));
    }

    /* Menu container: alignments */
    $menu_alignments = [];

    $menu_alignments[] = array( 'slug'=>'sagaio_vertimenu_header_alignment', 'default' => 'left', 'label' => __( 'Align header left/center/right', 'sagaio-vertimenu' ) );
    $menu_alignments[] = array( 'slug'=>'sagaio_vertimenu_item_alignment', 'default' => 'left', 'label' => __( 'Align item title left/center/right', 'sagaio-vertimenu' ) );

    foreach($menu_alignments as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( $setting['slug'], array( 'type' => 'select', 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_menu', 'choices' => array( 'left' => 'Left', 'center' => 'Center', 'right' => 'Right') ));
    }

    /* Menu container: icons */
    $menu_item_icon = [];

    $menu_item_icon[] = array( 'slug'=>'sagaio_vertimenu_menu_item_indicator_icon_right', 'default' => 'ion-ios-arrow-right', 'label' => __( 'Up one level Indicator', 'sagaio-vertimenu' ), 'description' => 'Default is ion-ios-arrow-right' );
    $menu_item_icon[] = array( 'slug'=>'sagaio_vertimenu_menu_item_indicator_icon_left', 'default' => 'ion-ios-arrow-left', 'label' => __( 'Down one level Indicator', 'sagaio-vertimenu' ), 'description' => 'Default is ion-ios-arrow-left' );

    foreach($menu_item_icon as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( $setting['slug'], array( 'type' => 'input', 'label' => $setting['label'], 'description' => $setting['description'], 'section' => 'sagaio_vertimenu_menu' ));
    }

    /* Menu container: text fields */
    $menu_text = [];

    $menu_text[] = array( 'slug'=>'sagaio_vertimenu_menu_header_font_family', 'default' => 'Arial, sans-serif', 'label' => __( 'Header font family', 'sagaio-vertimenu' ), 'description' => 'Default is Arial, sans-serif' );
    $menu_text[] = array( 'slug'=>'sagaio_vertimenu_menu_header_font_style', 'default' => 'bold', 'label' => __( 'Header font style', 'sagaio-vertimenu' ), 'description' => 'Default is bold' );
    $menu_text[] = array( 'slug'=>'sagaio_vertimenu_menu_item_font_family', 'default' => 'Arial, sans-serif', 'label' => __( 'Items font family', 'sagaio-vertimenu' ), 'description' => 'Default is Arial, sans-serif' );
    $menu_text[] = array( 'slug'=>'sagaio_vertimenu_menu_item_font_style', 'default' => 'normal', 'label' => __( 'Items font style', 'sagaio-vertimenu' ), 'description' => 'Default is normal' );

    foreach($menu_text as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( $setting['slug'], array( 'type' => 'input', 'label' => $setting['label'], 'description' => $setting['description'], 'section' => 'sagaio_vertimenu_menu' ));
    }

    // TODO: Background logic with transparent items when using background image

    // /* Menu container: background image */
    // $menu_background = [];

    // $menu_background[] = array( 'slug'=>'sagaio_vertimenu_menu_background_image', 'default' => '', 'label' => __( 'Background image', 'sagaio-vertimenu' ) );

    // foreach($menu_background as $setting)
    // {
    //     $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
    //     $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $setting['slug'], array( 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_menu_background', 'settings' => $setting['slug'] )));
    // }

    // /* Menu container: background image repeat  */
    // $menu_background = [];

    // $menu_background[] = array( 'slug'=>'sagaio_vertimenu_background_repeat', 'default' => 'no-repeat', 'label' => __( 'Background repeat', 'sagaio-vertimenu' ), 'description' => 'Defaults to "no-repeat"' );

    // foreach($menu_background as $setting)
    // {
    //     $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
    //     $wp_customize->add_control( $setting['slug'], array( 'type' => 'select', 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_menu_background', 'choices' => array( 'repeat' => 'Repeat', 'no-repeat' => 'No repeat', 'right' => 'Right') ));
    // }

    // /* Menu container: background image size  */
    // $menu_background = [];

    // $menu_background[] = array( 'slug'=>'sagaio_vertimenu_background_size', 'default' => 'cover', 'label' => __( 'Background size', 'sagaio-vertimenu' ), 'description' => 'Defaults to "cover"' );

    // foreach($menu_background as $setting)
    // {
    //     $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
    //     $wp_customize->add_control( $setting['slug'], array( 'type' => 'input', 'label' => $setting['label'], 'description' => $setting['description'], 'section' => 'sagaio_vertimenu_menu_background' ));
    // }

    /* Menu container: background color */
    $menu_background = [];

    $menu_background[] = array( 'slug'=>'sagaio_vertimenu_menu_background_color', 'default' => '#ffffff', 'label' => __( 'Menu background color', 'sagaio-vertimenu' ) );

    foreach($menu_background as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $setting['slug'], array( 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_menu_background', 'settings' => $setting['slug'] )));
    }

    /* Menu container: color settings */
    $menu_colors = [];

    $menu_colors[] = array( 'slug'=>'sagaio_vertimenu_menu_item_header_color', 'default' => '#1b1b1b', 'label' => __( 'Menu header text color', 'sagaio-vertimenu' ) );
    $menu_colors[] = array( 'slug'=>'sagaio_vertimenu_menu_item_header_background_color', 'default' => '#ffffff', 'label' => __( 'Menu header background color', 'sagaio-vertimenu' ) );
    $menu_colors[] = array( 'slug'=>'sagaio_vertimenu_menu_item_header_background_hover_color', 'default' => '#f1f1f1', 'label' => __( 'Menu header background hover color', 'sagaio-vertimenu' ) );

    $menu_colors[] = array( 'slug'=>'sagaio_vertimenu_menu_border_color', 'default' => '#f1f1f1', 'label' => __( 'Menu border color', 'sagaio-vertimenu' ) );
    $menu_colors[] = array( 'slug'=>'sagaio_vertimenu_menu_item_background_color', 'default' => '#ffffff', 'label' => __( 'Menu items background color', 'sagaio-vertimenu' ) );
    $menu_colors[] = array( 'slug'=>'sagaio_vertimenu_menu_item_background_hover_color', 'default' => '#f1f1f1', 'label' => __( 'Menu items background hover color', 'sagaio-vertimenu' ) );
    $menu_colors[] = array( 'slug'=>'sagaio_vertimenu_menu_item_text_color', 'default' => '#1b1b1b', 'label' => __( 'Menu items text color', 'sagaio-vertimenu' ) );
    $menu_colors[] = array( 'slug'=>'sagaio_vertimenu_menu_item_text_hover_color', 'default' => '#000000', 'label' => __( 'Menu items text hover color', 'sagaio-vertimenu' ) );
    $menu_colors[] = array( 'slug'=>'sagaio_vertimenu_menu_item_border_color', 'default' => '#f1f1f1', 'label' => __( 'Menu item border color', 'sagaio-vertimenu' ) );
    $menu_colors[] = array( 'slug'=>'sagaio_vertimenu_menu_indicator_right_color', 'default' => '#f1f1f1', 'label' => __( 'Indicator right color', 'sagaio-vertimenu' ) );
    $menu_colors[] = array( 'slug'=>'sagaio_vertimenu_menu_indicator_left_color', 'default' => '#f1f1f1', 'label' => __( 'Indicator left color', 'sagaio-vertimenu' ) );

    foreach($menu_colors as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, $setting['slug'], array( 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_menu_colors', 'settings' => $setting['slug'] )));
    }

    /* Menu container: border width */
    $menu_borderwidth = [];

    $menu_borderwidth[] = array( 'slug'=>'sagaio_vertimenu_menu_border_width', 'default' => '1px', 'label' => __( 'Menu border width', 'sagaio-vertimenu' ) );

    foreach($menu_borderwidth as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( $setting['slug'], array( 'type' => 'input', 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_menu' ));
    }

    /* Menu container: border style */
    $menu_borderstyle = [];

    $menu_borderstyle[] = array( 'slug'=>'sagaio_vertimenu_menu_border_style', 'default' => 'solid', 'label' => __( 'Menu border style', 'sagaio-vertimenu' ) );
    $menu_borderstyle[] = array( 'slug'=>'sagaio_vertimenu_menu_item_border_style', 'default' => 'solid', 'label' => __( 'Menu item border style', 'sagaio-vertimenu' ) );

    foreach($menu_borderstyle as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( $setting['slug'], array( 'type' => 'select', 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_menu', 'choices' => array( 'solid' => 'Solid', 'dotted' => 'Dotted', 'dashed' => 'Dashed', 'double' => 'Dotted', 'groove' => 'Groove', 'ridge' => 'Ridge') ));
    }

    /* Menu container: height, widths, margins and paddings */
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_item_border_width', 'default' => '2', 'label' => __( 'Menu item border bottom width', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_wrapper_width', 'default' => '100', 'label' => __( 'Menu wrapper width (%)', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_wrapper_padding_top', 'default' => '0', 'label' => __( 'Menu wrapper padding top (px)', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_wrapper_padding_right', 'default' => '0', 'label' => __( 'Menu wrapper padding right (px)', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_wrapper_padding_bottom', 'default' => '0', 'label' => __( 'Menu wrapper padding bottom (px)', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_wrapper_padding_left', 'default' => '0', 'label' => __( 'Menu wrapper padding left (px)', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_container_margin_top', 'default' => '50', 'label' => __( 'Menu top margin (px)', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_container_padding_top', 'default' => '0', 'label' => __( 'Menu container padding top (px)', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_container_padding_right', 'default' => '0', 'label' => __( 'Menu container padding right (px)', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_container_padding_bottom', 'default' => '25', 'label' => __( 'Menu container padding bottom (px)', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_container_padding_left', 'default' => '0', 'label' => __( 'Menu container padding left (px)', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_header_padding_left_right', 'default' => '30', 'label' => __( 'Menu header padding left/right', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_header_padding_top_bottom', 'default' => '20', 'label' => __( 'Menu header padding top/bottom', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_item_padding_left_right', 'default' => '30', 'label' => __( 'Menu item padding left/right', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_item_padding_top_bottom', 'default' => '10', 'label' => __( 'Menu item padding top/bottom', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_item_font_size', 'default' => '24', 'label' => __( 'Menu item font size', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_item_line_height', 'default' => '28', 'label' => __( 'Menu item line height', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_item_indicator_right_size', 'default' => '33', 'label' => __( 'Item right indicator size', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_item_indicator_right_top', 'default' => '2', 'label' => __( 'Item right indicator pixel from top of item', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_item_indicator_right_left', 'default' => '7', 'label' => __( 'Item right indicator pixel from left of item', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_item_indicator_left_size', 'default' => '33', 'label' => __( 'Item left indicator size', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_item_indicator_left_top', 'default' => '7', 'label' => __( 'Item left indicator pixels from top of item', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_item_indicator_left_left', 'default' => '7', 'label' => __( 'Item left indicator pixels from left of item', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_item_indicator_left_line_height', 'default' => '1.3', 'label' => __( 'Item indicator left line-height', 'sagaio-vertimenu' ) );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_header_font_size', 'default' => '24', 'label' => __( 'Header font size', 'sagaio-vertimenu' ), 'description' => 'Default is 12px' );
    $menu_hwmp[] = array( 'slug'=>'sagaio_vertimenu_menu_header_line_height', 'default' => '28', 'label' => __( 'Header line height', 'sagaio-vertimenu' ), 'description' => 'Default is 12px' );

    foreach($menu_hwmp as $setting)
    {
        $wp_customize->add_setting( $setting['slug'], array( 'default' => $setting['default'], 'type' => 'option', 'capability' => 'edit_theme_options' ));
        $wp_customize->add_control( $setting['slug'], array( 'type' => 'number', 'label' => $setting['label'], 'section' => 'sagaio_vertimenu_menu', 'input_attrs' => array( 'min' => 0, 'max' => 1000) ));
    }
}

static function echo_customizer_styles() {
    $sagaio_vertimenu_icon_width = get_option('sagaio_vertimenu_icon_width', '55');
    $sagaio_vertimenu_icon_height = get_option('sagaio_vertimenu_icon_height', '50');
    $sagaio_vertimenu_icon_margin_top = get_option('sagaio_vertimenu_icon_margin_top', '0');
    $sagaio_vertimenu_icon_margin_right = get_option('sagaio_vertimenu_icon_margin_right', '0');
    $sagaio_vertimenu_icon_margin_bottom = get_option('sagaio_vertimenu_icon_margin_bottom', '0');
    $sagaio_vertimenu_icon_margin_left = get_option('sagaio_vertimenu_icon_margin_left', '0');
    $sagaio_vertimenu_icon_bar_color = get_option('sagaio_vertimenu_icon_bar_color', '#1b1b1b');
    $sagaio_vertimenu_icon_bar_width = get_option('sagaio_vertimenu_icon_bar_width', '45');
    $sagaio_vertimenu_icon_bar_height = get_option('sagaio_vertimenu_icon_bar_height', '4');
    $sagaio_vertimenu_icon_bar_border_radius = get_option('sagaio_vertimenu_icon_bar_border_radius', '0');
    $sagaio_vertimenu_icon_bar_transition_time = get_option('sagaio_vertimenu_icon_bar_transition_time', '.25');

    $sagaio_vertimenu_button_position = get_option('sagaio_vertimenu_button_position', 'fixed');
    $sagaio_vertimenu_button_top = get_option('sagaio_vertimenu_button_top', '0');
    $sagaio_vertimenu_button_right = get_option('sagaio_vertimenu_button_right', 'unset');
    $sagaio_vertimenu_button_bottom = get_option('sagaio_vertimenu_button_bottom', 'unset');
    $sagaio_vertimenu_button_left = get_option('sagaio_vertimenu_button_left', '0');

    $sagaio_vertimenu_menu_item_header_background_color = get_option('sagaio_vertimenu_menu_item_header_background_color', '#ffffff');
    $sagaio_vertimenu_menu_item_header_background_hover_color = get_option('sagaio_vertimenu_menu_item_header_background_hover_color', '#f1f1f1');

    $sagaio_vertimenu_menu_background_color = get_option('sagaio_vertimenu_menu_background_color', '#ffffff');
    $sagaio_vertimenu_menu_item_background_color = get_option('sagaio_vertimenu_menu_item_background_color', '#ffffff');
    $sagaio_vertimenu_menu_item_background_hover_color = get_option('sagaio_vertimenu_menu_item_background_hover_color', '#f1f1f1');
    $sagaio_vertimenu_menu_item_header_color = get_option('sagaio_vertimenu_menu_item_header_color', '#1b1b1b');
    $sagaio_vertimenu_menu_item_text_color = get_option('sagaio_vertimenu_menu_item_text_color', '#1b1b1b');
    $sagaio_vertimenu_menu_item_text_hover_color = get_option('sagaio_vertimenu_menu_item_text_hover_color', '#000000');
    $sagaio_vertimenu_menu_indicator_color = get_option('sagaio_vertimenu_menu_indicator_color', '#f1f1f1');
    $sagaio_vertimenu_menu_border_width = get_option('sagaio_vertimenu_menu_border_width', '1px');
    $sagaio_vertimenu_menu_border_style = get_option('sagaio_vertimenu_menu_border_style', 'solid');
    $sagaio_vertimenu_menu_border_color = get_option('sagaio_vertimenu_menu_border_color', '´#f1f1f1');

    $sagaio_vertimenu_menu_wrapper_width = get_option('sagaio_vertimenu_menu_wrapper_container_width', '100');
    $sagaio_vertimenu_menu_wrapper_padding_top = get_option('sagaio_vertimenu_menu_wrapper_padding_top', '0');
    $sagaio_vertimenu_menu_wrapper_padding_right = get_option('sagaio_vertimenu_menu_wrapper_padding_right', '0');
    $sagaio_vertimenu_menu_wrapper_padding_bottom = get_option('sagaio_vertimenu_menu_wrapper_padding_bottom', '0');
    $sagaio_vertimenu_menu_wrapper_padding_left = get_option('sagaio_vertimenu_menu_wrapper_padding_left', '0');
    $sagaio_vertimenu_menu_container_margin_top = get_option('sagaio_vertimenu_menu_container_margin_top', '50');
    $sagaio_vertimenu_menu_container_padding_top = get_option('sagaio_vertimenu_menu_container_padding_top', '0');
    $sagaio_vertimenu_menu_container_padding_right = get_option('sagaio_vertimenu_menu_container_padding_right', '0');
    $sagaio_vertimenu_menu_container_padding_bottom = get_option('sagaio_vertimenu_menu_container_padding_bottom', '25');
    $sagaio_vertimenu_menu_container_padding_left = get_option('sagaio_vertimenu_menu_container_padding_left', '0');

    $sagaio_vertimenu_menu_item_border_width = get_option('sagaio_vertimenu_menu_item_border_width', '2');
    $sagaio_vertimenu_menu_item_border_color = get_option('sagaio_vertimenu_menu_item_border_color', '#f1f1f1');
    $sagaio_vertimenu_menu_item_border_style = get_option('sagaio_vertimenu_menu_item_border_style', 'solid');
    $sagaio_vertimenu_menu_header_padding_left_right = get_option('sagaio_vertimenu_menu_header_padding_left_right', '30');
    $sagaio_vertimenu_menu_header_padding_top_bottom = get_option('sagaio_vertimenu_menu_header_padding_top_bottom', '20');
    $sagaio_vertimenu_menu_item_padding_left_right = get_option('sagaio_vertimenu_menu_item_padding_left_right', '30');
    $sagaio_vertimenu_menu_item_padding_top_bottom = get_option('sagaio_vertimenu_menu_item_padding_top_bottom', '10');
    $sagaio_vertimenu_menu_item_font_size = get_option('sagaio_vertimenu_menu_item_font_size', '24');
    $sagaio_vertimenu_menu_item_line_height = get_option('sagaio_vertimenu_menu_item_line_height', '28');

    $sagaio_vertimenu_menu_header_font_family = get_option('sagaio_vertimenu_menu_header_font_family', 'Arial, sans-serif');
    $sagaio_vertimenu_menu_header_font_style = get_option('sagaio_vertimenu_menu_header_font_style', 'bold');
    $sagaio_vertimenu_menu_item_font_family = get_option('sagaio_vertimenu_menu_item_font_family', 'Arial, sans-serif');
    $sagaio_vertimenu_menu_item_font_style = get_option('sagaio_vertimenu_menu_item_font_style', 'normal');
    $sagaio_vertimenu_menu_item_font_size = get_option('sagaio_vertimenu_menu_item_font_size', '24');
    $sagaio_vertimenu_menu_item_line_height = get_option('sagaio_vertimenu_menu_item_line_height', '28');
    $sagaio_vertimenu_menu_header_font_size = get_option('sagaio_vertimenu_menu_header_font_size', '24');
    $sagaio_vertimenu_menu_header_line_height = get_option('sagaio_vertimenu_menu_header_line_height', '28');

    $sagaio_vertimenu_menu_item_indicator_left_size = get_option('sagaio_vertimenu_menu_item_indicator_left_size', '33');
    $sagaio_vertimenu_menu_item_indicator_left_top = get_option('sagaio_vertimenu_menu_item_indicator_left_top', '7');
    $sagaio_vertimenu_menu_item_indicator_left_left = get_option('sagaio_vertimenu_menu_item_indicator_left_left', '7');
    $sagaio_vertimenu_menu_item_indicator_right_size = get_option('sagaio_vertimenu_menu_item_indicator_right_size', '33');
    $sagaio_vertimenu_menu_item_indicator_right_top = get_option('sagaio_vertimenu_menu_item_indicator_right_top', '2');
    $sagaio_vertimenu_menu_item_indicator_right_left = get_option('sagaio_vertimenu_menu_item_indicator_right_left', '7');

    $sagaio_vertimenu_menu_item_indicator_left_line_height = get_option('sagaio_vertimenu_menu_item_indicator_left_line_height', '1.3');
    $sagaio_vertimenu_menu_indicator_right_color = get_option('sagaio_vertimenu_menu_indicator_right_color', '#f1f1f1');
    $sagaio_vertimenu_menu_indicator_left_color = get_option('sagaio_vertimenu_menu_indicator_left_color', '#f1f1f1');

        // Alignments
    $sagaio_vertimenu_header_alignment = get_option('sagaio_vertimenu_header_alignment', 'left');
    $sagaio_vertimenu_item_alignment = get_option('sagaio_vertimenu_item_alignment', 'left');

    $style = '<style>';
    $style .= '.vertimenu-menu-wrapper {
        width: '.$sagaio_vertimenu_menu_wrapper_width.'% !important;
        padding: '.$sagaio_vertimenu_menu_wrapper_padding_top.'px '.$sagaio_vertimenu_menu_wrapper_padding_right.'px '.$sagaio_vertimenu_menu_wrapper_padding_bottom.'px '.$sagaio_vertimenu_menu_wrapper_padding_left.'px !important;
    }';
    $style .= '.vertimenu-menu-wrapper.vertimenu-full-height {
        height: 100%;
    }';
    $style .= '.vertimenu-menu-wrapper, .vertimenu-main-menu  {
        background: '.$sagaio_vertimenu_menu_background_color.' !important;
        border: '.$sagaio_vertimenu_menu_border_width.'px '.$sagaio_vertimenu_menu_border_style.' '.$sagaio_vertimenu_menu_border_color.' !important;
    }';
    $style .= '.vertimenu-menu-btn {
        background: transparent;
        height: '.$sagaio_vertimenu_icon_height.'px !important;
        width: '.$sagaio_vertimenu_icon_width.'px !important;
        margin: '.$sagaio_vertimenu_icon_margin_top.'px '.$sagaio_vertimenu_icon_margin_right.'px '.$sagaio_vertimenu_icon_margin_bottom.'px '.$sagaio_vertimenu_icon_margin_left.'px !important;
        z-index:9999999;
        position: '.$sagaio_vertimenu_button_position.';
        top: '.$sagaio_vertimenu_button_top.';
        right: '.$sagaio_vertimenu_button_right.';
        bottom: '.$sagaio_vertimenu_button_bottom.';
        left: '.$sagaio_vertimenu_button_left.';
    }';
    switch ($sagaio_vertimenu_icon_bar_height) {
        case 1:
        $bar_margin = 12;
        break;
        case 2:
        $bar_margin = 11;
        break;
        case 3:
        $bar_margin = 10;
        break;
        case 4:
        $bar_margin = 9;
        break;
        case 5:
        $bar_margin = 8;
        break;
        case 6:
        $bar_margin = 7;
        break;
        case 7:
        $bar_margin = 6;
        break;
        case 8:
        $bar_margin = 5;
        break;
        case 9:
        $bar_margin = 4;
        break;
        case 10:
        $bar_margin = 3;
        break;
    }
    $style .= '.vertimenu-menu-btn .vertimenu-line {
        background: '.$sagaio_vertimenu_icon_bar_color.';
        border-radius: '.$sagaio_vertimenu_icon_bar_border_radius.'px;
        -webkit-transition: '.$sagaio_vertimenu_icon_bar_transition_time.'s ease-in-out !important;
        -moz-transition: '.$sagaio_vertimenu_icon_bar_transition_time.'s ease-in-out !important;
        -o-transition: '.$sagaio_vertimenu_icon_bar_transition_time.'s ease-in-out !important;
        transition: '.$sagaio_vertimenu_icon_bar_transition_time.'s ease-in-out !important;
        width: '.$sagaio_vertimenu_icon_bar_width.'px;
        height: '.$sagaio_vertimenu_icon_bar_height.'px;
        margin: '.$bar_margin.'px !important;
    }';
    $style .= '.vertimenu-menu-wrapper .vertimenu-main-menu {
        background: '.$sagaio_vertimenu_menu_background_color.' !important;
        margin-top: '.$sagaio_vertimenu_menu_container_margin_top.'px !important;
        padding: '.$sagaio_vertimenu_menu_container_padding_top.'px '.$sagaio_vertimenu_menu_container_padding_right.'px '.$sagaio_vertimenu_menu_container_padding_bottom.'px '.$sagaio_vertimenu_menu_container_padding_left.'px !important;
    }';
    $style .= '.vertimenu-menu-item {
        background: '.$sagaio_vertimenu_menu_item_background_color.' !important;
    }';

    $style .= '.vertimenu-menu-item a {
        font-family: '.$sagaio_vertimenu_menu_item_font_family.' !important;
        font-size: '.$sagaio_vertimenu_menu_item_font_size.'px !important;
        font-style: '.$sagaio_vertimenu_menu_item_font_style.' !important;
        line-height: '.$sagaio_vertimenu_menu_item_line_height.'px !important;
        color: '.$sagaio_vertimenu_menu_item_text_color.' !important;
        border-top: '.$sagaio_vertimenu_menu_item_border_width.'px solid '.$sagaio_vertimenu_menu_item_border_color.' !important;
        text-align: '.$sagaio_vertimenu_item_alignment.' !important;
        padding: '.$sagaio_vertimenu_menu_item_padding_top_bottom.'px '.$sagaio_vertimenu_menu_item_padding_left_right.'px !important;
    }';

    $style .= '.vertimenu-menu-header a {
        text-align: '.$sagaio_vertimenu_header_alignment.' !important;
        font-family: '.$sagaio_vertimenu_menu_header_font_family.' !important;
        font-size: '.$sagaio_vertimenu_menu_header_font_size.'px !important;
        font-style: '.$sagaio_vertimenu_menu_header_font_style.' !important;
        line-height: '.$sagaio_vertimenu_menu_header_line_height.'px !important;
        color: '.$sagaio_vertimenu_menu_item_header_color.' !important;
        border-top: '.$sagaio_vertimenu_menu_item_border_width.'px solid '.$sagaio_vertimenu_menu_item_border_color.' !important;
        text-align: '.$sagaio_vertimenu_header_alignment.' !important;
        padding: '.$sagaio_vertimenu_menu_item_padding_top_bottom.'px '.$sagaio_vertimenu_menu_item_padding_left_right.'px !important;
    }';
    $conditional_left_right_padding = get_option('sagaio_vertimenu_category_images', 'yes') == 'yes' ? ($sagaio_vertimenu_menu_item_padding_left_right + 20) : $sagaio_vertimenu_menu_item_padding_left_right;

    $style .= '.vertimenu-submenu.vertimenu-submenu-items-has-image > .vertimenu-menu-item > a {
        padding: '.$sagaio_vertimenu_menu_item_padding_top_bottom.'px '.$conditional_left_right_padding.'px !important;
    }';

    $conditional_left_right_padding_header = get_option('sagaio_vertimenu_category_images', 'yes') == 'yes' ? ($sagaio_vertimenu_menu_header_padding_left_right + 20) : $sagaio_vertimenu_menu_header_padding_left_right;
    $style .= '.vertimenu-submenu.vertimenu-submenu-items-has-image > .vertimenu-menu-header > a {
        padding: '.$sagaio_vertimenu_menu_header_padding_top_bottom.'px '.$conditional_left_right_padding_header.'px !important;
    }';
    $style .= '.vertimenu-main-menu .vertimenu-menu-item:last-child > a, .vertimenu-submenu .vertimenu-menu-item:last-child > a {
        border-bottom: '.$sagaio_vertimenu_menu_item_border_width.'px solid '.$sagaio_vertimenu_menu_item_border_color.' !important;
    }';

    $circled_images = get_option('sagaio_vertimenu_category_images_circled', 'yes') == 'yes' ? '50' : '0';
    $style .= '.vertimenu-category-image {
        border-radius: '.$circled_images.'px;
    }';

    $style .= '.vertimenu-indicator-right {
        font-size: '.$sagaio_vertimenu_menu_item_indicator_right_size.'px !important;
        top: '.$sagaio_vertimenu_menu_item_indicator_right_top.'px !important;
        left: '.$sagaio_vertimenu_menu_item_indicator_right_left.'px !important;
        color: '.$sagaio_vertimenu_menu_indicator_right_color.' !important;
    }';
    $style .= '.vertimenu-indicator-left {
        font-size: '.$sagaio_vertimenu_menu_item_indicator_left_size.'px !important;
        top: '.$sagaio_vertimenu_menu_item_indicator_left_top.'px !important;
        left: '.$sagaio_vertimenu_menu_item_indicator_left_left.'px !important;
        color: '.$sagaio_vertimenu_menu_indicator_left_color.' !important;
    }';
    $style .= '.vertimenu-submenu.vertimenu-submenu-items-has-image > .vertimenu-menu-header > a > b.vertimenu-indicator-left {
        left: '.($sagaio_vertimenu_menu_item_indicator_left_left + $sagaio_vertimenu_menu_item_indicator_left_left).'px !important;
    }';
    $style .= '.vertimenu-menu-item:hover {
        background-color: '.$sagaio_vertimenu_menu_item_background_hover_color.' !important;
    }';
    $style .= '.vertimenu-menu-header {
        background: '.$sagaio_vertimenu_menu_item_header_background_color.' !important;
    }';
    $style .= '.vertimenu-menu-header:hover {
        background: '.$sagaio_vertimenu_menu_item_header_background_hover_color.' !important;
    }';
    $style .= '.ion-ios-indicator-left {
        line-height: '.$sagaio_vertimenu_menu_item_indicator_left_line_height.';
    }';


    /* Control the visibility of the menu */
    $style .= '@media (min-width: '.get_option('sagaio_vertimenu_hide_over_px', '960').'px) {
        .vertimenu-menu-btn { display: none !important; }
        .vertimenu-menu-wrapper { display: none !important; }
    }';

    /* Control what other elements should be hidden */
    if(get_option('sagaio_vertimenu_hide_over_px', '960') == '' || get_option('sagaio_vertimenu_hide_over_px', '960') == 0) {
        $style .= get_option('sagaio_vertimenu_hidden_elements') . '{ display:none !important; }';
    } else {
        $style .= '@media (max-width: '. (intval(get_option('sagaio_vertimenu_hide_over_px', '960')) - 1) .'px) {
            ' . get_option('sagaio_vertimenu_hidden_elements') . ' { display:none !important; }
        }';
    }

    /* Make sure the indicator is also showing as clickable */
    $style .= 'b.vertimenu-indicator-right:hover { cursor: pointer; }';

    $style .= '</style>';

    echo $style;

}

static function load_vertimenu_in_footer() {

    $holder = 'sagaio_vertimenu';

    if( ($locations = get_nav_menu_locations()) && (isset($locations[$holder])) && get_option('sagaio_vertimenu_enabled', 'yes') !== 'no') {

        $vertimenu_trigger_position = get_option('sagaio_vertimenu_trigger_position', 'left');

        wp_enqueue_style( 'vertimenu-style');
        if(get_option('sagaio_vertimenu_load_ionicons', 'yes') == 'yes') {
            wp_enqueue_style( 'vertimenu-ionicons');
        }
        wp_enqueue_script( 'vertimenu-script');

        self::echo_customizer_styles();

        $shortcode_content = '';
        $content_in_nav_header = get_option( 'sagaio_vertimenu_content_in_nav_header','');
        if( $content_in_nav_header ){
            $shortcode_content = do_shortcode( $content_in_nav_header );
        }

        $indicator = get_option('sagaio_vertimenu_menu_item_indicator_icon_left', 'ion-ios-arrow-left');
        $indicator_left = '<b class="vertimenu-indicator-left '.$indicator.'"></b>';

        $vertimenu_icon_id = get_option('sagaio_vertimenu_icon_id', '1');

        $itemClickable = get_option('sagaio_vertimenu_item_with_subitems_clickable', 'yes');

        if($shortcode_content) {
            wp_localize_script('vertimenu-script', 'vertimenu_php_data', array( 'vertimenu_shortcode_data' => $shortcode_content, 'vertimenu_indicator_left' => $indicator_left, 'vertimenu_icon_id' => $vertimenu_icon_id, 'item_with_subitem_clickable' => $itemClickable ));
        } else {
            wp_localize_script('vertimenu-script', 'vertimenu_php_data', array( 'vertimenu_indicator_left' => $indicator_left, 'vertimenu_icon_id' => $vertimenu_icon_id, 'item_with_subitem_clickable' => $itemClickable ));
        }

        $vertimenu =  wp_nav_menu( array(
            'theme_location'   => 'sagaio_vertimenu',
            'menu_class' => 'vertimenu-main-menu vertimenu-hidden',
            'container' => false,
            'walker' => new VertiMenu_Walker()
        ) );

        return $vertimenu;

    } else {
        return;
    }
}

static function vertimenu_register_styles() {
    wp_register_style( 'vertimenu-style', plugins_url( 'css/vertimenu.css', __FILE__ ) );
    wp_register_style( 'vertimenu-ionicons', plugins_url( 'css/ionicons.min.css', __FILE__ ) );
}

static function vertimenu_register_scripts() {
    wp_register_script( 'vertimenu-script', plugins_url( 'js/vertimenu.js', __FILE__ ), array( 'jquery'), '', true );
}
}
VertiMenu::init();