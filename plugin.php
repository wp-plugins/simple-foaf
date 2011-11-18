<?php
/*
Plugin Name: SimpleFOAF
Plugin URI: http://notizblog.org/
Description: Personal profile page with friends (XFN) and interests, FOAF RDF/XML profile, and extended RSS (1.0) output.
Version: 0.1
Author: Matthias Pfefferle
Author URI: http://notizblog.org/
*/

/**
 * based on the great work of http://www.wasab.dk/morten/blog/archives/2004/07/05/wordpress-plugin-foaf-output?version=1.17
 */

add_action('wp_head', array('SimpleFoaf', 'add_header'));
add_filter('host_meta', array('SimpleFoaf', 'add_host_meta_info'));
add_filter('webfinger', array('SimpleFoaf', 'add_webfinger_info'), 10, 2);

// add 'foaf' as feed
add_action('do_feed_foaf', array('SimpleFoaf', 'do_feed_foaf'));
add_action('init', array('SimpleFoaf', 'init'));

class SimpleFoaf {
  /**
   * init function
   */
  function init() {
    add_feed('foaf', array('SimpleFoaf', 'do_feed_foaf'));
  }
  
  /**
   * add link headers
   */
  function add_header() {
    if (is_home()) {
?>
  <link rel="meta" type="application/rdf+xml" title="FOAF" href="<?php echo get_feed_link("foaf"); ?>" />
<?php
      foreach(get_users() as $user) {
?>
  <link rel="meta" type="application/rdf+xml" title="FOAF" href="<?php echo get_author_feed_link( $user->ID, 'foaf' ); ?>" />
<?php 
      }
    } elseif(is_author()) {
      if (get_query_var('author_name')) :
        $authordata = get_user_by('slug', get_query_var('author_name'));
      else :
        $authordata = get_userdata(get_query_var('author'));
      endif;
?>      
      <link rel="meta" type="application/rdf+xml" title="FOAF" href="<?php echo get_author_feed_link( $authordata->ID, 'foaf' ); ?>" />
<?php
    }
  }
  
  /**
   * handles new feed type
   */
  function do_feed_foaf() {
    header("Content-type: application/rdf+xml");

    if (is_author()) {
      SimpleFoaf::print_author_foaf();
    } else {
      SimpleFoaf::print_foaf();
    }
  }
  
  /**
   * prints foaf feed
   */
  function print_foaf() {
    echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<rdf:RDF
	xmlns:foaf="http://xmlns.com/foaf/0.1/"
	xmlns:rss="http://purl.org/rss/1.0/"
	xmlns:admin="http://webns.net/mvcb/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
	xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
<foaf:Document rdf:about="">
	<dc:title>Author list for <?php echo htmlspecialchars(get_bloginfo('name')); ?></dc:title>
	<foaf:primaryTopic rdf:nodeID="authors"/>
	<admin:generatorAgent rdf:resource="http://wordpress.org/extend/plugins/simple-foaf/"/>
</foaf:Document>
<foaf:Group rdf:nodeID="authors">
	<foaf:name>Authors at <?php echo htmlspecialchars(get_bloginfo('name')); ?></foaf:name>
	<foaf:made>

		<foaf:Document rdf:about="<?php echo htmlspecialchars(get_bloginfo('siteurl')); ?>">
			<dc:title><?php echo htmlspecialchars(get_bloginfo('name')); ?></dc:title>
			<rdfs:seeAlso>
				<rss:channel rdf:about="<?php echo htmlspecialchars(get_bloginfo('rss2_url')); ?>"/>
			</rdfs:seeAlso>
		</foaf:Document>
	</foaf:made>
<?php
foreach(get_users() as $user) {
  $authordata = get_userdata($user->ID);
?>
	<foaf:member>
		<foaf:Person>
    	<foaf:firstName><?php echo htmlspecialchars($authordata->user_firstname); ?></foaf:firstName>
      <foaf:surname><?php echo htmlspecialchars($authordata->user_lastname); ?></foaf:surname>
      <foaf:nick><?php echo htmlspecialchars($authordata->nickname); ?></foaf:nick>
      <foaf:email><?php echo $authordata->user_email ?></foaf:email>
			<foaf:weblog rdf:resource="<?php echo htmlspecialchars(get_author_posts_url($authordata->ID, $authordata->user_nicename)); ?>"/>
			<rdfs:seeAlso>
				<foaf:PersonalProfileDocument rdf:about="<?php echo htmlspecialchars(get_author_feed_link( $authordata->ID, 'foaf' )); ?>"/>
			</rdfs:seeAlso>
		</foaf:Person>
	</foaf:member>
<?php
}
?>
</foaf:Group>
</rdf:RDF>
<?php
  }
  
  /**
   * prints author foaf feed
   */
  function print_author_foaf() {
    if (get_query_var('author_name')) :
      $authordata = get_user_by('slug', get_query_var('author_name'));
    else :
      $authordata = get_userdata(get_query_var('author'));
    endif;

    echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
?>
<rdf:RDF
  xmlns:foaf="http://xmlns.com/foaf/0.1/"
  xmlns:rss="http://purl.org/rss/1.0/"
  xmlns:rel="http://purl.org/vocab/relationship/"
  xmlns:xfn="http://gmpg.org/xfn/1#"
  xmlns:bio="http://purl.org/vocab/bio/0.1/"
  xmlns:trust="http://trust.mindswap.org/ont/trust.owl#"
  xmlns:admin="http://webns.net/mvcb/"
  xmlns:skos="http://www.w3.org/2004/02/skos/core#"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  <?php do_action('foaf_ns'); ?>
>
<foaf:PersonalProfileDocument rdf:about="">
  <dc:title>FOAF Document for <?php echo htmlspecialchars($authordata->display_name); ?></dc:title>
	<foaf:primaryTopic rdf:nodeID="a<?php echo $authordata->ID; ?>"/>
	<foaf:maker rdf:nodeID="a<?php echo $authordata->ID; ?>"/>
	<admin:generatorAgent rdf:resource="http://wordpress.org/extend/plugins/simple-foaf/"/>
</foaf:PersonalProfileDocument>
<foaf:Person rdf:nodeID="a<?php echo $authordata->ID; ?>">
	<foaf:weblog>
		<foaf:Document rdf:about="<?php echo htmlspecialchars(get_author_posts_url($authordata->ID, $authordata->user_nicename)); ?>">
			<dc:title><?php echo htmlspecialchars(get_bloginfo('name')); ?></dc:title>
			<rdfs:seeAlso>
				<rss:channel rdf:about="<?php echo htmlspecialchars(get_author_feed_link( $authordata->ID, 'rss2' )); ?>"/>
			</rdfs:seeAlso>
		</foaf:Document>
	</foaf:weblog>
	<foaf:firstName><?php echo htmlspecialchars($authordata->user_firstname); ?></foaf:firstName>
  <foaf:surname><?php echo htmlspecialchars($authordata->user_lastname); ?></foaf:surname>
  <foaf:nick><?php echo htmlspecialchars($authordata->nickname); ?></foaf:nick>
  <foaf:email><?php echo $authordata->user_email ?></foaf:email>
  <foaf:aimChatID><?php echo htmlspecialchars($authordata->user_aim); ?></foaf:aimChatID>
  <foaf:yahooChatID><?php echo htmlspecialchars($authordata->user_yim); ?></foaf:yahooChatID>
  <foaf:msnChatID><?php echo htmlspecialchars($authordata->user_msn); ?></foaf:msnChatID>
  <foaf:icqChatID><?php echo htmlspecialchars($authordata->user_icq); ?></foaf:icqChatID>
  <foaf:homepage rdf:resource="<?php echo htmlspecialchars($authordata->user_url); ?>"/>
  <bio:olb><?php echo htmlspecialchars(strip_tags($authordata->user_description)); ?></bio:olb>
  <?php
  foreach (get_bookmarks() as $link) {
    if ($link->link_rel && $authordata->ID == $link->link_owner) {
  ?>
  	<foaf:knows>
  		<foaf:Person rdf:nodeID="n<?php echo $link->link_id; ?>">
  			<foaf:name><?php echo htmlspecialchars($link->link_description); ?></foaf:name>
  			<foaf:weblog>
  				<foaf:Document rdf:about="<?php echo htmlspecialchars($link->link_url); ?>">
  					<dc:title><?php echo htmlspecialchars($link->link_name); ?></dc:title>
  				  <?php if (!empty($link->link_rss)) { ?>
  					<rdfs:seeAlso>
  						<rss:channel rdf:about="<?php echo htmlspecialchars($link->link_rss); ?>"/>
  					</rdfs:seeAlso>
  					<?php } ?>
  				</foaf:Document>
  			</foaf:weblog>
  		</foaf:Person>
  	</foaf:knows>
  <?php
      if (!empty($link->link_rating)) {
  ?>
    <trust:trust<?php echo $link->link_rating; ?> rdf:nodeID="n<?php echo $link->link_id; ?>"/>
  <?php
      }
    }
  }
  ?>
</foaf:Person>
</rdf:RDF>
<?php
  }
  
  /**
   * add the host meta information
   */
  function add_host_meta_info($array) {
	  $array["links"][] = array("rel" => "describedby", "href" => get_feed_link("foaf"), "type" => "application/rdf+xml");

	  return $array;
  }
  
  /**
   * add the host meta information
   */
  function add_webfinger_info($array, $user) {
	  $array["links"][] = array("rel" => "describedby", "href" => get_author_feed_link( $user->ID, 'foaf' ), "type" => "application/rdf+xml");

	  return $array;
  }
}