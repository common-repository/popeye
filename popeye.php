<?php
/*
Plugin Name: wp-popeye
Plugin URI: http://chrp.es/wp-popeye
Description: This plugin presents images from the Wordpress media library in a nice and elegant way within your posts and pages.
Author: Christoph Peschel
Version: 0.2.5
Author URI: http://chrp.es/

Copyright 2009  Christoph Peschel  (email: hi@chrp.es)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('PPY_JQ_VERSION', '2.0.4'); //the version of popeye itself is the main version nr of this plugin
define('PPY_WP_PLUGIN_VERSION', '0.2.5'); //subversion of this wp plugin
define('PPY_DEBUG', 0); //debug mode on = 1 / off = 0

define('PPY_DONATION_LINK', 'https://flattr.com/thing/11381/wp-popeye-wordpress-inline-gallery-plugin');
define('PPY_FEEDBACK_LINK', 'http://wppopeye.uservoice.com/');
define('PPY_CHRISTOPH_SCHUESSLER_LINK', 'http://dev.herr-schuessler.de/jquery/popeye/');

// /////////////////////////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////////////////////////
// plugin install/-uninstall

register_activation_hook( __FILE__, 'ppy_activate' );
add_filter('plugin_row_meta', 'ppy_plugin_links', 10, 2);
add_action('plugin_action_links_' . plugin_basename(__FILE__), 'ppy_plugin_actions');

function ppy_activate()
{
  //set initial options
	$ppy_options = ppy_get_options(null, true);
	
  if($ppy_options == 'nothing here yet')
    add_option('ppy_options', ppy_default_options(), 'The Popeye Settings', 'yes');
    
  //say hello when this plugin is activated
  if(get_option( 'ppy_say_hello', 'nothing here yet' ) == 'nothing here yet')
    add_option('ppy_say_hello', 'yes', 'The Popeye says hello', 'no');
  else
    update_option('ppy_say_hello', 'yes');
}

/*
 * Adds links for donation and the original jquery plugin
 */
function ppy_plugin_links($links, $file)
{
  if ( $file == plugin_basename(__FILE__) )
  {
    $links[] = '<a href="' . PPY_CHRISTOPH_SCHUESSLER_LINK . '#">About jquery.Popeye</a>';
    $links[] = '<a href="' . PPY_FEEDBACK_LINK . '#">Feedback</a>';
    $links[] = '<a href="' . PPY_DONATION_LINK . '#">Donate</a>';
  }
  
  return $links;
}


/**
 * Add settings option to plugin actions on "Manage Plugins" site
 * @param $links
 * @return array
 */
function ppy_plugin_actions($links) 
{
  $links[] = '<a href="options-general.php?page=' . basename(__FILE__) . '">Settings</a>';
  return $links;
}

// show hint where to find the ppy settings if you've never seen it before /////////////////////////

if(is_admin() && get_option( 'ppy_say_hello', 'yes' ) == 'yes') 
  add_action('admin_notices', 'ppy_say_hello');

function ppy_say_hello() 
{
  ?><div id='ppy-hello' class='updated fade'>
    <p><strong>Popeye is ready.</strong> 
    You can learn how to use it or configure its behaviour using the <a href="options-general.php?page=popeye">popeye settings page</a>.
    </p></div><?php 
}

// /////////////////////////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////////////////////////
// frontend runtime functions

if(!is_admin())
{
  add_action('init', 'ppy_init', 10); //Load popeye js and css files with the page
  add_action('wp_head', 'ppy_jquery_loader', 80); //Activate JS function in the page header
  add_shortcode('popeye', 'ppy_shortcode_handler'); //Handle popeye shortcode
  add_shortcode('ppy', 'ppy_shortcode_handler'); //Handle popeye's even shorter shortcode
  add_filter( 'the_content', 'ppy_content_filter' ); //Automatically insert popeye into content
  
  $ppy_counter = 1; //Counts the number of shortcode replacements, used e.g. for html id attributes
}

function ppy_init()
{
	$jQver = PPY_JQ_VERSION;
	$pVer = PPY_WP_PLUGIN_VERSION;
	$min = 'min.'; // 'min.';
  $style = ppy_get_current_style();
	
	wp_enqueue_script('popeye',
	 WP_PLUGIN_URL . "/popeye/jquery/jquery.popeye-$jQver.{$min}js",
	 array('jquery'),
	 $pVer );
	 
	wp_enqueue_style( 'popeye-base', 
	 WP_PLUGIN_URL . "/popeye/jquery/jquery.popeye.css", 
	 array(), 
	 $pVer, 'screen' );

	wp_enqueue_style( 'popeye-style', 
	 WP_PLUGIN_URL . "/popeye/styles/$style/style.css", 
	 array('popeye-base'), 
	 $pVer, 'screen' );
}

function ppy_shortcode_handler($atts, $content=null, $code="")
{
	global $post;
  $ids = array();
	
  //get the options: join the options of a certain style and the ones set by attributes
	// 1. is there a special style set?
	// 2. load default options and style options
	// 3. override with attributes
	
    // 1. 
    $useCustomCssClass = false;
    $style = null; 
    if( isset($atts['style']) )
    {
    	$style = $atts['style'];
    	$useCustomCssClass = true;  
    }
    
    // 2. 
    $options = ppy_get_options($style);
    
    // 3. 
		foreach($options as $key => $value)
	    if(isset($atts[$key]) && $key != 'style') 
	    {
	    	$options[$key] = $atts[$key];
	    	$useCustomCssClass = true;
	    }
	    
    
  // ---
	    
  //if 'ids' attribute is not set we grab the attached images
	if(!isset($atts['ids'])) 
    $ids = ppy_get_image_attachments_of_post($post->ID, $options);
	
	//or use images ids explicitly
	else
    $ids = explode(';', str_replace(',', ';', $atts['ids']));

  //include some more ids
  // !!! - images included by attribute are appended in given order   
  if(isset($atts['include']))
  {
  	$ids = array_merge( $ids, explode(';', str_replace(',', ';', $atts['include'])) );
    foreach($ids as &$id)
      $id = intval(trim($id));
  	$ids = array_unique($ids);
  }
    
  //exclude ids
  if(isset($atts['exclude']))
    $ids = array_diff( $ids, explode(';', str_replace(',', ';', $atts['exclude'])) ); 
    
  //return html code
  return ppy_generate_html($ids, $options, $useCustomCssClass);
}

/**
 * Returns the images attached to a post 
 *
 * @param int $postId
 * @return array
 */
function ppy_get_image_attachments_of_post( $postId, $options )
{
  $ids = array();
  
  $args = array(
    'post_type' => 'attachment',
    'post_parent' => $postId,
	  'post_mime_type' => 'image',
	  'orderby' => $options['order'],
	  'numberposts' => -1
  );
  $attachments = get_posts($args);
  
  if ($attachments) 
    foreach ($attachments as $post) 
      $ids[] = $post->ID;

  $ids = array_reverse($ids); //popeye somehow orders them reversed
  return $ids;
}

/**
 * Returns the html structure to display a popeye gallery
 * 
 * @param $ids An array of post ids, should be images
 * @param $options The option set of popeye, an array
 * @param $useCustomOptions Boolean value which desides wheter to rely on the general css-class or to create an own one
 * @return string
 * @see http://bueltge.de/wordpress-attachment-metadaten-nutzen/625/
 */
function ppy_generate_html($ids = array(), $options = array(), $useCustomOptions = false)
{
	global $ppy_counter;
  $style = $options['style'];
  $htmlId = 'ppy' . $ppy_counter;
  $classId = 'ppy'; 
  
  $htmlPerImage = '<li><a href="%s"><img src="%s" /></a><span class="ppy-extcaption">%s</span></li>' . "\n"; // large image url, thumb url, caption string 
  $htmlImageList = '';
  
  $stageWidth = 0;
  $stageHeight = 0;
  
  foreach($ids as $id)
    if(wp_attachment_is_image($id))
    {
      $post = get_post($id);
      $enlargedImgSrc = wp_get_attachment_image_src($id, $options['enlarge_size']);
      $viewImgSrc = wp_get_attachment_image_src($id, $options['view_size']);
      
      $htmlImageList .= sprintf($htmlPerImage, 
        $enlargedImgSrc[0],
        $viewImgSrc[0], 
        !empty($post->post_content) ? $post->post_content : $post->post_excerpt
      );
      
      $stageWidth = max($viewImgSrc[1], $stageWidth);
      $stageHeight = max($viewImgSrc[2], $stageHeight);
    }
  
  $template = file_get_contents("wp-content/plugins/popeye/styles/$style/structure.template");
  $template = str_replace('{id}', $htmlId, $template );
  $template = str_replace('{ppyClass}', $classId, $template );
  $template = str_replace('{align}', $options['alignment'], $template );
  $template = str_replace('{imageList}', $htmlImageList, $template );
  $template = str_replace('{stageWidth}', $stageWidth, $template );
  $template = str_replace('{stageHeight}', $stageHeight, $template );
  
  //TODO l10n?
  //http://codex.wordpress.org/Function_Reference/load_plugin_textdomain
  
  //Load JS explicitly for this instance of ppy if it is not activated by the general css class
  if($useCustomOptions)
    ppy_jquery_loader($options, $htmlId);
  
  $ppy_counter++;
  return $template;
}

/**
 * Prints the html script section to convert elements of a certain css class to popeye galleries
 * 
 * @param $options  The option set of popeye, an array or null for the default options
 * @param $cssClassName  The css class to assign as popeye gallery
 * @return nothing
 */
function ppy_jquery_loader($options = null, $identifier = '.ppy')
{
	//Load default options if none given
	if(empty($options)) $options = ppy_get_options();
	
	#var_dump($options); die();
	
	//Collect popeye options
  $ppyOptions = array(
    'navigation' => "'" . $options['navigation'] . "'",
    'direction' => $options['alignment'] == 'right' ? "'left'" : "'right'",
    'caption' => $options['caption'] == 'none' ? 'false' : "'" . $options['caption'] . "'",
    'opacity' => $options['opacity'],
    'duration' => $options['duration'],
    'debug' => $options['debug'],
    'zindex' => $options['z-index']
    //easing is done below
  );
	
  //Put the options into a string
  $ppyOptionsString = '';
  foreach($ppyOptions as $key => $value)
    $ppyOptionsString .= "        $key: $value,\n";
  $ppyOptionsString .= "        easing: '" . $options['easing'] . "'\n";    
  
  //Echo the script-section
  ?><script type="text/javascript">
  <!--//<![CDATA[
    jQuery(document).ready(function () {
      jQuery('<?php echo $identifier ?>').popeye({
<?php echo $ppyOptionsString ?>
      });
    });
  //]]>-->
</script><?php   
}

/**
 * Parses the content of posts and pages and inserts popeye automatically, depending on the mode 
 * selected
 *
 * @param string $text
 * @return string
 */
function ppy_content_filter($text)
{
	//http://codex.wordpress.org/Plugin_API/Filter_Reference/the_content
	
	$options = ppy_get_options(null, true);
	$mode = $options['mode'];
	
	//Only manual mode? Or is there already a popeye shortcode present?
	if( $mode == 'manual' ||
	    strpos($text, '[popeye') !== false || 
	    strpos($text, '[ppy') !== false ) return $text; 
	    
	//Suppress popeye display
	if( strpos($text, '[nopopeye]') !== false ) return str_replace('[nopopeye]', '', $text);
	
	//Determining insertion pos
	$textPos = 0;

  //Searching for beginnings of paragraphs as possible random insertion points
	$pattern = '!<p>!i'; 
  $result = preg_match_all($pattern, $text, $subpattern, PREG_OFFSET_CAPTURE); 

  //Nothing found? Very bad ... we assume 1
  if($result) 
  {
	  //Gathering all positions
	  $possiblePositions = array();
	  foreach($subpattern[0] as $match)
	    $possiblePositions[] = $match[1];
	  $numOfParagraphs = count($possiblePositions);

	  switch($mode)
	  {
	  	case 'random': $textPos = $possiblePositions[array_rand($possiblePositions)]; break;
	  	case 'second': $textPos = $possiblePositions[min($numOfParagraphs-1,1)]; break;
	  	case 'third':  $textPos = $possiblePositions[min($numOfParagraphs-1,2)]; break;
	  }  
	}
	
	//Getting replacement
	$ids = ppy_get_image_attachments_of_post(get_the_ID(), $options);
	if(empty($ids)) $replace = '';
	else $replace = ppy_generate_html( $ids, ppy_get_options(), false ); 
	
	//Inserting popeye
	$text = substr($text, 0, $textPos) . $replace . @substr($text, $textPos);
	return $text;
}

// /////////////////////////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////////////////////////
// settings page

add_action('admin_menu', 'ppy_admin_add_options_page');
add_action('admin_init', 'ppy_admin_init');
  
function ppy_admin_add_options_page() 
{
	add_options_page('Popeye settings', 'Popeye', 'manage_options', 'popeye', 'ppy_options_page');
}

function ppy_options_page() 
{
	//We just say hello until one has seen the settings page once
  update_option('ppy_say_hello', 'no');
	
	?>
	<div class="wrap"><div id="icon-options-general" class="icon32"><br /></div></div>
  <h2>Popeye Settings</h2>
  <form action="options.php" method="post">
  
	<p>Popeye is a javascript gallery plugin based on jquery. Use it to present images in a nice 
	way within your posts. For further info visit the 
	<a href="http://chrp.es/wp-popeye">homepage</a> of the plugin.</p>
	
	<?php settings_fields('ppy_options'); ?>
	<?php do_settings_sections('popeye'); ?>
	
	<p class="submit">
	  <input type="submit" name="Submit" class="button-primary" value="Save Changes" />
	</p>
	</form>
	
	<div class="wrap"><div id="icon-tools" class="icon32"><br /></div>
	<h2>Usage help</h2>
	<p>If you are new to popeye here's the default way of how to add an image gallery to your post:</p>
	<ol>
	  <li>Upload you images using the <a href="media-new.php">media upload page</a></li>
	  <li>In the <a href="upload.php">media library</a> you can attach each image to a post or page you would like to let them appear in</li>
	  <li>While editing your post or page add a <code>[popeye]</code> where you want the popeye to be shown</li>
	  <li>Use the media gallery options (via "Add image" -&gt; Gallery) to order your images</li>
	  <li>Use this settings page to control the behaviour of your popeye</li>
	  <li>If you want popeye to appear in your posts automatically or you want to learn all possibilites of the <code>[popeye]</code> shortcode visit the <a href="http://chrp.es/wp-popeye">documentation</a></li>
	</ol>
	
	<div class="clear"></div>
	</div>
	<?php
}


function ppy_admin_init(){
	register_setting( 'ppy_options', 'ppy_options', 'ppy_options_validate' ); //setting group name, setting name, validation callback function
	
	add_settings_section('ppy_layout', 'Gallery Layout', 'ppy_options_layout_section_output', 'popeye'); //unique name, heading, html output callback function, handler name for do_settings_sections
	add_settings_field('ppy_mode', 'Insertion mode', 'ppy_options_mode_output', 'popeye', 'ppy_layout');
  add_settings_field('ppy_style', 'Style', 'ppy_options_style_output', 'popeye', 'ppy_layout');
  add_settings_field('ppy_alignment', 'Alignment within text', 'ppy_options_alignment_output', 'popeye', 'ppy_layout');
  add_settings_field('ppy_order', 'Image order', 'ppy_options_order_output', 'popeye', 'ppy_layout');
  add_settings_field('ppy_navigation', 'Navigation display', 'ppy_options_navigation_output', 'popeye', 'ppy_layout');
  
  add_settings_section('ppy_images', 'Image sizes', 'ppy_options_images_section_output', 'popeye'); //unique name, heading, html output callback function, handler name for do_settings_sections
  add_settings_field('ppy_view_size', 'View size', 'ppy_options_view_size_output', 'popeye', 'ppy_images');
  add_settings_field('ppy_enlarge_size', 'Enlarged size', 'ppy_options_enlarge_size_output', 'popeye', 'ppy_images');
  
}

// Sections output functions ///////////////////////////////////////////////////////////////////////

function ppy_options_layout_section_output() 
{
  ?><p>Popeye can be inserted into the text by a shortcode <code>[popeye]</code> or automatically at the 
  beginning or randomly within a page or post. </p><?php
}
 
function ppy_options_images_section_output() 
{
  ?><p>Visit the <a href="options-media.php">miscellaneous settings page</a> to see the pixel resolutions.</p><?php
}

// Form elements output functions ////////////////////////////////////////////////////////////////// 

function ppy_options_navigation_output()
{
	$options = get_option('ppy_options');
  $opt = $options['navigation'];
  ?><select id="ppy_navigation" name="ppy_options[navigation]" size="1" style="width:460px">
    <option value="default" <?php   if($opt=='default')   echo 'selected="selected"' ?>>Default (depends on style)</option>
    <option value="hover" <?php     if($opt=='hover')     echo 'selected="selected"' ?>>Visible on hover</option>
    <option value="permanent" <?php if($opt=='permanent') echo 'selected="selected"' ?>>Always visible</option>
  </select><?php
}

function ppy_options_alignment_output()
{
	$options = get_option('ppy_options');
	$opt = $options['alignment'];
  ?><select id="ppy_alignment" name="ppy_options[alignment]" size="1" style="width:460px">
    <option value="left" <?php      if($opt=='left')     echo 'selected="selected"' ?>>Left, text floating right</option>
    <option value="right" <?php     if($opt=='right')    echo 'selected="selected"' ?>>Right, text floating left</option>
  </select><?php
}

function ppy_options_mode_output()
{
  $options = get_option('ppy_options');
  $opt = $options['mode'];
  ?><select id="ppy_mode" name="ppy_options[mode]" size="1" style="width:460px">
    <option value="manual" <?php   if($opt=='manual')   echo 'selected="selected"' ?>>M: Use popeye only manually by placing shortcodes ([popeye])</option>
    <option value="prepend" <?php  if($opt=='prepend')  echo 'selected="selected"' ?>>0: Add popeye to the beginning of posts without a popeye shortcode</option>
    <option value="second" <?php   if($opt=='second')   echo 'selected="selected"' ?>>1: Add popeye to the second paragraph of posts without a shortcode</option>
    <option value="third" <?php    if($opt=='third')    echo 'selected="selected"' ?>>2: Add popeye to the third paragraph of posts without a shortcode</option>
    <option value="random" <?php   if($opt=='random')   echo 'selected="selected"' ?>>R: Insert popeye in a random place into posts without a popeye shortcode</option>

  
  </select><?php  
}

function ppy_options_view_size_output()
{
	$options = get_option('ppy_options');
  $opt = $options['view_size'];
  ?><select name="ppy_options[view_size]" size="1" style="width:130px">
    <option value="thumb" <?php    if($opt=='thumbnail') echo 'selected="selected"' ?>>Thumbnail</option>
    <option value="medium" <?php   if($opt=='medium')    echo 'selected="selected"' ?>>Medium</option>
    <option value="large" <?php    if($opt=='large')     echo 'selected="selected"' ?>>Large</option>
  </select><?php
}

function ppy_options_enlarge_size_output()
{
  $options = get_option('ppy_options');
  $opt = $options['enlarge_size'];
  ?><select name="ppy_options[enlarge_size]" size="1" style="width:130px">
    <option value="medium" <?php   if($opt=='medium')    echo 'selected="selected"' ?>>Medium</option>
    <option value="large" <?php    if($opt=='large')     echo 'selected="selected"' ?>>Large</option>
    <option value="full" <?php     if($opt=='full')      echo 'selected="selected"' ?>>Original</option>
  </select><?php
}

function ppy_options_order_output()
{
  $options = get_option('ppy_options');
  $opt = $options['order'];
  ?><select name="ppy_options[order]" size="1" style="width:460px">
    <option value="menu_order" <?php  if($opt=='menu_order') echo 'selected="selected"' ?>>As set in media gallery (also called menu order)</option>
    <option value="title" <?php       if($opt=='title')      echo 'selected="selected"' ?>>By image title</option>
    <option value="post_date" <?php   if($opt=='post_date')  echo 'selected="selected"' ?>>By image upload time</option>
  </select><?php
}

function ppy_options_style_output()
{
  $opt = ppy_get_current_style();
  $optionHtml =  "<option value=\"%s\" %s>%s</option>\n";
  $styles = ppy_find_styles();

  //TODO make this a optgroup list and show the author/website more prominent & maybe allow a description
  
  echo '<select id="ppy_style" name="ppy_options[style]" size="1" style="width:460px">';
    
  foreach($styles as $style)
    echo sprintf( $optionHtml,
	                $style['dir'],
	                $opt == $style['dir'] ? 'selected="selected"' : '',
	                $style['name'] . ' ' . $style['version'] . ' (' . $style['author'] . ')' );  	

  echo '</select>';
}

function ppy_options_validate($input) 
{
  $output = ppy_default_options();
  foreach($output as $option => $doesntMatter)
  {
    $inVal = isset($input[$option]) ? strtolower($input[$option]) : null;
  	switch($option)
    {
      case 'navigation':
        if(in_array($inVal, array('permanent', 'hover','default'))) $output[$option] = $inVal;
        break;
    	
    	case 'duration':
      	if($inVal >= 0 && $inVal <= 5000) $output[$option] = $inVal; 
        break;
        
      case 'alignment':
      	if(in_array($inVal, array('left','right'))) $output[$option] = $inVal;
        break;
        
      case 'opacity':
        if($inVal > 1) $inVal = $inVal / 100;
        if($inVal >= 0 && $inVal <= 1) $output[$option] = $inVal;
        else if($inVal < 0) $output[$option] = 0;
        else $output[$option] = 1;
      	break;
        
      case 'view_size':
      	if(in_array($inVal, array('thumbnail', 'medium','large'))) $output[$option] = $inVal;
        break;

      case 'enlarge_size':
        if(in_array($inVal, array('medium','large','full'))) $output[$option] = $inVal;
        break;
        
      case 'order':
      	if(in_array($inVal, array('menu_order','post_date','title'))) $output[$option] = $inVal;
        #die($outpunt[$option]);
      	break;

      case 'style':
      	$output[$option] = $inVal;
      	break;
      
      case 'mode':
      	if(in_array($inVal, array('prepend','random', 'manual', 'second', 'third'))) $output[$option] = $inVal;
        break;
    }
  }
  
  return $output;
}

// /////////////////////////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////////////////////////
// options

/**
 * Returns a default options array
 * Contains all options which can be manipulated by the wp backend
 * 
 * @return array
 */
function ppy_default_options() 
{
  return array(
    'alignment' => 'left', 
    'view_size' => 'thumbnail',
    'enlarge_size' => 'large',
    'style' => 'example-plain',
    'mode' => 'manual',
    'order' => 'menu_order',
    'navigation' => 'default'
  );
}

/**
 * Options which only can be manipulated here and by style ini files
 *  
 * @return array
 */
function ppy_constants()
{
	return array(
	  'z-index' => 10000,
    'easing' => 'swing',
    'debug' => PPY_DEBUG,
    'navigation' => 'hover',
    'caption' => 'hover',
    'duration' => 240,	
    'opacity' => 0.7	
  );
}

/**
 * Returns the currently set options for popeye and the current or given style
 *
 * @param string $style
 * @param boolean $ignoreStyleOptions 
 * @return array
 */
function ppy_get_options($style = null, $ignoreStyleOptions = false)
{
	$options = array_merge(
	  ppy_constants(),
    get_option('ppy_options', ppy_default_options())
  );
	$navigationSetByUser = $options['navigation'];
  
	//if we can ignore style options an navigation (set in wp backend) is not set to default
  if($ignoreStyleOptions && $navigationSetByUser!='default')
    return $options;
  
  //overwrite options with style options and return this
  return ppy_override_by_style_options(empty($style) ? ppy_get_current_style() : $style, $options);
}

// /////////////////////////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////////////////////////
// styles

/**
 * Parses the styles dir and returns an associative array with all found styles. 
 * The subarray contains the 'dir' key and all info from the about section of the style ini file
 *
 * @return array
 */
function ppy_find_styles()
{
	$styles = array();
	$stylesDir = dirname(__FILE__) . '/styles';
	$dir = opendir($stylesDir);
	
	if($dir) while (false !== ($subdir = readdir($dir))) 
	  if( file_exists("$stylesDir/$subdir/settings.ini") && 
	      file_exists("$stylesDir/$subdir/structure.template") &&
	      file_exists("$stylesDir/$subdir/style.css") )
    {
        $ini = parse_ini_file("$stylesDir/$subdir/settings.ini", true);
        if( isset($ini['about']['name']) && 
            isset($ini['about']['author']) && 
            isset($ini['about']['version']) && 
            isset($ini['about']['website']) )
        {
        	$styles[$subdir] = $ini['about'];
        	$styles[$subdir]['dir'] = $subdir;
        }
    }
	
	return $styles;
}

/**
 * Takes an options array (or an empty array instead) and overwrites it with the 
 * data from the style ini file
 *
 * @param string $style
 * @param array $options
 * @return array
 */
function ppy_override_by_style_options($style, $options = array())
{
	$settingsFile = dirname(__FILE__) . "/styles/$style/settings.ini";
	if(!file_exists($settingsFile)) return $options;
	$ini = parse_ini_file($settingsFile, true);
	$rewritableOptions = array('caption', 'opacity', 'duration', 'z-index', 'easing', 'debug');
	
	foreach($ini['settings'] as $option => $value)
	  if(in_array($option, $rewritableOptions)) 
	    $options[$option] = $value;
	    
	if(isset($options['navigation']) && $options['navigation']=='default')
	  $options['navigation'] = isset( $ini['settings']['navigation'] ) ? $ini['settings']['navigation'] : 'hover';
	
	return $options;
}

/**
 * Retrieves the currently selected style
 * @return string
 */
function ppy_get_current_style()
{
  $opt = get_option('ppy_options', ppy_default_options());
  return $opt['style'];
}



?>