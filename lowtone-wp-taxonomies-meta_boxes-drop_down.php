<?php
/*
 * Plugin Name: Drop-down Meta Box for Terms
 * Plugin URI: http://wordpress.lowtone.nl/wp-taxonomies-meta_boxes-drop_down
 * Plugin Type: lib
 * Description: Register a drop-down meta box for term selection.
 * Version: 1.0
 * Author: Lowtone <info@lowtone.nl>
 * Author URI: http://lowtone.nl
 * License: http://wordpress.lowtone.nl/license
 */

namespace lowtone\wp\taxonomies\meta_boxes\drop_down {

	use lowtone\content\packages\Package;

	// Includes
	
	if (!include_once WP_PLUGIN_DIR . "/lowtone-content/lowtone-content.php") 
		return trigger_error("Lowtone Content plugin is required", E_USER_ERROR) && false;

	$__i = Package::init(array(
			Package::INIT_PACKAGES => array(
				"lowtone", 
				"lowtone\\wp\\taxonomies\\meta_boxes",  
				"lowtone\\scripts\\chosen"
			),
			Package::INIT_MERGED_PATH => __NAMESPACE__,
			Package::INIT_SUCCESS => function() {

				$dropDown = function($post, $box, $options = NULL) {
					$options = array_merge(array(
							"type" => "single",
							"empty" => false
						), (array) $options);

					$taxonomy = get_taxonomy($box["args"]["taxonomy"]);

					// Enqueue chosen

					wp_enqueue_style("chosen");
					wp_enqueue_script("chosen.jquery");

					$name = sprintf("tax_input[%s][]", $taxonomy->name);

					$id = $box["id"] . "-select";

					// Selected terms

					$selected = wp_get_object_terms($post->ID, $taxonomy->name, array("fields" => "ids"));

					// All terms

					$terms = array();

					foreach ((array) get_terms($taxonomy->name, array("get" => "all")) as $term) 
						$terms[$term->term_id] = $term->name;

					// Define chosen options

					$chosenOptions = array();

					if ($options["empty"]) {
						$terms = array("" => "") + $terms;

						$chosenOptions["allow_single_deselect"] = true;
					}

					// Create placeholder

					$placeholder = "single" == $options["type"] 
						? sprintf(__("Select a %s", "lowtone_wp_taxonomies_meta_boxes_drop_down"), $taxonomy->labels->singular_name)
						: sprintf(__("Select one or more %s", "lowtone_wp_taxonomies_meta_boxes_drop_down"), $taxonomy->label);

					// Return output, the hidden input is for empty input

					return sprintf('<input type="hidden" name="%s" value="">', esc_attr($name)) . 
						sprintf('<select name="%s" id="%s" data-placeholder="%s" %s style="width: 100%%">', esc_attr($name), esc_attr($id), esc_attr($placeholder), "multiple" == $options["type"] ? 'multiple="multiple"' : "") . 
						implode(array_map(function($term, $id) use ($taxonomy, $selected) {
							$value = $taxonomy->hierarchical ? $id : $term;

							return sprintf('<option value="%s" %s>', esc_attr($value), in_array($id, $selected) ? 'selected="selected"' : "") . $term . '</option>';
						}, $terms, array_keys($terms))) . 
						'</select>' . 
						sprintf('<script>(function($) {$(function() {$("#%s").chosen(%s)})})(jQuery)</script>', $id, json_encode($chosenOptions));
				};

				\lowtone\wp\taxonomies\meta_boxes\register("dropdown", function($post, $box) use ($dropDown) {
					echo $dropDown($post, $box, array(
							"type" => "single",
							"empty" => false,
						));
				});

				\lowtone\wp\taxonomies\meta_boxes\register("dropdown_empty", function($post, $box) use ($dropDown) {
					echo $dropDown($post, $box, array(
							"type" => "single",
							"empty" => true,
						));
				});

				\lowtone\wp\taxonomies\meta_boxes\register("dropdown_multiple", function($post, $box) use ($dropDown) {
					echo $dropDown($post, $box, array(
							"type" => "multiple",
						));
				});

				// Load text domain
				
				$loadTextDomain = function() {
					if (is_textdomain_loaded("lowtone_wp_taxonomies_meta_boxes_drop_down"))
						return;

					load_textdomain("lowtone_wp_taxonomies_meta_boxes_drop_down", __DIR__ . "/assets/languages/" . get_locale() . ".mo");
				};

				add_action("plugins_loaded", $loadTextDomain);

				add_action("after_setup_theme", $loadTextDomain);

			}
		));
	
}