<?php

/*
 *
 * simply-rets-api-helper.php - Copyright (C) Reichert Brothers 2014
 * This file provides a class that has functions for retrieving and parsing
 * data from the remote retsd api.
 *
 *
*/

/* Code starts here */

class SimplyRetsApiHelper {



    public static function retrieveRetsListings( $params ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $params );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsApiHelper::srResidentialResultsGenerator( $request_response );

        return $response_markup;
    }


    public static function retrieveListingDetails( $listing_id ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $listing_id );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsApiHelper::srResidentialDetailsGenerator( $request_response );

        return $response_markup;
    }

    public static function retrieveWidgetListing( $listing_id ) {
        $request_url      = SimplyRetsApiHelper::srRequestUrlBuilder( $listing_id );
        $request_response = SimplyRetsApiHelper::srApiRequest( $request_url );
        $response_markup  = SimplyRetsApiHelper::srWidgetListingGenerator( $request_response );

        return $response_markup;
    }


    /*
     * This function build a URL from a set of parameters that we'll use to
     * requst our listings from the SimplyRETS API.
     *
     * @params is either an associative array in the form of [filter] => "val"
     * or it is a single listing id as a string, ie "123456".
     *
     * query variables for filtering will always come in as an array, so it
     * this is true, we can build a query off the standard /properties URL.
     *
     * If we do /not/ get an array, thenw we know we are requesting a single
     * listing, so we can just build the url with /properties/{ID}
     *
     * base url for local development: http://localhost:3001/properties
    */
    public static function srRequestUrlBuilder( $params ) {
        $authid   = get_option( 'sr_api_name' );
        $authkey  = get_option( 'sr_api_key' );
        $base_url = "http://{$authid}:{$authkey}@54.187.230.155/properties";

        if( is_array( $params ) ) {
            $filters_query = http_build_query( array_filter( $params ) );
            $request_url = "{$base_url}?{$filters_query}";
            return $request_url;

        } else {
            $request_url = $base_url . '/' . $params;
            return $request_url;

        }

    }


    /**
     * Make the request the SimplyRETS API. We try to use
     * cURL first, but if it's not enabled on the server, we
     * fall back to file_get_contents().
    */
    public static function srApiRequest( $url ) {
        $wp_version = get_bloginfo('version');
        $php_version = phpversion();

        $ua_string     = "SimplyRETSWP/1.1.1 Wordpress/{$wp_version} PHP/{$php_version}";
        $accept_header = "Accept: application/json; q=0.2, application/vnd.simplyrets-v0.1+json";

        if( is_callable( 'curl_init' ) ) {
            $ch = curl_init();
            $curl_info = curl_version();
            $curl_version = $curl_info['version'];
            $headers[] = $accept_header;
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            curl_setopt( $ch, CURLOPT_USERAGENT, $ua_string . " cURL/{$curl_version}" );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            $request = curl_exec( $ch );
            $response_array = json_decode( $request );
            curl_close( $ch );

        } else {
            $options = array(
                'http' => array(
                    'header' => $accept_header,
                    'user_agent' => $ua_string
                )
            );
            $context = stream_context_create( $options );
            $request = file_get_contents( $url, false, $context );
            $response_array = json_decode( $request );
        }

        if( $response_array === FALSE || empty($response_array) ) {
            $error =
                "Sorry, SimplyRETS could not complete this search." .
                "Please double check that your API credentials are valid " .
                "and that the search filters you used are correct. If this " .
                "is a new listing, you may also try back later.";
            $response_err = array(
                "error" => $error
            );
            return  $response_err;
        }

        return $response_array;
    }



    public static function simplyRetsClientCss() {
        wp_register_style( 'simply-rets-client-css', plugins_url( 'css/simply-rets-client.css', __FILE__ ) );
        wp_enqueue_style( 'simply-rets-client-css' );
    }

    public static function simplyRetsClientJs() {
        wp_register_script( 'simply-rets-client-js',
                            plugins_url( 'js/simply-rets-client.js', __FILE__ ),
                            array('jquery')
        );
        wp_enqueue_script( 'simply-rets-client-js' );
    }




    public static function srResidentialDetailsGenerator( $listing ) {
        $br = "<br>";
        $cont = "";
        $contact_page = get_option( 'sr_contact_page' );

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if( $listing == NULL ) {
            $err = "SimplyRETS could not complete this search. Please check your " .
                "credentials and try again.";
            return $err;
        }
        if( array_key_exists( "error", $listing ) ) {
            $error = $listing['error'];
            $cont .= "<hr><p>{$error}</p>";
            return $cont;
        }

        // stories
        $stories = $listing->property->stories;
        if( $stories == "" ) {
            $stories = "";
        } else {
            $stories = <<<HTML
                <tr>
                  <td>Stories</td>
                  <td>$stories</td></tr>
HTML;
        }
        // fireplaces
        $fireplaces = $listing->property->fireplaces;
        if( $fireplaces == "" ) {
            $fireplaces = "";
        } else {
            $fireplaces = <<<HTML
                <tr>
                  <td>Fireplaces</td>
                  <td>$fireplaces</td></tr>
HTML;
        }

        // lot size
        $lotSize          = $listing->property->lotSize;
        if( $lotSize == 0 ) {
            $lot_sqft = 'n/a';
        } else {
            $lot_sqft    = number_format( $lotSize );
        }


        // photos data (and set up slideshow markup)
        $photos = $listing->photos;
        if(empty($photos)) {
             $main_photo = plugins_url( 'img/defprop.jpg', __FILE__ );
        } else {
            $main_photo = $photos[0];
            $photo_counter = 0;
            foreach( $photos as $photo ) {
                $photo_markup .= "<input class=\"sr-slider-input\" type=\"radio\" name=\"slide_switch\" id=\"id$photo_counter\" value=\"$photo\"/>";
                $photo_markup .= "<label for='id$photo_counter'>";
                $photo_markup .= "  <img src='$photo' width='100'>";
                $photo_markup .= "</label>";
                $photo_counter++;
            }
        }

        // geographic data
        $geo_directions = $listing->geo->directions;
        if( $geo_directions == "" ) {
            $geo_directions = "";
        } else {
            $geo_directions = <<<HTML
              <thead>
                <tr>
                  <th colspan="2"><h5>Geographical Data</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Direction</td>
                  <td>$geo_directions</td></tr>
HTML;
        }
        // Long
        $geo_longitude = $listing->geo->lng;
        if( $geo_longitude == "" ) {
            $geo_longitude  = "";
        } else {
            $geo_longitude = <<<HTML
                <tr>
                  <td>Longitude</td>
                  <td>$geo_longitude</td></tr>
HTML;
        }
        // Long
        $geo_latitude = $listing->geo->lat;
        if( $geo_latitude == "" ) {
            $geo_latitude  = "";
        } else {
            $geo_latitude = <<<HTML
                <tr>
                  <td>Latitude</td>
                  <td>$geo_latitude</td></tr>
HTML;
        }
        // Long
        $geo_county= $listing->geo->county;
        if( $geo_county == "" ) {
            $geo_county   = "";
        } else {
            $geo_county = <<<HTML
                <tr>
                  <td>Latitude</td>
                  <td>$geo_county</td></tr>
HTML;
        }


        // school zone data
        $school_data = $listing->school->district;
        if( $school_data == "" ) {
            $school_data = "";
        } else {
            $school_data  = <<<HTML
                <tr>
                  <td>School Zone</td>
                  <td>$school_data</td></tr>
HTML;
        }

        // days on market
        $days_on_market = $listing->mls->daysOnMarket;
        if( $days_on_market == "" ) {
            $days_on_market = "";
        } else {
            $days_on_market = <<<HTML
                <tr>
                  <td>Days on Market</td>
                  <td>$days_on_market</td></tr>
HTML;
        }

        // mls area
        $mls_area       = $listing->mls->area;
        if( $mls_area == "" ) {
            $mls_area = "";
        } else {
            $mls_area = <<<HTML
                <tr>
                  <td>MLS Area</td>
                  <td>$mls_area</td></tr>
HTML;
        }

        // tax data
        $tax_data    = $listing->tax->id;
        if( $tax_data == "" ) {
            $tax_data = "";
        } else {
            $tax_data = <<<HTML
                <tr>
                  <td>Tax Data</td>
                  <td>$tax_data</td></tr>
HTML;
        }

        // Amenities
        $bedrooms         = $listing->property->bedrooms;
        $bathsFull        = $listing->property->bathsFull;
        $interiorFeatures = $listing->property->interiorFeatures;
        $style            = $listing->property->style;
        $heating          = $listing->property->heating;
        $exteriorFeatures = $listing->property->exteriorFeatures;
        $yearBuilt        = $listing->property->yearBuilt;
        $subdivision      = $listing->property->subdivision;
        $roof             = $listing->property->roof;
        // listing meta information
        $listing_modified    = $listing->modified; // TODO: format date
        $date_modified       = date("M j, Y", strtotime($listing_modified));
        $list_date           = $listing->listDate;
        $list_date_formatted = date("M j, Y", strtotime($list_date));

        $disclaimer  = $listing->disclaimer;
        $listing_uid = $listing->mlsId;
        // street address info
        $postal_code   = $listing->address->postalCode;
        $country       = $listing->address->country;
        $address       = $listing->address->full;
        $city          = $listing->address->city;
        // Listing Data
        $listing_office   = $listing->office->name;
        $list_date        = $listing->listDate;
        $listing_price    = $listing->listPrice;
        $listing_USD      = '$' . number_format( $listing_price );
        $listing_remarks  = $listing->remarks;

        // agent data
        $listing_agent_id    = $listing->agent->id;
        $listing_agent_name  = $listing->agent->firstName;
        $listing_agent_email = $listing->agent->contact->email;
        if( !$listing_agent_email == "" ) {
            $listing_agent_name = "<a href='mailto:$listing_agent_email'>$listing_agent_name</a>";
        }

        // mls information
        $mls_status     = $listing->mls->status;

        // listing markup
        $cont .= <<<HTML
          <div class="sr-details" style="text-align:left;">
            <p class="sr-details-links" style="clear:both;">
              <span id="sr-toggle-gallery">See more photos</span> |
              <span id="sr-listing-contact">
                <a href="$contact_page">Contact us about this listing</a>
              </span>
            </p>
            <div class="sr-slider">
              <img class="sr-slider-img-act" src="$main_photo">
              $photo_markup
            </div>
            <div class="sr-primary-details">
              <div class="sr-detail" id="sr-primary-details-beds">
                <h3>$bedrooms <small>Beds</small></h3>
              </div>
              <div class="sr-detail" id="sr-primary-details-baths">
                <h3>$bathsFull <small>Baths</small></h3>
              </div>
              <div class="sr-detail" id="sr-primary-details-size">
                <h3>$lot_sqft <small>SqFt</small></h3>
              </div>
              <div class="sr-detail" id="sr-primary-details-status">
                <h3>$mls_status</h3>
              </div>
            </div>
            <div class="sr-remarks-details">
              <p>$listing_remarks</p>
            </div>

            <table style="width:100%;">
              <thead>
                <tr>
                  <th colspan="2"><h5>Listing Details</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Price</td>
                  <td>$listing_USD</td></tr>
                <tr>
                  <td>Bedrooms</td>
                  <td>$bedrooms</td></tr>
                <tr>
                  <td>Full Bathrooms</td>
                  <td>$bathsFull</td></tr>
                <tr>
                  <td>Interior Features</td>
                  <td>$interiorFeatures</td></tr>
                <tr>
                  <td>Property Style</td>
                  <td>$style</td></tr>
                <tr>
                  <td>Heating</td>
                  <td>$heating</td></tr>
                $stories
                <tr>
                  <td>Exterior Features</td>
                  <td>$exteriorFeatures</td></tr>
                <tr>
                  <td>Year Built</td>
                  <td>$yearBuilt</td></tr>
                <tr>
                  <td>Lot Size</td>
                  <td>$lot_sqft SqFt</td></tr>
                $fireplaces
                <tr>
                  <td>Subdivision</td>
                  <td>$subdivision</td></tr>
                <tr>
                  <td>Roof</td>
                  <td>$roof</td></tr>
              </tbody>
                $geo_directions
                $geo_county
                $geo_latitude
                $geo_longitude
              </tbody>
              <thead>
                <tr>
                  <th colspan="2"><h5>Listing Meta Data</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Listing date</td>
                  <td>$list_date_formatted</td></tr>
                <tr>
                  <td>Listing last modified</td>
                  <td>$date_modified</td></tr>
                $school_data
                <tr>
                  <td>Disclaimer</td>
                  <td>$disclaimer</td></tr>
                $tax_data
                <tr>
                  <td>Listing Id</td>
                  <td>$listing_uid</td></tr>
              </tbody>
              <thead>
                <tr>
                  <th colspan="2"><h5>Address Information</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Postal Code</td>
                  <td>$postal_code</td></tr>
                <tr>
                  <td>Country Code</td>
                  <td>$country</td></tr>
                <tr>
                  <td>Address</td>
                  <td>$address</td></tr>
                <tr>
                  <td>City</td>
                  <td>$city</td></tr>
              </tbody>
              <thead>
                <tr>
                  <th colspan="2"><h5>Listing Information</h5></th></tr></thead>
              <tbody>
                <tr>
                  <td>Listing Office</td>
                  <td>$listing_office</td></tr>
                <tr>
                  <td>Listing Agent</td>
                  <td>$listing_agent_name</td></tr>
                <tr>
                  <td>Remarks</td>
                  <td>$listing_remarks</td></tr>
              </tbody>
              <thead>
                <tr>
                  <th colspan="2"><h5>Mls Information</h5></th></tr></thead>
              <tbody>
                $days_on_market
                <tr>
                  <td>Mls Status</td>
                  <td>$mls_status</td></tr>
                $mls_area
              </tbody>
            </table>
          </div>
HTML;

        return $cont;
    }


    public static function srResidentialResultsGenerator( $response ) {
        $br = "<br>";
        $cont = "";

        // echo '<pre><code>';
        // var_dump( $response );
        // echo '</pre></code>';

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if( $response == "NULL" ) {
            $err = "SimplyRETS could not complete this search. Please check your " .
                "credentials and try again.";
            return $err;
        }
        if( array_key_exists( "error", $response ) ) {
            $error = "SimplyRETS could not find any properties matching your criteria. Please try another search.";
            $response_markup = "<hr><p>{$error}</p><br>";
            return $response_markup;
        }

        $response_size = sizeof( $response );
        if( $response_size < 1 ) {
            $response = array( $response );
        }

        foreach ( $response as $listing ) {
            // id
            $listing_uid      = $listing->mlsId;
            // Amenities
            $bedrooms    = $listing->property->bedrooms;
            $bathsFull   = $listing->property->bathsFull;
            $lotSize     = $listing->property->lotSize; // might be empty
            if( $lotSize == 0 ) {
                $lot_sqft = 'n/a';
            } else {
                $lot_sqft    = number_format( $lotSize );
            }
            $subdivision = $listing->property->subdivision;
            $yearBuilt   = $listing->property->yearBuilt;
            // listing data
            $listing_agent_id    = $listing->agent->id;
            $listing_agent_name  = $listing->agent->firstName;

            $listing_price    = $listing->listPrice;
            $list_date        = $listing->listDate;
            $list_date_formatted = date("M j, Y", strtotime($list_date));
            $listing_USD = '$' . number_format( $listing_price );
            // street address info
            $city    = $listing->address->city;
            $address = $listing->address->full;
            // listing photos
            $listingPhotos = $listing->photos;
            if( empty( $listingPhotos ) ) {
                $listingPhotos[0] = plugins_url( 'img/defprop.jpg', __FILE__ );
            }
            $main_photo = trim($listingPhotos[0]);

            $listing_link = get_home_url() . "/?sr-listings=sr-single&listing_id=$listing_uid&listing_price=$listing_price&listing_title=$address";
            // append markup for this listing to the content
            $cont .= <<<HTML
              <hr>
              <div class="sr-listing">
                <a href="$listing_link">
                  <div class="sr-photo" style="background-image:url('$main_photo');">
                  </div>
                </a>
                <div class="sr-primary-data">
                  <a href="$listing_link">
                    <h4>$address
                    <span id="sr-price"><i>$listing_USD</i></span></h4>
                  </a>
                </div>
                <div class="sr-secondary-data">
                  <ul class="sr-data-column">
                    <li>
                      <span>$bedrooms Bedrooms</span>
                    </li>
                    <li>
                      <span>$bathsFull Full Baths</span>
                    </li>
                    <li>
                      <span>$lot_sqft SqFt</span>
                    </li>
                    <li>
                      <span>Built in $yearBuilt</span>
                    </li>
                  </ul>
                  <ul class="sr-data-column">
                    <li>
                      <span>$subdivision</span>
                    </li>
                    <li>
                      <span>The City of $city</span>
                    </li>
                    <li>
                      <span>Listed by $listing_agent_name</span>
                    </li>
                    <li>
                      <span>Listed on $list_date_formatted</span>
                    </li>
                  </ul>
                </div>
                <div style="clear:both;">
                  <a href="$listing_link">More details</a>
                </div>
              </div>
HTML;
        }

        $cont .= "<br><p><small><i>This information is believed to be accurate, but without any warranty.</i></small></p>";
        return $cont;
    }


    public static function srWidgetListingGenerator( $response ) {
        $br = "<br>";
        $cont = "";

        // echo '<pre><code>';
        // var_dump( $response );
        // echo '</pre></code>';

        /*
         * check for an error code in the array first, if it's
         * there, return it - no need to do anything else.
         * The error code comes from the UrlBuilder function.
        */
        if( $response == NULL ) {
            $err = "SimplyRETS could not complete this search. Please check your " .
                "credentials and try again.";
            return $err;
        }
        if( array_key_exists( "error", $response ) ) {
            $error = $response['error'];
            $response_markup = "<hr><p>{$error}</p>";
            return $response_markup;
        }

        $response_size = sizeof( $response );
        if( $response_size <= 1 ) {
            $response = array( $response );
        }

        foreach ( $response as $listing ) {
            $listing_uid      = $listing->mlsId;
            // widget details
            $bedrooms    = $listing->property->bedrooms;
            $bathsFull   = $listing->property->bathsFull;
            $mls_status    = $listing->mls->status;
            $listing_remarks  = $listing->remarks;
            $listing_price = $listing->listPrice;
            $listing_USD   = '$' . number_format( $listing_price );

            // widget title
            $address = $listing->address->full;

            // widget photo
            $listingPhotos = $listing->photos;
            if( empty( $listingPhotos ) ) {
                $listingPhotos[0] = plugins_url( 'img/defprop.jpg', __FILE__ );
            }
            $main_photo = $listingPhotos[0];

            // create link to listing
            $listing_link = get_home_url() . "/?sr-listings=sr-single&listing_id=$listing_uid&listing_price=$listing_price&listing_title=$address";

            // append markup for this listing to the content
            $cont .= <<<HTML
              <div class="sr-listing-wdgt">
                <a href="$listing_link">
                  <h5>$address
                    <small> - $listing_USD </small>
                  </h5>
                </a>
                <a href="$listing_link">
                  <img src="$main_photo" width="100%" alt="$address">
                </a>
                <div class="sr-listing-wdgt-primary">
                  <div id="sr-listing-wdgt-details">
                    <span>$bedrooms Bed | $bathsFull Bath | $mls_status </span>
                  </div>
                  <hr>
                  <div id="sr-listing-wdgt-remarks">
                    <p>$listing_remarks</p>
                  </div>
                </div>
                <div id="sr-listing-wdgt-btn">
                  <a href="$listing_link">
                    <button class="button btn">
                      More about this listing
                    </button>
                  </a>
                </div>
              </div>
HTML;

        }
        return $cont;
    }

}
