<?php

/*
 *
 * simply-rets-widgets.php - Copyright (C) Reichert Brothers 2014
 * This file provides the logic for the simply-rets sidebar widgets.
 *
*/

/*
 * To add new widgets, extend the WP_Widget class  with a constructor,
 * update, form, and widget function.
 * To activate new widgets, simply add a line to register the widget
 * in the srRegisterWidgets function - the rest is already initialized.
*/


/* Code starts here */
function srRegisterWidgets() {
    register_widget('srFeaturedListingWidget');
    register_widget('srRandomListingWidget');
    register_widget('srSearchFormWidget');
}


class srFeaturedListingWidget extends WP_Widget {

	/** constructor */
	function srFeaturedListingWidget() {
		parent::WP_Widget(false, $name = 'SimplyRETS Featured Listing');
	}

	/** save widget --  @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['mlsid'] = strip_tags($new_instance['mlsid']);
		return $instance;
	}

	/** admin widget form --  @see WP_Widget::form */
	function form( $instance ) {
		$title = esc_attr($instance['title']);
		$mlsid= esc_attr($instance['mlsid']);

		?>
		<p>
		  <label for="<?php echo $this->get_field_id('title'); ?>">
			<?php _e('Title:'); ?>
		  </label>
		  <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
				 name="<?php echo $this->get_field_name('title'); ?>"
				 type="text"
				 value="<?php echo $title; ?>" />
		</p>

		<p>
		  <label for="<?php echo $this->get_field_id('mlsid'); ?>">
			<?php _e('Listing MLS Id:'); ?>
		  </label>
		  <input class="widefat"
				 id="<?php echo $this->get_field_id('mlsid'); ?>"
				 name="<?php echo $this->get_field_name('mlsid'); ?>"
				 type="text"
				 value="<?php echo $mlsid; ?>" />
		</p>
		<?php
	}

	/** front end widget render -- @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		$mlsid = $instance['mlsid'];


		$cont .= $before_widget;
		// populate title
		if( $title ) {
			$cont .= $before_title . $title . $after_title;
		} else {
			$cont .= $before_title . "Featured Listing" .$after_title;
		}

		// populate content
		if( $mlsid ) {
			$cont .= SimplyRetsApiHelper::retrieveWidgetListing( $mlsid );
		} else {
			$cont = "No listing found";
		}

		$cont .= $after_widget;
		echo $cont;
	}

}

class srRandomListingWidget extends WP_Widget {

	/** constructor */
	function srRandomListingWidget() {
		parent::WP_Widget(false, $name = 'SimplyRETS Random Listing');
	}

	/** save widget --  @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['mlsids'] = strip_tags($new_instance['mlsids']);
		return $instance;
	}

	/** admin widget form --  @see WP_Widget::form */
	function form( $instance ) {
		$title  = esc_attr($instance['title']);
		$mlsids = esc_attr($instance['mlsids']);

		?>
		<p>
		  <label for="<?php echo $this->get_field_id('title'); ?>">
			<?php _e('Title:'); ?>
		  </label>
		  <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
				 name="<?php echo $this->get_field_name('title'); ?>"
				 type="text"
				 value="<?php echo $title; ?>" />
		</p>

		<p>
		  <label for="<?php echo $this->get_field_id('mlsids'); ?>">
			<?php _e('MLS Id\'s (comma separated):'); ?>
		  </label>
		  <input class="widefat"
				 id="<?php echo $this->get_field_id('mlsids'); ?>"
				 name="<?php echo $this->get_field_name('mlsids'); ?>"
				 type="text"
				 value="<?php echo $mlsids; ?>" />
		</p>
		<?php
	}

	/** front end widget render -- @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract( $args );

		$title  = apply_filters('widget_title', $instance['title']);
		$mlsids = $instance['mlsids'];
        $mlsids_arr = explode( ',', $mlsids );

        $mlsid = $mlsids_arr[array_rand($mlsids_arr)];

		$cont .= $before_widget;
		// populate title
		if( $title ) {
			$cont .= $before_title . $title . $after_title;
		} else {
			$cont .= $before_title . "Featured Listing" .$after_title;
		}

		// populate content
		if( $mlsid ) {
			$cont .= SimplyRetsApiHelper::retrieveWidgetListing( $mlsid );
		} else {
			$cont = "No listing found";
		}

		$cont .= $after_widget;
		echo $cont;
	}

}

class srSearchFormWidget extends WP_Widget {

	/** constructor */
	function srSearchFormWidget() {
		parent::WP_Widget(false, $name = 'SimplyRETS Search Widget');
	}

	/** save widget --  @see WP_Widget::update */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}

	/** admin widget form --  @see WP_Widget::form */
	function form( $instance ) {
		$title  = esc_attr($instance['title']);

		?>
		<p>
		  <label for="<?php echo $this->get_field_id('title'); ?>">
			<?php _e('Title:'); ?>
		  </label>
		  <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
				 name="<?php echo $this->get_field_name('title'); ?>"
				 type="text"
				 value="<?php echo $title; ?>" />
		</p>
        <?php
	}

	/** front end widget render -- @see WP_Widget::widget */
	function widget( $args, $instance ) {
		extract( $args );

		$title  = apply_filters('widget_title', $instance['title']);

		$cont .= $before_widget;
		// populate title
		if( $title ) {
			$cont .= $before_title . $title . $after_title;
		} else {
			$cont .= $before_title . $after_title;
		}

        $home_url = get_home_url();
        $search_form_markup = <<<HTML
        <div class="sr-search-widget">
          <form method="get" class="sr-search" action="$home_url">
            <input type="hidden" name="sr-listings" value="sr-search">

            <div class="sr-search-field" id="sr-search-keywords">
              <input name="sr_keywords" type="text" placeholder="Subdivision, Zipcode, or Keywords" />
            </div>

            <div class="sr-search-field" id="sr-search-ptype">
              <select name="sr_ptype">
                <option value="">Property Type</option>
                <option value="res">Residential</option>
                <option value="cnd">Condo</option>
                <option value="rnt">Rental</option>
              </select>
            </div>

            <div class="sr-search-widget-filters">
              <div class="sr-search-widget-field" id="sr-search-minprice">
                <input name="sr_minprice" type="number" placeholder="Min Price.." />
              </div>
              <div class="sr-search-widget-field" id="sr-search-maxprice">
                <input name="sr_maxprice" type="number" placeholder="Max Price.." />
              </div>

              <div class="sr-search-widget-field" id="sr-search-minbeds">
                <input name="sr_minbeds" type="number" placeholder="Min Beds.." />
              </div>
              <div class="sr-search-widget-field" id="sr-search-maxbeds">
                <input name="sr_maxbeds" type="number" placeholder="Max Beds.." />
              </div>

              <div class="sr-search-widget-field" id="sr-search-minbaths">
                <input name="sr_minbaths" type="number" placeholder="Min Baths.." />
              </div>
              <div class="sr-search-widget-field" id="sr-search-maxbaths">
                <input name="sr_maxbaths" type="number" placeholder="Max Baths.." />
              </div>
            </div>

            <input class="submit button btn" type="submit" value="Search Properties">

          </form>
        </div>
HTML;

		// populate content
        $cont .= $search_form_markup;

		$cont .= $after_widget;
		echo $cont;
	}

}