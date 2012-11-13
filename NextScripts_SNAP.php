<?php
/*
Plugin Name: NextScripts: Social Networks Auto-Poster
Plugin URI: http://www.nextscripts.com/social-networks-auto-poster-for-wordpress
Description: This plugin automatically publishes posts from your blog to multiple accounts on Facebook, Twitter, and Google+ profiles and/or pages.
Author: Next Scripts
Version: 2.4.0
Author URI: http://www.nextscripts.com
Copyright 2012  Next Scripts, Inc
*/
define( 'NextScripts_SNAP_Version' , '2.4.0' ); require_once "nxs_functions.php";   
//## Include All Available Networks
global $nxs_snapAvNts, $nxs_snapThisPageUrl, $nxs_plurl, $nxs_isWPMU, $nxs_tpWMPU;
$nxs_snapAvNts = array();  foreach (glob(plugin_dir_path( __FILE__ ).'inc-cl/*.php') as $filename){  require_once $filename; }

$nxs_snapThisPageUrl = admin_url().'options-general.php?page=NextScripts_SNAP.php'; 
$nxs_plurl = plugin_dir_url(__FILE__);
$nxs_isWPMU = (defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE==true && defined('MULTISITE') && MULTISITE==true); 

//## Define SNAutoPoster class
if (!class_exists("NS_SNAutoPoster")) {
    class NS_SNAutoPoster {//## General Functions         
        var $dbOptionsName = "NS_SNAutoPoster";       
        var $nxs_options = ""; var $nxs_ntoptions = "";
        
        function __construct() { $this->nxs_options = $this->getAPOptions();} 
        //## Constructor
        function NS_SNAutoPoster() { }
        //## Initialization function
        function init() { $this->getAPOptions(); }
        //## Administrative Functions
        //## Options loader function
        function getAPOptions() { global $nxs_isWPMU, $blog_id; $dbMUOptions = array();
            //## Some Default Values            
            //$options = array('nsOpenGraph'=>1);            
            $dbOptions = get_option($this->dbOptionsName);
            $this->nxs_ntoptions = get_site_option($this->dbOptionsName); 
            if ($nxs_isWPMU && $blog_id>1) { switch_to_blog(1); $dbMUOptions = get_option($this->dbOptionsName);  restore_current_blog(); 
              $dbOptions['lk'] = $dbMUOptions['lk']; $dbOptions['ukver'] = $dbMUOptions['ukver']; $dbOptions['uklch'] = $dbMUOptions['uklch']; $dbOptions['uk'] = $dbMUOptions['uk'];
            }              
            if (!empty($dbOptions))  foreach ($dbOptions as $key => $option) if (trim($key)!='') $options[$key] = $option;  $updTime = "+2 hours"; // $updTime = "+15 seconds"; // $updTime = "+5 minutes"; $updTime = "+1 day"";                       
                        
            if (defined('NXSAPIVER') && $options['ukver']!=NXSAPIVER){$options['ukver']=NXSAPIVER;  update_option($this->dbOptionsName, $options);}            
            $options['isMA'] = function_exists('nxs_doSMAS1') && isset($options['lk']) && isset($options['uk']) && $options['uk']!='';   
            $options['isMU'] = function_exists('showSNAP_WPMU_OptionsPageExt') && isset($options['lk']) && isset($options['uk']) && $options['uk']!='';   
            $options['isMUx'] = function_exists('showSNAP_WPMU_OptionsPageExtX') && isset($options['lk']) && isset($options['uk']) && $options['uk']!=''; //  prr($options);
            
            if (!isset($options['isPro']) || $options['isPro']!='1'){ //## Upgrade from non-pro version            
              $optPro = array();foreach ($options as $indx => $opt){                 
                 if (substr($indx, 0, 2)=='fb') $optPro['fb'][0][$indx] = $opt;
                 elseif (substr($indx, 0, 2)=='gp') $optPro['gp'][0][$indx] = $opt;
                 elseif (substr($indx, 0, 2)=='tw') $optPro['tw'][0][$indx] = $opt;
                 elseif (substr($indx, 0, 2)=='tr') $optPro['tr'][0][$indx] = $opt;
                 elseif (substr($indx, 0, 2)=='bg') $optPro['bg'][0][$indx] = $opt;
                 elseif (substr($indx, 0, 2)=='li') $optPro['li'][0][$indx] = $opt;
                 elseif (substr($indx, 0, 2)=='pn') $optPro['pn'][0][$indx] = $opt;
                 elseif ($indx=='doFB') $optPro['fb'][0][$indx] = $opt;
                 elseif ($indx=='doGP') $optPro['gp'][0][$indx] = $opt;
                 elseif ($indx=='doTW') $optPro['tw'][0][$indx] = $opt;
                 elseif ($indx=='doTR') $optPro['tr'][0][$indx] = $opt;
                 elseif ($indx=='doBG') $optPro['bg'][0][$indx] = $opt;
                 elseif ($indx=='doLI') $optPro['li'][0][$indx] = $opt;
                 elseif ($indx=='doPN') $optPro['pn'][0][$indx] = $opt;
                 elseif (trim($indx)!='') $optPro[$indx] = $opt; 
                 if ($options['twAccTokenSec']!='') $optPro['tw'][0]['twOK'] = '1';
                 if ($options['bgBlogID']!='') $optPro['bg'][0]['bgOK'] = '1';
                 $optPro['isPro'] = '1'; 
              } 
              //## Update the options for the panel
              $options = $optPro; update_option($this->dbOptionsName, $options);
            } 
            if(!$options['isMA']) $options = nxs_snapCleanup($options);
            return $options;
        }
  
        function showSNAP_WPMU_OptionsPage(){ global $nxs_snapAvNts, $nxs_snapThisPageUrl, $nxsOne, $wpdb, $nxs_isWPMU; $nxsOne = ''; $options = $this->nxs_options; 
          $this->NS_SNAP_ShowPageTop();  
          if ($nxs_isWPMU && function_exists('showSNAP_WPMU_OptionsPageExt')) { showSNAP_WPMU_OptionsPageExt($this); } elseif ($nxs_isWPMU && function_exists('showSNAP_WPMU_OptionsPageExtX')) { ?>          
              <br/><br/><b style="font-size:16px; line-height:24px; color:red;">You are running SNAP <?php echo $options['isMA']?'Single Site Pro':'Free'; ?> <br/> </b>               
              This version does not fully support Wordpress Multisite (ex Wordpress MU) Advanced Features. SNAP is available for all sites/blogs in your networks and each individual blog admin can setup and manage it.
              <br/>Please upgrade to <a href="http://www.nextscripts.com/social-networks-auto-poster-pro-for-wpmu/" target="_blank"> SNAP For Wordpress Multisite</a> if you need advanced Super Admin management of SNAP for sites/blogs in your networks. Please see <a href="http://www.nextscripts.com/social-networks-auto-poster-pro-for-wpmu/" target="_blank">here</a> for more info              
              <br/><br/>Please <a href="http://www.nextscripts.com/contact-us/" target="_blank"> contact us</a> if you got the SNAP PRO before Oct 1st, 2012. You may be eligible for upgrade discount.              
               <br/><br/>               
               <?php return;
          } elseif ( !$options['isMA']) { 
               ?> <br/><br/><b style="font-size:16px; line-height:24px; color:red;">You are running SNAP <?php echo $options['isMA']?'Single Site Pro':'Free'; ?> <br/> This version does not support Wordpress Multisite (ex Wordpress MU). <br/>Please upgrade to <a href="http://www.nextscripts.com/social-networks-auto-poster-pro-for-wpmu/" target="_blank"> SNAP Pro for Wordpress Multisite</a></b> 
               <br/><br/><hr/>
               <h3>FAQ:</h3> <b>Question:</b> I am not running Wordpress Multisite! Why I am seeing this?<br/><b>Answer:</b>               
               Your Wordpress is configured to run as a Wordpress Multisite. Please open your wp-config.php and change: <br/><br/>
define('WP_ALLOW_MULTISITE', true);<br/>to<br/>define('WP_ALLOW_MULTISITE', false);<br/><br/>and<br/><br/>define('MULTISITE', true);<br/>to<br/>define('MULTISITE', false);<br/><br/>
<b>Question:</b> I am running Wordpress Multisite, but I need SNAP on one blog only? Can I use it?<br/><b>Answer:</b>We are sorry, but it is not possible to run "SNAP Free" on Wordpress Multisite. You need to either upgrade plugin to "SNAP Pro" to run it on one blog or to "SNAP Pro for WPNU" to run it on all blogs or disable Wordpress Multisite.          
<br/><br/><hr/>     
               <?php return; 
          } else {
               ?> <br/><b style="font-size:16px; line-height:24px; color:red;">You are running SNAP <?php echo $options['isMA']?'Single Site Pro':'Free'; ?> <br/> This version does not fully support Wordpress Multisite (ex Wordpress MU).</b> <br/>
               
               <br/><span style="font-size: 16px;"> You can use SNAP for your main blog only. <a href="<?php echo admin_url(); ?>options-general.php?page=NextScripts_SNAP.php">Click here to setup it.</a></span><br/><br/>
               
               <span style="font-size: 12px; font-weight: bold;">Please upgrade to <a href="http://www.nextscripts.com/social-networks-auto-poster-pro-for-wpmu/" target="_blank"> SNAP Pro for Wordpress Multisite</a> to get all features:  </span>              
               <br/>
- All Blogs/Sites autopost to networks configured by Super Admin    <br/>
- Each Blog/Site Admin can configure and auto-post to it's own networks  <br/>  
- Super Admin can enable/disable auto-posting for each site and the whole network<br/>
- Super Admin can also manage/setup/disable/override SNAP settings for each Blog/Site.<br/>
               
               <br/>
               <?php return; 
          }
        }
        function showSNAutoPosterOptionsPage() { global $nxs_snapAvNts, $nxs_snapThisPageUrl, $nxsOne, $nxs_plurl, $nxs_isWPMU, $nxs_tpWMPU; $nxsOne = ''; $options = $this->nxs_options;
          //if($acid==1) $options = $this->nxs_options;  else { switch_to_blog($acid); $options = $this->getAPOptions(); }
          
          if (isset($_POST['update_NS_SNAutoPoster_settings'])) { if (get_magic_quotes_gpc()) {array_walk_recursive($_POST, 'nsx_stripSlashes');}  array_walk_recursive($_POST, 'nsx_fixSlashes'); 
            foreach ($nxs_snapAvNts as $avNt) if (isset($_POST[$avNt['lcode']])) { $clName = 'nxs_snapClass'.$avNt['code']; if (!isset($options[$avNt['lcode']])) $options[$avNt['lcode']] = array(); 
              $ntClInst = new $clName(); $ntOpt = $ntClInst->setNTSettings($_POST[$avNt['lcode']], $options[$avNt['lcode']]); $options[$avNt['lcode']] = $ntOpt;
            }           
            if (isset($_POST['apCats']))      $options['apCats'] = $_POST['apCats'];                
            if (isset($_POST['nxsHTDP']))     $options['nxsHTDP'] = $_POST['nxsHTDP'];                
            if (isset($_POST['ogImgDef']))    $options['ogImgDef'] = $_POST['ogImgDef'];
            if (isset($_POST['nxsURLShrtnr']))$options['nxsURLShrtnr'] = $_POST['nxsURLShrtnr']; 
            if (isset($_POST['bitlyUname']))  $options['bitlyUname'] = $_POST['bitlyUname']; 
            if (isset($_POST['bitlyAPIKey'])) $options['bitlyAPIKey'] = $_POST['bitlyAPIKey']; 
            if (trim($_POST['bitlyAPIKey'])=='' || trim($_POST['bitlyAPIKey'])=='') $options['nxsURLShrtnr'] = 'G';
            
            if (isset($_POST['nsOpenGraph']))   $options['nsOpenGraph'] = $_POST['nsOpenGraph']; else $options['nsOpenGraph'] = 0;                
            if (isset($_POST['useUnProc']))   $options['useUnProc'] = $_POST['useUnProc']; else $options['useUnProc'] = 0;                            
            if (isset($_POST['nxsCPTSeld']))      $options['nxsCPTSeld'] = serialize($_POST['nxsCPTSeld']);                      
            if (isset($_POST['post_category']))  { $pk = $_POST['post_category']; $cIds = get_all_category_ids(); $options['exclCats'] = serialize(array_diff($cIds, $pk)); } // prr($options['exclCats']);
            if (!isset($_POST['whoCanSeeSNAPBox'])) $_POST['whoCanSeeSNAPBox'] = array(); $_POST['whoCanSeeSNAPBox'][] = 'administrator';            
            if (isset($_POST['whoCanSeeSNAPBox'])) $options['whoCanSeeSNAPBox'] = $_POST['whoCanSeeSNAPBox'];
            if ($nxs_isWPMU && (!isset($options['suaMode'])||$options['suaMode'] = '')) $options['suaMode'] = $nxs_tpWMPU; 
            $editable_roles = get_editable_roles(); foreach ( $editable_roles as $roleX => $details ) {$role = get_role($roleX); $role->remove_cap('see_snap_box');  }
            foreach ($options['whoCanSeeSNAPBox'] as $uRole) { $role = get_role($uRole); $role->add_cap('see_snap_box'); }            
            
            update_option($this->dbOptionsName, $options); // prr($options);
            ?><div class="updated"><p><strong><?php _e("Settings Updated.", "NS_SNAutoPoster");?></strong></p></div><?php           
          }   $isNoNts = true; foreach ($nxs_snapAvNts as $avNt) if (isset($options[$avNt['lcode']]) && is_array($options[$avNt['lcode']]) && count($options[$avNt['lcode']])>0) {$isNoNts = false; break;}      
          
          $category_ids = get_all_category_ids(); $pk = maybe_unserialize($options['exclCats']);
if ( is_array($category_ids) && is_array($pk) && count($category_ids) == count($pk)) { ?>
  <div class="error" id="message"><p><strong>All your categories are excluded from auto-posting.</strong> Nothing will be auto-posted. Please Click "Other Settings" and select some categories.</div>
<?php }
          
          if(!$nxs_isWPMU) $this->NS_SNAP_ShowPageTop();  ?>
            Please see the <a target="_blank" href="http://www.nextscripts.com/installation-of-social-networks-auto-poster-for-wordpress">detailed installation/configuration instructions</a> (will open in a new tab)<br/><br/>
                       
           
<ul class="nsx_tabs">
    <li><a href="#nsx_tab1">Your Social Networks Accounts</a></li>
    <li><a href="#nsx_tab2">Other Settings</a></li>
    <li><a href="#nsx_tab3">Log/History</a></li>
    <li><a href="#nsx_tab4">Help/Support</a></li>
</ul>
<form method="post" id="nsStForm" action="<?php echo $nxs_snapThisPageUrl?>">
<div class="nsx_tab_container">
    <div id="nsx_tab1" class="nsx_tab_content"><a href="#" class="NXSButton" id="nxs_snapAddNew">Add new account</a> <div class="nxsInfoMsg"><img style="position: relative; top: 8px;" alt="Arrow" src="http://cdn.gtln.us/img/icons/arrow_l_green_c1.png"/> You can add Facebook, Twitter, Google+, Pinterest, LinkedIn, Tumblr, Blogger/Blogspot, Delicious accounts</div><br/><br/>
           <div id="nxs_spPopup"><span class="nxspButton bClose"><span>X</span></span>Add New Network: <select onchange="doShowFillBlockX(this.value);" id="nxs_ntType"><option value =""></option>
           <?php foreach ($nxs_snapAvNts as $avNt) { if (!isset($options[$avNt['lcode']]) || count($options[$avNt['lcode']])==0) $mt=0; else $mt = 1+max(array_keys($options[$avNt['lcode']]));
              echo '<option value ="'.$avNt['code'].$mt.'">'.$avNt['name'].'</option>'; 
           } ?>
           </select>           
           <div id="nsx_addNT">
             <?php foreach ($nxs_snapAvNts as $avNt) { $clName = 'nxs_snapClass'.$avNt['code']; $ntClInst = new $clName(); 
             if (!isset($options[$avNt['lcode']]) || count($options[$avNt['lcode']])==0) { $ntClInst->showNewNTSettings(0); } else { 
                 $mt = 1+max(array_keys($options[$avNt['lcode']])); if (function_exists('nxs_doSMAS1')) nxs_doSMAS1($ntClInst, $mt); else nxs_doSMAS($avNt['name'], $avNt['code'].$mt);             
             }} ?>           
           </div>
           
           </div>
            <?php wp_nonce_field( 'nsDN', 'nsDN_wpnonce' ); 
           foreach ($nxs_snapAvNts as $avNt) { $clName = 'nxs_snapClass'.$avNt['code']; $ntClInst = new $clName();
              if ( isset($options[$avNt['lcode']]) && count($options[$avNt['lcode']])>0) { $ntClInst->showGenNTSettings($options[$avNt['lcode']]); } // else $ntClInst->showNewNTSettings(0);
           }
           if ($isNoNts) { ?><br/><br/><br/>You don't have any configured social networks yet. Please click "Add new account" button.<?php } else {?>   
           <div class="submit"><input type="submit" class="button-primary" name="update_NS_SNAutoPoster_settings" value="<?php _e('Update Settings', 'NS_SNAutoPoster') ?>" /></div>
           <?php } ?>   
    </div> <!-- END TAB -->
    <div id="nsx_tab2" class="nsx_tab_content">
       <!-- ##################### OTHER #####################-->
            
            <h3 style="font-size: 17px;">Other Settings</h3> <?php wp_nonce_field( 'nxsSsPageWPN', 'nxsSsPageWPN_wpnonce' ); ?>  
                      
            <h3 style="font-size: 14px; margin-bottom: 2px;">How to make auto-posts? <span style="font-size: 12px;" > &lt;-- (<a id="showShAttIS" onmouseover="showPopShAtt('IS', event);" onmouseout="hidePopShAtt('IS');"  onclick="return false;" class="underdash" href="#">What's the difference?</a>)</span></h3>               
            <div class="popShAtt" id="popShAttIS">
        <h3>The difference between "Immediately" and "Scheduled"</h3>
<b>"Immediately"</b> - Once you click "Publish" button plugin starts pushing your update to configured social networks. At this time you need to wait and look at the turning circle. Some APIs are pretty slow, so you have to wait and wait and wait until all updates are posted and page released back to you.<br/><br/>
<b>"Scheduled"</b> - Releases the page immediately back to you, so you can proceed with something else and it schedules all auto-posting jobs to your WP-Cron. This is much faster and much more efficient, but it could not work if your WP-Cron is disabled or broken.
      </div>
            <input type="radio" name="nxsHTDP" value="S" <?php if (!isset($options['nxsHTDP']) || $options['nxsHTDP']=='S') echo 'checked="checked"'; ?> /> Schedule (Recomennded) - <i>Faster Perfomance</i><br/>
            <input type="radio" name="nxsHTDP" value="I" <?php if (isset($options['nxsHTDP']) && $options['nxsHTDP']=='I') echo 'checked="checked"'; ?> /> Publish Immediately  - <i>Use if WP Cron is disabled or broken on your website</i><br/>
            
            <h3 style="font-size: 14px; margin-bottom: 2px;">Who can see auto-posting options on the "New Post" pages?</h3>                
            <?php $editable_roles = get_editable_roles(); if (!is_array($options['whoCanSeeSNAPBox'])) $options['whoCanSeeSNAPBox'] = array(); 

    foreach ( $editable_roles as $role => $details ) { $name = translate_user_role($details['name'] ); echo '<input type="checkbox" '; 
        if (in_array($role, $options['whoCanSeeSNAPBox']) || $role=='administrator') echo ' checked="checked" '; if ($role=='administrator' || $role=='subscriber') echo '  disabled="disabled" ';
        echo 'name="whoCanSeeSNAPBox[]" value="'.esc_attr($role).'"> '.$name; 
        if ($role=='administrator') echo ' - Somebody who has access to all the administration features';
        if ($role=='editor') echo ' - Somebody who can publish and manage posts and pages as well as manage other users\' posts, etc. ';
        if ($role=='author') echo ' - Somebody who can publish and manage their own posts ';
        if ($role=='contributor') echo ' - Somebody who can write and manage their posts but not publish them';
        if ($role=='subscriber') echo ' - Somebody who can only manage their profile';        
        echo '<br/>';    
    } ?>
            
            <h3 style="font-size: 14px; margin-bottom: 2px;">Include/Exclude Custom Post Types</h3>  <?php $nxsOne = base64_encode("v=".$nxsOne); ?>
            <p style="font-size: 11px; margin: 0px;">Select Custom Post Types that you would to be published on your social networks</p>           
            <?php 
              $args=array('public'=>true, '_builtin'=>false);  $output = 'names';  $operator = 'and';  $post_types = array(); if (function_exists('get_post_types')) $post_types=get_post_types($args, $output, $operator); 
              if ($options['nxsCPTSeld']!='') $nxsCPTSeld = unserialize($options['nxsCPTSeld']); else $nxsCPTSeld = array_keys($post_types);
            ?>
            <select multiple="multiple" name="nxsCPTSeld[]" id="nxsCPTSeld" class="nxsMultiSelect" size="<?php echo count($post_types)+2; ?>">
            <option <?php if (count($nxsCPTSeld)==0) echo 'selected="selected"'; ?> value="-----">----------------------- None -----------------------</option>
            <?php             
              foreach ($post_types as $cptID=>$cptName){ 
                ?><option <?php if (in_array($cptID,$nxsCPTSeld)) echo 'selected="selected"'; ?> value="<?php echo $cptID; ?>"><?php echo $cptName; ?></option><?php
              }
            ?>
            </select>            
            
           <div style="width:500px;"><strong style="font-size: 14px;"><br/>Categories to Include/Exclude:</strong> 
              <p style="font-size: 11px; margin: 0px;">Each blogpost will be autoposted to all categories selected below. All categories are selected by default. 
              <b>Uncheck</b> categories that you would like <b>NOT</b> to auto-post by default. Assigning the uncheked category to the new blogpost will turn off auto-posting to all configured networks.</p>
            
<script type="text/javascript">
function nxs_chAllCats(ch){
    jQuery("form input:checkbox[name='post_category[]']").attr('checked', ch==1);
}
</script>            
            
<a href="#" onclick="nxs_chAllCats(1); return false;">Check all</a> &nbsp;|&nbsp; <a href="#" onclick="nxs_chAllCats(0); return false;">UnCheck all</a>

 <div id="taxonomy-category" class="categorydiv">
        <div id="category-all" class="tabs-panel"><input type='hidden' name='post_category[]' value='0' />
            <ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
                <?php $category_ids = get_all_category_ids(); $pk = maybe_unserialize($options['exclCats']); if (is_array($pk) && count($pk)>0 ) $selCats = array_diff($category_ids, $pk); else $selCats = $category_ids;            
                  $args = array( 'descendants_and_self' => 0, 'selected_cats' => $selCats, 'taxonomy' => 'category', 'checked_ontop' => false);    
                  if (function_exists('wp_terms_checklist')) wp_terms_checklist(0, $args ); 
                ?>
            </ul>
        </div>  
    </div>
    
    </div>
    
     <!-- ##################### URL Shorthener #####################-->
            <br/>
             <h3 style="font-size: 14px; margin-bottom: 2px;">URL Shorthener</h3>                      
            &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="nxsURLShrtnr" value="G" <?php if (!isset($options['nxsURLShrtnr']) || $options['nxsURLShrtnr']=='' || $options['nxsURLShrtnr']=='G') echo 'checked="checked"'; ?> /> gd.is (Default) - fast, simple, free, no configuration nessesary.<br/>
            <?php if (function_exists('wpme_get_shortlink')) { ?> &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="nxsURLShrtnr" value="W" <?php if (isset($options['nxsURLShrtnr']) && $options['nxsURLShrtnr']=='W')  echo 'checked="checked"'; ?> /> wp.me - WP.com Jetpack Shortener<br/> <?php } ?>
            &nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="nxsURLShrtnr" value="B" <?php if (isset($options['nxsURLShrtnr']) && $options['nxsURLShrtnr']=='B') echo 'checked="checked"'; ?> /> bit.ly  - <i>Enter bit.ly username and <a target="_blank" href="http://bitly.com/a/your_api_key">API Key</a> below</i><br/>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;bit.ly Username: <input name="bitlyUname" style="width: 20%;" value="<?php if (isset($options['bitlyUname'])) _e(apply_filters('format_to_edit',$options['bitlyUname']), 'NS_SNAutoPoster') ?>" /><br/>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;bit.ly&nbsp;&nbsp;API Key:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input name="bitlyAPIKey" style="width: 20%;" value="<?php if (isset($options['bitlyAPIKey'])) _e(apply_filters('format_to_edit',$options['bitlyAPIKey']), 'NS_SNAutoPoster') ?>" /><br/>
            
             <!-- ##################### Open Graph #####################-->
            
            <h3 style="font-size: 14px; margin-bottom: 2px;">"Open Graph" Tags</h3> 
             <span style="font-size: 11px; margin-left: 1px;">"Open Graph" tags are used for generating title, description and preview image for your Facebook and Google+ posts. This is quite simple implementation of "Open Graph" Tags. This option will only add tags needed for "Auto Posting". If you need something more serious uncheck this and use other specialized plugins. </span>
            <p style="margin: 0px;margin-left: 5px;"><input value="1" id="nsOpenGraph" name="nsOpenGraph"  type="checkbox" <?php if ((int)$options['nsOpenGraph'] == 1) echo "checked"; ?> /> 
              <strong>Add Open Graph Tags</strong>                                 
                         
            </p>
            
            <div style="width:100%; margin-left: 15px;">            
             
            <div style="margin: 7px; margin-left: 10px;"><input value="1" id="useUnProc" name="useUnProc"  type="checkbox" <?php if ((int)$options['useUnProc'] == 1) echo "checked"; ?> /> 
             <strong>Use advanced image finder</strong> - Check this if your images could be found only in the fully processed posts. <br/>This feature could interfere with some plugins using post processing functions incorrectly. Your site could become messed up, have troubles displaying content or start giving you "ob_start() [ref.outcontrol]: Cannot use output buffering in output buffering display handlers" errors.                        
            </div>
            
            <strong style="font-size: 11px; margin: 10px; margin-left: 10px;">Default Image URL for og:image tag:</strong> <img src="http://www.nextscripts.com/gif.php<?php echo "?g=".$nxsOne; ?> "/>
            <input name="ogImgDef" style="width: 30%;" value="<?php if (isset($options['ogImgDef'])) _e(apply_filters('format_to_edit',$options['ogImgDef']), 'NS_SNAutoPoster') ?>" />
            
            </div>
            
             
            
           
        <div class="submit"><input type="submit" class="button-primary" name="update_NS_SNAutoPoster_settings" value="<?php _e('Update Settings', 'NS_SNAutoPoster') ?>" /></div>
           
    </div>
    <div id="nsx_tab3" class="nsx_tab_content"> 
    <div style="width:760px;">
    <a href="#" style="float: right" onclick="nxs_rfLog();return false;" class="NXSButton" id="nxs_clearLog">Refresh</a>
    
    Showing last 150 records <a href="#" onclick="nxs_clLog();return false;" class="NXSButton" id="nxs_clearLog">Clear Log</a><br/><br/>    
      <div style="overflow: auto; border: 1px solid #999; width: 750px; height: 600px; font-size: 11px;" class="logDiv" id="nxslogDiv">
        <?php $logInfo = maybe_unserialize(get_option('NS_SNAutoPosterLog')); if (is_array($logInfo)) 
          foreach (array_reverse($logInfo) as $logline) echo '<snap style="color:#008000">['.$logline['date'].']</snap> - ['.$logline['nt'].'] -  <snap style="color:#'.($logline['type']=='E'?'FF0000':'585858').'">'.$logline['msg'].'</snap> '.$logline['extInfo'].'<br/>'; ?>
      </div>  
      
      </div>
        
    </div>
    
    <div id="nsx_tab4" class="nsx_tab_content"> 
     
     <div style="max-width:1000px;"> 
     
<h3> Setup/Installation/Configuration Instructions   </h3>
     <table style="max-width:1000px"><tr><td valign="top" width="250">
     
     
     
   <div style="margin:0 25px 0 0; line-height: 24px;">   

<a style="background-image:url(http://www.nextscripts.com/wp-content/themes/NXS/images/icons/16/led-icons/application_form.png) !important;" class="nxs_icon16" target="_parent" href="http://www.nextscripts.com/installation-of-social-networks-auto-poster-for-wordpress/">Plugin Setup/Installation</a>
<br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a style="background-image:url(http://www.nextscripts.com/wp-content/themes/NXS/images/icons/16/led-icons/facebook.png) !important;" class="nxs_icon16" target="_parent" href="http://www.nextscripts.com/setup-installation-facebook-social-networks-auto-poster-wordpress/">  Facebook </a>
<br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a style="background-image:url(http://www.nextscripts.com/wp-content/themes/NXS/images/icons/16/led-icons/twitter.png) !important;" class="nxs_icon16" target="_parent" href="http://www.nextscripts.com/setup-installation-twitter-social-networks-auto-poster-wordpress/">  Twitter </a>
<br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a style="background-image:url(http://www.nextscripts.com/wp-content/themes/NXS/images/icons/16/led-icons/googleplus.png) !important;" class="nxs_icon16" target="_parent" href="http://www.nextscripts.com/setup-installation-google-plus-social-networks-auto-poster-wordpress/"> Google+ </a>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;<a style="background-image:url(http://www.nextscripts.com/wp-content/themes/NXS/images/icons/16/led-icons/pinterest.png) !important;" class="nxs_icon16" target="_parent" href="http://www.nextscripts.com/setup-installation-pinterest-social-networks-auto-poster-wordpress/">  Pinterest</a>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;<a style="background-image:url(http://www.nextscripts.com/wp-content/themes/NXS/images/icons/16/led-icons/tumblr.png) !important;" class="nxs_icon16" target="_parent" href="http://www.nextscripts.com/setup-installation-tumblr-social-networks-auto-poster-wordpress/">  Tumblr </a>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;<a style="background-image:url(http://www.nextscripts.com/wp-content/themes/NXS/images/icons/16/led-icons/linkedin.png) !important;" class="nxs_icon16" target="_parent" href="http://www.nextscripts.com/setup-installation-linkedin-social-networks-auto-poster-wordpress/">  LinkedIn </a>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;<a style="background-image:url(http://www.nextscripts.com/wp-content/themes/NXS/images/icons/16/led-icons/blogger.png) !important;" class="nxs_icon16" target="_parent" href="http://www.nextscripts.com/setup-installation-blogger-social-networks-auto-poster-wordpress/">  Blogger </a>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;<a style="background-image:url(http://www.nextscripts.com/wp-content/themes/NXS/images/icons/16/led-icons/delicious.png) !important;" class="nxs_icon16" target="_parent" href="http://www.nextscripts.com/setup-installation-delicious-social-networks-auto-poster-wordpress/"> Delicious </a>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;<a style="background-image:url(http://www.nextscripts.com/wp-content/themes/NXS/images/icons/16/led-icons/blogcom.png) !important;" class="nxs_icon16" target="_parent" href="http://www.nextscripts.com/setup-installation-wp-based-social-networks-auto-poster-wordpress/"> Wordpress.com/Blog.com</a>
<br/><br/>
<a style="font-weight: normal; font-size: 16px; line-height: 24px;" target="_blank" href="http://www.nextscripts.com/faq">FAQ</a><br/>
<a style="font-weight: normal; font-size: 16px; line-height: 24px;" target="_blank" href="http://www.nextscripts.com/troubleshooting-social-networks-auto-poster">Troubleshooting FAQ</a>

</div>

</td>
<td  valign="top" style="font-size: 14px;">
<h3 style="margin-top: 0px;">Have questions/suggestions?</h3>
<a style="font-weight: normal; font-size: 18px; line-height: 24px;" target="_blank" href="http://www.nextscripts.com/contact-us">===&gt; Contact us &lt;===</a> <br/>
<h3 style="margin-top: 20px;">Have troubles/problems/found a bug?</h3>
<a style="font-weight: normal; font-size: 18px; line-height: 24px;" target="_blank" href="http://www.nextscripts.com/support">===&gt; Open support ticket &lt;===</a>


<h3 style="margin-top: 30px;">Like the Plugin? Would you like to support developers?</h3>
<div style="line-height: 24px;">
<b>Here is what you can do:</b><br/>
<?php if(function_exists('doPostToGooglePlus')) { ?><s><?php } ?><img src="<?php echo $nxs_plurl; ?>img/snap-icon12.png"/> Get the <a href="http://www.nextscripts.com/social-networks-auto-poster-for-wp-multiple-accounts/#getit">"Pro" Edition</a>. You will be able to add several accounts for each network as well as post to Google+, Pinterest and LinkedIn company pages.<?php if(function_exists('doPostToGooglePlus')) { ?></s> <i>Done! Thank you!</i><?php } ?><br/>
<img src="<?php echo $nxs_plurl; ?>img/snap-icon12.png"/> Rate the plugin 5 stars at <a href="http://wordpress.org/extend/plugins/social-networks-auto-poster-facebook-twitter-g/">wordpress.org page</a>.<br/>
<img src="<?php echo $nxs_plurl; ?>img/snap-icon12.png"/> <a href="<?php echo admin_url(); ?>post-new.php">Write a blogpost</a> about the plugin and don't forget to auto-post this blogpost to all your social networks ;-).<br/>
</div>
</td></tr></table>
   
   <br/><br/>
   <h3>Solutions for some common problems </h3>
   
   <b>Problem:</b> <i>I can't create an app on developers.facebook.com/apps</i>. When I am trying to enter that page it redirects me back to my account?<br/>
<b>Solution:</b> Facebook "Business" or "Advertising" accounts can't manage apps. This is an unavoidable Facebook limitation. Only real user accounts are able to create and manage apps. Please login to Facebook as a personal account to be able to create app. You will need to add your personal Facebook account as "Administrator" to your page..
   <br/><br/>
   <b>Problem:</b> When I follow the instructions to allow plugin authorize/access to my Facebook/Twitter/Tumblr/LinkedIn account, it redirects me to my <i>"Google Analytics for WordPress Configuration"</i> page.<br/>
<b>Solution:</b> It's a known issue. Google Analytics plugin hijacks the authorization workflow. Please temporary deactivate Google Analytics plugin, do all authorizations and then activate it back. There are some other plugins ("Blog Promoter", "Tweet Old Post", etc.. ) that could also hijack the authorization. Solution is the same: Deactivate the other plugin, do authorization, reactivate it.   
<br/><br/>

 <b>Problem:</b> Plugin breaks <i>NextGen galleries</i>. I got error <i>"Fatal error: Class 'nggMeta' not found"</i>.<br/>
<b>Solution:</b>There is a known bug in NextGen galleries that was reported back to them over a year ago, but still hasn't been fixed. Any plugin calling standard wordpress function apply_filters('the_content' will break NextGen galleries.
We have posted the solution here: <a target="_blank" href="http://wordpress.org/support/topic/plugin-nextgen-gallery-fatal-error-insert-picture-in-event?replies=4">http://wordpress.org/support/topic/plugin-nextgen-gallery-fatal-error-insert-picture-in-event?replies=4</a>
<br/><br/>

<b>Problem:</b> When I publish a new post to <i>Facebook</i> I am getting this weird Twitter Error:<i> Error:(#100) The status you are trying to publish is a duplicate of, or too similar to, one that we recently posted to Twitter</i>.<br/>
<b>Solution:</b> Your Facebook is already auto-posting to Twitter. When it sees the same tweet made by our plugin it fails with this error. You need to either unlink your Facebook from Twitter or disable Twitter auto-posting from our plugin.
If you decide to unlink your Facebook from Twitter:<br/>
Go to http://www.facebook.com/twitter and remove the link to twitter from the affected wall (Click on "Unlink from Twitter").
<br/><br/>

<b>Problem:</b> Facebook Error: <i>"The user hasn't authorized the application to perform this action"</i><br/>
<b>Solution:</b>
The most popular cause for "The user hasn't authorized the application to perform this action" is that your domain is not configured for your app.<br/>
Please read and carefully follow the installation instructions:<br/>
You missed/messed steps 1.4 and 1.5 from Facebook section:<br/>
4. Click "Website", enter your website URL<br/>
5. Enter your domain to the App Domain. Domain should be the same domain from URL that you have entered to the "Website" during the step 4.
<br/><br/>


<b>Problem:</b> Facebook Error:  <i>SSL certificate problem, verify that the CA cert is OK. Details:error:14090086:SSL routines:SSL3_GET_SERVER_CERTIFICATE:certificate verify failed</i><br/>
<b>Solution:</b>
This error means that cURL is misconfigured on your server. Most probably curl ssl(open SSL) is broken or it simply can't find the certificates at the pointed location. Please contact your hosting provider and ask them to fix this.<br/>
http://curl.haxx.se/docs/sslcerts.html<br/>
Unlike Twitter or Google+ that could be automatically switched to non-SSL connections in such cases, Facebook requires to be accessed by SSL at all times.

<br/><br/>

<b>Problem:</b> Twitter Error:  <i>{"error":"Read-only application cannot POST","request":"/1/statuses/update.json"}</i><br/>
<b>Solution:</b>You just need to follow the instructions step by step. Please don't skip anything.<br/>
<br/>
Please see #4 and #5 for Twitter:<br/>
<br/>
4. Click "Settings" tab. Scroll to the "Application type", change Access level from "Read Only" to <b>"Read and Write"</b>. Click "Update this Twitter application settings".<br/>
5. Come back to "Details" tab. Scroll to the "Your access token" and click "Create my access token" button. Refresh page and notice "Access token" and "Access token secret". Make sure you have <b>"Read and Write"</b> access level.<br/>

    </div> 
        
    </div>
</div>
           
           </form>
           <br/>&nbsp;<br/>           <?php
        }
        
        function NS_SNAP_ShowPageTop(){  global $nxs_snapAvNts, $nxs_snapThisPageUrl, $nxsOne, $nxs_plurl, $nxs_isWPMU; $nxsOne = ''; $options = $this->nxs_options; 
            $nxsOne = NextScripts_SNAP_Version; if (defined('NXSAPIVER')) $nxsOne .= " (<span id='nxsAPIUpd'>API</span> Version: ".NXSAPIVER.")"; ?>
           
           
           <div style="float:right; padding-top: 10px; padding-right: 10px;">
              <div style="float:right; text-align: center;"><a target="_blank" href="http://www.nextscripts.com"><img src="http://www.nextscripts.net/wp-content/uploads/2012/07/Next_Scripts_Logo2.1-HOR-100px.png"></a><br/>
              <a style="font-weight: normal; font-size: 16px; line-height: 24px;" target="_blank" href="http://www.nextscripts.com/support">[Contact support]</a> 
              <?php if(!$options['isMA']) { ?><br/> <span style="color:#800000;">Ready to to Upgrade to Multiple Accounts Edition<br/> and get Google+ and Pinterest Auto-Posting?</span>
              <br/><a style="font-weight: normal; font-size: 12px; line-height: 24px;" target="_blank" id="showLic" href="#">[Enter your Activation Key]</a>&nbsp;&nbsp;&nbsp;&nbsp;<a target="_blank" href="http://www.nextscripts.com/social-networks-auto-poster-for-wp-multiple-accounts#getit">[Get Key]</a>  <?php } ?>
              </div>
              <div id="showLicForm"><span class="nxspButton bClose"><span>X</span></span><div style="position: absolute; right: 10px; top:10px; font-family: 'News Cycle'; font-size: 34px; font-weight: lighter;">Activation</div>
              <br/><br/>
              <h3>Multiple Accounts Edition and Google+ and Pinterest Auto-Posting</h3><br/>You can find your key on this page: <a href="http://www.nextscripts.com/mypage">http://www.nextscripts.com/mypage</a>
                <br/><br/> Enter your Key:  <input name="eLic" id="eLic"  style="width: 50%;"/>
                <input type="button" class="button-primary" name="eLicDo" onclick="doLic();" value="Enter" />
                <br/><br/>Your plugin will be automatically upgraded. <?php wp_nonce_field( 'doLic', 'doLic_wpnonce' ); ?>
              </div>              
           </div> 

                    
           <div class=wrap><h2>Next Scripts: Social Networks Auto Poster Options</h2> Plugin Version: <span style="color:#008000;font-weight: bold;"><?php echo $nxsOne; ?></span> <?php if($options['isMA']) { ?> [Pro - Multiple Accounts Edition]&nbsp;&nbsp;<?php } else {?>
           <span style="color:#800000; font-weight: bold;">[Single Accounts Edition]</span>
           <?php if(!$nxs_isWPMU) { ?>
            - <a target="_blank" href="http://www.nextscripts.com/social-networks-auto-poster-for-wp-multiple-accounts">Get PRO - Multiple Accounts Edition</a><br/><br/>
            
           Here you can setup "Social Networks Auto Poster".<br/> You can start by clicking "Add new account" button and choosing the Social Network you would like to add.<?php }} ?><br/> 
           <?php 
           if (!function_exists('curl_init')) {  
               echo ('<br/><b style=\'font-size:16px; color:red;\'>Error: No CURL Found</b> <br/><i>Social Networks AutoPoster needs the CURL PHP extension. Please install it or contact your hosting company to install it.</i><br/>'); 
           }
           /*
           if ((defined('WP_ALLOW_MULTISITE') && WP_ALLOW_MULTISITE==true) || (defined('MULTISITE') &&  MULTISITE==true) ) { 
               echo "<br/><br/><br/><b style=\"font-size:16px; color:red;\">Sorry, we do not support Multiuser Wordpress at this time</b>"; return; 
           }
           */
           ?>
           
<?php if (function_exists('yoast_analytics')) { ?>
  <div class="error" id="message"><p><strong>You have Google Analytics Plugin installed and activated.</strong> This plugin hijacks the authorization workflow. Please temporary <a href="<?php echo admin_url();?>/plugins.php">deactivate</a> Google Analytics plugin, do all authorizations and then activate it back.</div>
<?php }  
        }
        
        function NS_SNAP_SavePostMetaTags($id) { global $nxs_snapAvNts, $plgn_NS_SNAutoPoster;  if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options;  
          if (isset($_POST["SNAPEdit"])) $nspost_edit = $_POST["SNAPEdit"];  
          if (isset($nspost_edit) && !empty($nspost_edit)) { delete_post_meta($id, 'snapEdIT'); add_post_meta($id, 'snapEdIT', '1' );
            foreach ($nxs_snapAvNts as $avNt) { 
              if (count($options[$avNt['lcode']])>0 && isset($_POST[$avNt['lcode']]) && count($_POST[$avNt['lcode']])>0) { $savedMeta = maybe_unserialize(get_post_meta($id, 'snap'.$avNt['code'], true)); $newMeta = $_POST[$avNt['lcode']]; 
              // echo "<br/>Code - ".$avNt['code']; echo "<br/>Saved"; prr($savedMeta); echo "<br/>Posted"; prr($newMeta);
                if (is_array($savedMeta) && is_array($newMeta)) $newMeta = nxsMergeArraysOV($savedMeta, $newMeta); delete_post_meta($id, 'snap'.$avNt['code']); add_post_meta($id, 'snap'.$avNt['code'], mysql_real_escape_string(serialize($newMeta))); 
              // echo "<br/>ToBeSaved:"; prr($newMeta);  
              }
            }            
          } // prr($_POST);
        }
        
        
        function NS_SNAP_AddPostMetaTags() { global $post, $nxs_snapAvNts, $plgn_NS_SNAutoPoster; $post_id = $post; if (is_object($post_id))  $post_id = $post_id->ID; if (!is_object($post)) $post = get_post($post_id);
          if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options; 
          ?>
          <div id="postftfp" class="postbox"><div class="inside"><div id="postftfp">
           <style type="text/css">div#popShAtt {display: none; position: absolute; width: 600px; padding: 10px; background: #eeeeee; color: #000000; border: 1px solid #1a1a1a; font-size: 90%; }
.underdash {border-bottom: 1px #21759B dashed; text-decoration:none;}
.underdash a:hover {border-bottom: 1px #21759B dashed}
</style>
          <script type="text/javascript"> if (typeof jQuery == 'undefined') { var script = document.createElement('script'); script.type = "text/javascript"; 
               script.src = "https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"; document.getElementsByTagName('head')[0].appendChild(script);
          }</script>
          
          <input value="SNAPEdit" type="hidden" name="SNAPEdit" />
          <?php if($post->post_status != "publish" ) { ?>
          <div style="float: right;">          
          <a href="#" onclick="jQuery('.nxsGrpDoChb').attr('checked','checked'); return false;">Check All</a>&nbsp;<a href="#" onclick="jQuery('.nxsGrpDoChb').removeAttr('checked'); return false;">Uncheck All</a>
          </div>
          <?php } ?>
           
          
          <table style="margin-bottom:40px; clear:both;" border="0"><?php        
          
          foreach ($nxs_snapAvNts as $avNt) { $clName = 'nxs_snapClass'.$avNt['code']; 
             if (count($options[$avNt['lcode']])>0) { $ntClInst = new $clName(); $ntClInst->showEdPostNTSettings($options[$avNt['lcode']], $post); }
          }
         ?></table></div></div></div> <?php 
        }
        //## Add MetaBox to Post->Edit
        function NS_SNAP_addCustomBoxes() { add_meta_box( 'NS_SNAP_AddPostMetaTags',  __( 'NextScripts: Social Networks Auto Poster - Post Options', 'NS_SNAutoPoster' ), array($this, 'NS_SNAP_AddPostMetaTags'), 'post' );           
          global $plgn_NS_SNAutoPoster;  if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options; 
          $args=array('public'=>true, '_builtin'=>false);  $output = 'names';  $operator = 'and';  $post_types = array(); if (function_exists('get_post_types')) $post_types=get_post_types($args, $output, $operator); 
          if ((isset($options['nxsCPTSeld'])) && $options['nxsCPTSeld']!='') $nxsCPTSeld = unserialize($options['nxsCPTSeld']); else $nxsCPTSeld = array_keys($post_types); //prr($nxsCPTSeld);
          foreach ($post_types as $cptID=>$cptName) if (in_array($cptID, $nxsCPTSeld)){ 
              add_meta_box( 'NS_SNAP_AddPostMetaTags',  __( 'NextScripts: Social Networks Auto Poster - Post Options', 'NS_SNAutoPoster' ), array($this, 'NS_SNAP_AddPostMetaTags'), $cptID );
          }    
        }
    }
}

if (class_exists("NS_SNAutoPoster")) { $plgn_NS_SNAutoPoster = new NS_SNAutoPoster(); }
//## Delete Account
if (!function_exists("ns_delNT_ajax")) { function ns_delNT_ajax(){ check_ajax_referer('nsDN'); $indx = (int)$_POST['id']; 
  global $plgn_NS_SNAutoPoster;  if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options; 
  unset($options[$_POST['nt']][$indx]); if (is_array($options)) update_option('NS_SNAutoPoster', $options);
}}
if (!function_exists("nsAuthFBSv_ajax")) { function nsAuthFBSv_ajax() { check_ajax_referer('nsFB');  $pgID = $_POST['pgID']; $fbs = array();
  global $plgn_NS_SNAutoPoster;  if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options;   
  foreach ($options['fb'] as $two) { if ($two['fbPgID']==$pgID) $two['wfa']=time(); $fbs[] = $two; } $options['fb'] = $fbs; if (is_array($options)) update_option('NS_SNAutoPoster', $options);
}}  
if (!function_exists("nsGetBoards_ajax")) { 
  function nsGetBoards_ajax() {  global $nxs_gCookiesArr; check_ajax_referer('getBoards');  global $plgn_NS_SNAutoPoster;  if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options; 
  if (get_magic_quotes_gpc()) { $_POST['u'] = stripslashes($_POST['u']);  $_POST['p'] = stripslashes($_POST['p']);} $_POST['p'] = trim($_POST['p']); $u = trim($_POST['u']);  
   $loginError = doConnectToPinterest($_POST['u'],  substr($_POST['p'], 0, 5)=='g9c1a'?nsx_doDecode(substr($_POST['p'], 5)):$_POST['p'] );  if ($loginError!==false) {echo $loginError; return "BAD USER/PASS";} 
   $gPNBoards = doGetBoardsFromPinterest();  $options['pn'][$_POST['ii']]['pnBoardsList'] = $gPNBoards;   
   $options['pn'][$_POST['ii']]['pnSvC'] = serialize($nxs_gCookiesArr); if (is_array($options)) update_option('NS_SNAutoPoster', $options); echo $gPNBoards; die();
  }
}     
if (!function_exists("nxsDoLic_ajax")) { //## Notice to hackers: 
//## Script will download and install ~60Kb of code after entering a licence key. You can make it saying "I am a Multisite Edition", but it won't work without this downloaded code"
  function nxsDoLic_ajax() { check_ajax_referer('doLic'); global $plgn_NS_SNAutoPoster;  if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options; 
    if(isset($_POST['lk']) && trim($_POST['lk'])!='') $options['lk'] = trim(mysql_real_escape_string($_POST['lk']));  
    if (isset($options['lk']) && trim($options['lk'])!='' ) { $options = getRemNSXOption($options); if (is_array($options)) update_option('NS_SNAutoPoster', $options); }
    if (strlen($options['uk'])>100) echo "OK"; else echo "NO"; die();
}} 

if (!function_exists("nxs_clLgo_ajax")) { function nxs_clLgo_ajax() { check_ajax_referer('nxsSsPageWPN'); 
  update_option('NS_SNAutoPosterLog', ''); echo "OK";
}} 

if (!function_exists("nxs_rfLgo_ajax")) { function nxs_rfLgo_ajax() { echo "W"; check_ajax_referer('nxsSsPageWPN'); echo "Y";
  $log = get_option('NS_SNAutoPosterLog'); 
  $logInfo = maybe_unserialize(get_option('NS_SNAutoPosterLog')); if (is_array($logInfo)) 
          foreach (array_reverse($logInfo) as $logline) echo '<snap style="color:#008000">['.$logline['date'].']</snap> - ['.$logline['nt'].'] -  <snap style="color:#'.($logline['type']=='E'?'FF0000':'585858').'">'.$logline['msg'].'</snap> '.$logline['extInfo'].'<br/>';
  
  
}} 

//## Initialize the admin panel if the plugin has been activated
if (!function_exists("nxs_AddSUASettings")) { function nxs_AddSUASettings() {  global $plgn_NS_SNAutoPoster, $nxs_plurl;  if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options;       
  add_menu_page('Social Networks Auto Poster', 'Social Networks Auto Poster', 'manage_options', basename(__FILE__), array(&$plgn_NS_SNAutoPoster, 'showSNAP_WPMU_OptionsPage'), $nxs_plurl.'img/snap-icon12.png');  }}
//## Initialize the admin panel if the plugin has been activated
if (!function_exists("NS_SNAutoPoster_ap")) { function NS_SNAutoPoster_ap() { global $plgn_NS_SNAutoPoster, $nxs_plurl;  if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options;       
 if (function_exists('add_options_page')) {
   add_options_page('Social Networks Auto Poster', '<img src="'.$nxs_plurl.'img/snap-icon12.png"/><span style="color:#287A0A">{SNAP}</span> Social Networks Auto Poster', 'manage_options', basename(__FILE__), array(&$plgn_NS_SNAutoPoster, 'showSNAutoPosterOptionsPage'));     
 }
}}

//## Main Function to Post 
if (!function_exists("nxs_snapPublishTo")) { function nxs_snapPublishTo($postArr, $type='', $aj=false) { global $plgn_NS_SNAutoPoster, $nxs_snapAvNts, $blog_id, $nxs_tpWMPU;   
 if (function_exists('nxs_doSMAS2')) { nxs_doSMAS2($postArr, $type, $aj); return; } else {  
  if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options;  $ltype=strtolower($type);    
  $ntOptions = $plgn_NS_SNAutoPoster->nxs_ntoptions; if (!isset($options['suaMode'])) $options['suaMode'] = '';
  if ($nxs_tpWMPU=='S') { switch_to_blog(1); $plgn_NS_SNAutoPoster = new NS_SNAutoPoster(); $options = $plgn_NS_SNAutoPoster->nxs_options; restore_current_blog(); }   
  if (!isset($options['nxsHTDP']) || $options['nxsHTDP']=='S') {$publtype='S'; $delay = rand(2,10);} else $publtype = 'I';
  if(is_object($postArr)) $postID = $postArr->ID; else $postID = $postArr;  $isPost = isset($_POST["SNAPEdit"]);    
  nxs_addToLog('Start =- ', 'M', '<span style="color:#008000; font-weight:bold;">------=========#### NEW AUTO-POST REQUEST PostID:('.$postID.') '.($publtype=='S'?'Scheduled +'.$delay:'Immediate').' ####=========------</span>'); // echo "UUU";
  $post = get_post($postID);   $args=array( 'public'   => true, '_builtin' => false);  $output = 'names';  $operator = 'and';  $post_types = array(); if (function_exists('get_post_types')) $post_types=get_post_types($args, $output, $operator); 
  $snap_isEdIT = get_post_meta($postID, 'snapEdIT', true); if ($snap_isEdIT!='1') { $doPost = true; $exclCats = $options['exclCats']; $postCats = wp_get_post_categories($postID);     
    if (is_array($exclCats) && is_array($postCats)) {     
      foreach ($postCats as $pCat) { if (in_array($pCat, $exclCats)) $doPost = false; else {$doPost = true; break;}} if (!$doPost) return;    
    }
  }  
  if ($options['nxsCPTSeld']!='') $nxsCPTSeld = unserialize($options['nxsCPTSeld']); else $nxsCPTSeld = array_keys($post_types); //prr($nxsCPTSeld);  
  $snap_isAutoPosted = get_post_meta($postID, 'snap_isAutoPosted', true); if ($snap_isAutoPosted=='1') return;    
  
  if ($post->post_type == 'post' || in_array($post->post_type, $post_types) && in_array($post->post_type, $nxsCPTSeld)) foreach ($nxs_snapAvNts as $avNt) { 
    if (count($options[$avNt['lcode']])>0) { $clName = 'nxs_snapClass'.$avNt['code'];
      if ($isPost) $po = $_POST[$avNt['lcode']]; else { $po =  get_post_meta($postID, 'snap'.$avNt['code'], true); $po =  maybe_unserialize($po);}            
      $optMt = $options[$avNt['lcode']][0]; if (isset($po) && is_array($po)) { $ntClInst = new $clName(); $optMt = $ntClInst->adjMetaOpt($optMt, $po[0]); }   
      $isCustBoxMeta = get_post_meta($postID, 'nxs_snapPostTo_'.$avNt['code'], true);
      if ($optMt['do'.$avNt['code']]=='1' || $isCustBoxMeta=='1') { delete_post_meta($postID, 'snap_isAutoPosted'); add_post_meta($postID, 'snap_isAutoPosted', '1'); $optMt['ii'] = 0;
        if ($publtype=='S') { // nxs_addToLog($logNT, 'M', $avNt['code'].' autopost scheduled');           
           $args = array($postID, $optMt);  wp_schedule_single_event(time()+$delay, 'ns_doPublishTo'.$avNt['code'], $args); 
           nxs_addToLog('Scheduled - '. $avNt['name'].' ('.$optMt['nName'].') post', 'M', ' - Post ID:('.$postID.')' );
        } else { $fname = 'nxs_doPublishTo'.$avNt['code']; $fname($postID, $optMt); }        
      }
      //$options['log'] .= "\r\n".(time()+2)." - ".'ns_doPublishTo'.$avNt['code'].print_r($args, true); update_option('NS_SNAutoPoster', $options);
    }
  }} if ($isS) restore_current_blog();
}}

//## AJAX to Post to Google+

//## Add settings link to plugins list
if (!function_exists("ns_add_settings_link")) { function ns_add_settings_link($links, $file) {
    static $this_plugin;
    if (!$this_plugin) $this_plugin = plugin_basename(__FILE__);
    if ($file == $this_plugin){
        $settings_link = '<a href="options-general.php?page=NextScripts_SNAP.php">'.__("Settings","default").'</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}}
//## Actions and filters    
if (!function_exists("ns_custom_types_setup")) { function ns_custom_types_setup(){ global $plgn_NS_SNAutoPoster;  if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options; 
  $args=array('public'=>true, '_builtin'=>false);  $output = 'names';  $operator = 'and';  $post_types = array(); if (function_exists('get_post_types')) $post_types=get_post_types($args, $output, $operator); 
  if ( isset($options['nxsCPTSeld']) && $options['nxsCPTSeld']!='') $nxsCPTSeld = unserialize($options['nxsCPTSeld']); else $nxsCPTSeld = array_keys($post_types); //prr($nxsCPTSeld);
  
  foreach ($post_types as $cptID=>$cptName) if (in_array($cptID, $nxsCPTSeld)){ // echo "|".$cptID."|";
    add_action('future_to_publish_'.$cptID, 'nxs_snapPublishTo');
    add_action('new_to_publish_'.$cptID, 'nxs_snapPublishTo');
    add_action('draft_to_publish_'.$cptID, 'nxs_snapPublishTo');
    add_action('pending_to_publish_'.$cptID, 'nxs_snapPublishTo');
    add_action('private_to_publish_'.$cptID, 'nxs_snapPublishTo');
    add_action('auto-draft_to_publish_'.$cptID, 'nxs_snapPublishTo');
  }
}} 

//## Format Message
if (!function_exists("nsFormatMessage")) { function nsFormatMessage($msg, $postID){ global $ShownAds; $post = get_post($postID); // $title = $post->post_title; echo "##".$title."##"; $tta = qtrans_split($title); prr($tta); prr($post); die();
  $msg = stripcslashes($msg); if (isset($ShownAds)) $ShownAdsL = $ShownAds; // $msg = htmlspecialchars(stripcslashes($msg)); 
  if (preg_match('%URL%', $msg)) { $url = get_permalink($postID); $msg = str_ireplace("%URL%", $url, $msg);}
  if (preg_match('%SURL%', $msg)) { $url = get_permalink($postID); $rurl = '';  global $plgn_NS_SNAutoPoster;  if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options;  
    if ($options['nxsURLShrtnr']=='B' && trim($options['bitlyUname']!='') && trim($options['bitlyAPIKey']!='')) {      
      $response  = wp_remote_get('https://api-ssl.bitly.com/v3/shorten?login='.$options['bitlyUname'].'&apiKey='.$options['bitlyAPIKey'].'&longUrl='.urlencode($url)); $rtr = json_decode($response['body'],true);
      if ($rtr['status_code']=='200') $rurl = $rtr['data']['url'];
    } //echo "###".$rurl;
    if ($options['nxsURLShrtnr']=='W' && function_exists('wpme_get_shortlink')) { $rurl = wp_get_shortlink($post->ID, 'post'); }
    if ($rurl=='') { $response  = wp_remote_get('http://gd.is/gtq/'.$url); if ((is_array($response) && ($response['response']['code']=='200'))) $rurl = $response['body']; }
    if ($rurl!='') $url = $rurl; $msg = str_ireplace("%SURL%", $url, $msg);
  } //die();
  if (preg_match('%IMG%', $msg)) { $imgURL = nxs_getPostImage($postID); $msg = str_ireplace("%IMG%", $imgURL, $msg); } 
  if (preg_match('%TITLE%', $msg)) { $title = nxs_doQTrans($post->post_title, $lng);  $msg = str_ireplace("%TITLE%", $title, $msg); }                    
  if (preg_match('%STITLE%', $msg)) { $title = nxs_doQTrans($post->post_title, $lng);   $title = substr($title, 0, 115); $msg = str_ireplace("%STITLE%", $title, $msg); }                    
  if (preg_match('%AUTHORNAME%', $msg)) { $aun = $post->post_author;  $aun = get_the_author_meta('display_name', $aun );  $msg = str_ireplace("%AUTHORNAME%", $aun, $msg);}                    
  if (preg_match('%TEXT%', $msg)) {      
    if ($post->post_excerpt!="") $excerpt = apply_filters('the_content', nxs_doQTrans($post->post_excerpt), $lng); else $excerpt= apply_filters('the_content', nxs_doQTrans($post->post_content), $lng); 
      $excerpt = nsTrnc(strip_tags(strip_shortcodes($excerpt)), 300, " ", "..."); $msg = str_ireplace("%TEXT%", $excerpt, $msg);
  }
  if (preg_match('%FULLTEXT%', $msg)) { $postContent = apply_filters('the_content', nxs_doQTrans($post->post_content), $lng); $msg = str_ireplace("%FULLTEXT%", $postContent, $msg);}                    
  if (preg_match('%RAWTEXT%', $msg)) { $postContent = nxs_doQTrans($post->post_content, $lng); $msg = str_ireplace("%RAWTEXT%", $postContent, $msg);}
  if (preg_match('%SITENAME%', $msg)) { $siteTitle = htmlspecialchars_decode(get_bloginfo('name'), ENT_QUOTES); $msg = str_ireplace("%SITENAME%", $siteTitle, $msg);}      
  if (isset($ShownAds)) $ShownAds = $ShownAdsL; // FIX for the quick-adsense plugin
  return $msg;
}}

if (!function_exists("nxs_adminInitFunc")) { function nxs_adminInitFunc(){ global $plgn_NS_SNAutoPoster, $nxs_snapThisPageUrl, $pagenow, $nxs_isWPMU; 
  $nxs_snapThisPageUrl = admin_url().($pagenow=='admin.php'?'network/':'').$pagenow.'?page=NextScripts_SNAP.php'; 
  //## Javascript to Admin Panel        
  if (( ($pagenow=='options-general.php'||$pagenow=='admin.php')&& $_GET['page']=='NextScripts_SNAP.php') ||$pagenow=='post.php'||$pagenow=='post-new.php'){add_action('admin_head', 'jsPostToSNAP'); add_action('admin_head', 'nxs_jsPostToSNAP2');}
}}
if (!function_exists("nxs_adminInitFunc2")) { function nxs_adminInitFunc2(){ global $plgn_NS_SNAutoPoster, $nxs_snapThisPageUrl, $pagenow;   $nxs_snapThisPageUrl = admin_url().($pagenow=='admin.php'?'network/':'').$pagenow.'?page=NextScripts_SNAP.php';  //## Add MEtaBox to Post Edit Page
  if (current_user_can("see_snap_box") || current_user_can("manage_options")) add_action('add_meta_boxes', array($plgn_NS_SNAutoPoster, 'NS_SNAP_addCustomBoxes'));        
}}

function nxs_saveSiteSets_ajax(){ check_ajax_referer('nxssnap'); 
   if ($_POST['sid']=='A'){  global $wpdb; $allBlogs = $wpdb->get_results("SELECT blog_id FROM wp_blogs where blog_id > 1");
     foreach( $allBlogs as $aBlog ) { switch_to_blog($aBlog->blog_id); $plgn_NS_SNAutoPoster = new NS_SNAutoPoster(); 
       $options = $plgn_NS_SNAutoPoster->nxs_options; $options['suaMode'] = $_POST['sset']; update_option($plgn_NS_SNAutoPoster->dbOptionsName, $options);
     }       
   } else { switch_to_blog($_POST['sid']); $plgn_NS_SNAutoPoster = new NS_SNAutoPoster(); 
     $options = $plgn_NS_SNAutoPoster->nxs_options; $options['suaMode'] = $_POST['sset']; update_option($plgn_NS_SNAutoPoster->dbOptionsName, $options); //    prr($plgn_NS_SNAutoPoster->dbOptionsName);  prr($options);
   }
}

function nxs_start_ob(){ob_start( 'nxs_ogtgCallback' );}
function nxs_end_flush_ob(){ob_end_flush();}

function nxs_ogtgCallback($content){ global $post, $plgn_NS_SNAutoPoster;  if (!isset($plgn_NS_SNAutoPoster)) return; $options = $plgn_NS_SNAutoPoster->nxs_options;    $ogimgs = array();  
  if (stripos($content, 'og:title')!==false) $ogOut = "\r\n"; else {    
    $title = preg_match( '/<title>(.*)<\/title>/', $content, $title_matches );  
    if ($title !== false && count( $title_matches) == 2 ) $ogT ='<meta property="og:title" content="' . $title_matches[1] . '" />'."\r\n"; else {
      if (is_home() || is_front_page() )  $ogT = get_bloginfo( 'name' ); else $ogT = get_the_title();
      $ogT =  '<meta property="og:title" content="' . esc_attr( apply_filters( 'nxsog_title', $ogT ) ) . '" />'."\r\n";          
    }    
    $decsription = preg_match( '/<meta name="description" content="(.*)"/', $content, $description_matches );    
    if ( $description !== false && count( $description_matches ) == 2 ) $ogD = '<meta property="og:description" content="' . $description_matches[1] . '" />'."\r\n"; {
      if (is_singular()) {
        if(has_excerpt($post->ID))$ogD=strip_tags(nxs_snapCleanHTML(get_the_excerpt($post->ID)));else $ogD= str_replace("  ", ' ', str_replace("\r\n", ' ', trim(substr(strip_tags(nxs_snapCleanHTML(strip_shortcodes($post->post_content))), 0, 200))));
      } else $ogD = get_bloginfo('description');  $ogD = preg_replace('/\r\n|\r|\n/m','',$ogD); 
      $ogD = '<meta property="og:description" content="'.esc_attr( apply_filters( 'nxsog_desc', $ogD ) ).'" />'."\r\n";          
    }    
    $ogSN = '<meta property="og:site_name" content="'.get_bloginfo('name').'" />'."\r\n";
    $ogLoc = '<meta property="og:locale" content="'.strtolower(esc_attr(get_locale())).'" />'."\r\n"; $iss = is_home();  
    $ogType = is_singular()?'article':'website'; if($vidsFromPost == false) $ogType = '<meta property="og:type" content="'.esc_attr(apply_filters('nxsog_type', $ogType)).'" />'."\r\n";                  
        
    if (is_home() || is_front_page()) $ogUrl = get_bloginfo( 'url' ); else $ogUrl = 'http' . (is_ssl() ? 's' : '') . "://".$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $ogUrl = '<meta property="og:url" content="'.esc_url( apply_filters( 'nxsog_url', $ogUrl ) ) . '" />' . "\r\n";
  
    if (!is_home()) { /*
      $vidsFromPost = nsFindVidsInPost($post); if ($vidsFromPost !== false && is_singular()) {  echo '<meta property="og:video" content="http://www.youtube.com/v/'.$vidsFromPost[0].'" />'."\n";  
      echo '<meta property="og:video:type" content="application/x-shockwave-flash" />'."\n";
      echo '<meta property="og:video:width" content="480" />'."\n";
      echo '<meta property="og:video:height" content="360" />'."\n";
      echo '<meta property="og:image" content="http://i2.ytimg.com/vi/'.$vidsFromPost[0].'/mqdefault.jpg" />'."\n";
      echo '<meta property="og:type" content="video" />'."\n"; 
    } */
      if (function_exists('has_post_thumbnail') && has_post_thumbnail($post->ID)) {
        $thumbnail_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'thumbnail' ); $ogimgs[] = $thumbnail_src[0];
      } $imgsFromPost = nsFindImgsInPost($post, (int)$options['advFindOGImg']==1);           
      if ($imgsFromPost !== false && is_singular() && is_array($ogimgs) && is_array($imgsFromPost))  $ogimgs = array_merge($ogimgs, $imgsFromPost);       
    }       
    //## Add default image to the endof the array
    if ( count($ogimgs)<1 && isset($options['ogImgDef']) && $options['ogImgDef']!='') $ogimgs[] = $options['ogImgDef']; 
    //## Output og:image tags
    if (!empty($ogimgs) && is_array($ogimgs)) foreach ($ogimgs as $ogimage)  $ogImgsOut = '<meta property="og:image" content="'.esc_url(apply_filters('ns_ogimage', $ogimage)).'" />'."\r\n"; 
    $ogOut  = "\r\n".$ogSN.$ogT.$ogD.$ogType.$ogUrl.$ogLoc.$ogImgsOut;
  } $content = str_ireplace('<!-- ## NXSOGTAGS ## -->', $ogOut, $content); 
  return $content;
}

function nxs_addOGTagsPreHolder() { echo "<!-- ## NXS/OG ## --><!-- ## NXSOGTAGS ## --><!-- ## NXS/OG ## -->\n\r";}
 
//## Actions and filters    
if (isset($plgn_NS_SNAutoPoster)) { //## Actions
  //## Add the admin menu    
  if ($nxs_isWPMU) add_action('network_admin_menu', 'nxs_AddSUASettings'); $suOptions = array();
  $suOptions = $plgn_NS_SNAutoPoster->nxs_options; if ($nxs_isWPMU) { $ntOptions = $plgn_NS_SNAutoPoster->nxs_ntoptions; if (!isset($suOptions['suaMode'])) $suOptions['suaMode'] = ''; }  
  
  $isO = !$nxs_isWPMU || ($nxs_isWPMU && ($suOptions['isMU']||$suOptions['isMUx']) && ($suOptions['suaMode']=='O' || ($suOptions['suaMode']=='' && $ntOptions['nxsSUType']=='O')));
  $isS = !$nxs_isWPMU || ($nxs_isWPMU && ($suOptions['isMU']||$suOptions['isMUx']) && ($suOptions['suaMode']=='S' || ($suOptions['suaMode']=='' && $ntOptions['nxsSUType']=='S')));
  if ($nxs_isWPMU) { if ($isO) $nxs_tpWMPU = 'O'; elseif ($isS) $nxs_tpWMPU = 'S';} // prr($nxs_tpWMPU); prr($suOptions);
  
  if (function_exists('nxs_doSMAS3')) nxs_doSMAS3($isS, $isO);

  add_action('admin_init', 'nxs_adminInitFunc');  
  add_action('wp_ajax_nxsDoLic' , 'nxsDoLic_ajax');     
  
  add_action('wp_ajax_nxs_clLgo', 'nxs_clLgo_ajax');
  add_action('wp_ajax_nxs_rfLgo', 'nxs_rfLgo_ajax');
  
  if ($isO || $isS) {    
  //## Whenever you publish a post, post to Social Networks
    add_action('future_to_publish', 'nxs_snapPublishTo');
    add_action('new_to_publish', 'nxs_snapPublishTo');
    add_action('draft_to_publish', 'nxs_snapPublishTo');
    add_action('pending_to_publish', 'nxs_snapPublishTo');   
    add_action('private_to_publish', 'nxs_snapPublishTo');
    add_action('auto-draft_to_publish', 'nxs_snapPublishTo');
    //## Add nxs_snapPublishTo to custom post types
    add_action('wp_loaded', 'ns_custom_types_setup' );       
    foreach ($nxs_snapAvNts as $avNt) { add_action('ns_doPublishTo'.$avNt['code'], 'nxs_doPublishTo'.$avNt['code'], 1, 2); }
    
  }  
  if ($isO) {    
    add_action('admin_menu', 'NS_SNAutoPoster_ap');    
    add_action('admin_init', 'nxs_adminInitFunc2');    
    //## Initialize options on plugin activation
    $myrelpath = preg_replace( '/.*wp-content.plugins./', '', __FILE__ ); 
    add_action("activate_".$myrelpath,  array(&$plgn_NS_SNAutoPoster, 'init'));    
    
    //## Add/Change meta on Save
    add_action('edit_post', array($plgn_NS_SNAutoPoster, 'NS_SNAP_SavePostMetaTags'));
    add_action('publish_post', array($plgn_NS_SNAutoPoster, 'NS_SNAP_SavePostMetaTags'));
    add_action('save_post', array($plgn_NS_SNAutoPoster, 'NS_SNAP_SavePostMetaTags'));
    add_action('edit_page_form', array($plgn_NS_SNAutoPoster, 'NS_SNAP_SavePostMetaTags'));         
    
    //## Add AJAX Calls for Test and Repost
    foreach ($nxs_snapAvNts as $avNt) { add_action('wp_ajax_rePostTo'.$avNt['code'], 'nxs_rePostTo'.$avNt['code'].'_ajax'); }
    add_action('wp_ajax_getBoards' , 'nsGetBoards_ajax');
    add_action('wp_ajax_nsDN', 'ns_delNT_ajax');
    
    add_action('wp_ajax_nsAuthFBSv', 'nsAuthFBSv_ajax');
    //## Custom Post Types and OG tags
    add_filter('plugin_action_links','ns_add_settings_link', 10, 2 );

    //## Scedulled Publish Calls    
    
    
    if ((int)$suOptions['nsOpenGraph'] == 1) {    
      add_action( 'init', 'nxs_start_ob', 0 );
      add_action( 'wp_footer', 'nxs_end_flush_ob', 10000 ); 
      add_action('wp_head', 'nxs_addOGTagsPreHolder', 150);
    }    
  }
    
  if ($nxs_isWPMU){      
      if (function_exists('nxssnapmu_columns_head')) add_filter('wpmu_blogs_columns', 'nxssnapmu_columns_head');
      if (function_exists('nxssnapmu_columns_content')) add_action('manage_blogs_custom_column', 'nxssnapmu_columns_content', 10, 2);
      if (function_exists('nxssnapmu_columns_content')) add_action('manage_sites_custom_column', 'nxssnapmu_columns_content', 10, 2);    
      if (function_exists('nxs_add_style')) add_action( 'admin_footer', 'nxs_add_style' );  
      if (function_exists('nxs_saveSiteSets_ajax')) add_action('wp_ajax_nxs_saveSiteSets', 'nxs_saveSiteSets_ajax');
  }
}
?>