<?php
                               
global $tabOptions;
        //var_dump($tabOptions);
        //query all categories
        $aExclude=get_category_ids(array('vacancies','thematic-partner','alliance-members','none'));
//var_dump($aExclude);
        if($tabOptions['categories']=='all'){
            $aCategories = get_categories(array('exclude'=>join(',',$aExclude)));
            //var_dump($aCategories);
        }else{
            $aCategories=array();
            if(!is_array($tabOptions['categories']))$tabOptions['categories']=array();
            $iNone = array_search(1,$tabOptions['categories']);
            if($iNone!==false)unset($tabOptions['categories'][$iNone]);
            if(isset($tabOptions['country'])){
                $aInclude = get_category_ids(array('blog'));
                $tabOptions['categories']=array_merge($tabOptions['categories'],$aInclude);
            }
            $tabOptions['categories']=array_unique($tabOptions['categories']);
            
            if(count($tabOptions['categories'])>0){
                $aCategories=get_categories(array('exclude'=>join(',',$aExclude),'include'=>join(',',$tabOptions['categories'])));
            }
            //foreach($tabOptions['categories'] AS $iCatID)$aCategories[]=get_category($iCatID);
        }
	if(count($aCategories)>0 || $tabOptions['showUpdates']){
        $aPosts = array('all'=>'');

        $aCategoryIDs = array();
        $aCategoryNames = array();
        
        ?>
            
        <?php
        $keys = array('financial','institutional','environmental','technical','social');
         foreach($aCategories AS $aCategory){
             
            $aCategoryIDs[$aCategory->name]=$aCategory->cat_ID;
            $aCategoryNames[array_search($aCategory->slug,$keys)]=$aCategory->name;
            
           
        }
//var_dump($aCategoryIDs);
         ksort($aCategoryNames);
//ksort($aCategoryIDs);
        $aPosts=array();
        $aUpdates=array();
        global $wp_query;
        if(isset($tabOptions['page'])){
            $page = ($tabOptions['page']) ? $tabOptions['page'] : 1;
        }else{
            $page = (get_query_var('paged')) ? get_query_var('paged') : 1;
        }
        $temp = $wp_query;
        if($tabOptions['doquery']==true){
            $wp_query = null;
            $wp_query = new WP_Query();
            if((!isset($tabOptions['onlyupdates']) || $tabOptions['onlyupdates']==0) && (count($aCategoryIDs)>0 || isset($tabOptions['country']))){
                $args=array();
                if(count($aCategoryIDs)>0){
                    $args = array(
                        'category__in'=>$aCategoryIDs,
                        'posts_per_page' => -1,
                        'nopaging'=>true
                    );
                }
                if(isset($tabOptions['country'])){
                    $aTagIDs = get_tag_ids(array($tabOptions['country']));
                    $args['tag__in']=join(',',$aTagIDs);
                }
    //var_dump($args);
                //get posts for category
                $aPosts = $wp_query->query($args);
            }
            if($tabOptions['showUpdates']){
 
                    $args= array(
                        'post_type'=>'project_update',
                        'posts_per_page' => -1
                        );
                   
                    if(isset($tabOptions['country'])){
                        $oAPC = new AkvoPartnerCommunication();
                        $aProjectUpdates = $oAPC->readProjectUpdatesFromDbByCountry($tabOptions['country']);
                        // var_dump($aProjectUpdates);
                        $tabOptions['updateIDs']=$aProjectUpdates;
                        $args['post__in']=$tabOptions['updateIDs'];
                    }
                    
                    if(
                            !isset($tabOptions['updateIDs']) || 
                            (isset($tabOptions['updateIDs']) && count($tabOptions['updateIDs'])>0)
                        ){
                        //$args['paged']=$page;
                        $aUpdates = $wp_query->query($args);
                       // var_dump($wp_query->query);
                    }


            }
            $aAllPosts = array_merge($aPosts,$aUpdates);
            //array_unique($aAllPosts);
            $aAllPostIDs = wp_list_pluck($aAllPosts, 'ID');
            if(count($aAllPostIDs)>0){
                $aPostTypes = array('post','project_update') ;
                $args = array(
                    'post__in'=>$aAllPostIDs,
                    'post_type'=>$aPostTypes,
                    'posts_per_page' => (isset($tabOptions['numposts'])) ? $tabOptions['numposts'] : 9,
                    'posts_per_archive_page' => (isset($tabOptions['numposts'])) ? $tabOptions['numposts'] : 9,
                    'paged' => $page
                    );
                //var_dump($args);
                $aPosts = $wp_query->query($args);
                //var_dump($wp_query->query);
                //die();
            }
        }
        if($tabOptions['showTabs']){ 
        ?>
           <ul class="cUlBlogCats"> 
               <li id="iLiBlogPosts" class="cLiFilterBy" rel="all">
					Filter by:
				</li>
				<li id="iLiBlogPosts" class="cLiBlogCat" rel="all">
					<a>All</a>
				</li>
            <?php
            //if show updates is true, add project updates tab

            if($tabOptions['showUpdates']){

            ?>
				<li id="iLiBlogPosts" class="cLiBlogCat" rel="project updates">
					<a>Project updates</a>
				</li>
            <?php
            }
            if(!isset($tabOptions['showcategories']) && $tabOptions['categories']=='all'){?>
                <li id="iLiBlogPosts" class="cLiBlogCat" rel="blogposts">
                            <a>Blog posts</a>
                        </li>
            <?php
            
            }else{
            ///add category tab per category
                foreach($aCategoryNames AS $iK=>$sCategory){
                    ?>
                        <li id="iLiBlogPosts" class="cLiBlogCat" rel="<?php echo $sCategory; ?>" catid ="<?php echo $aCategoryIDs[$sCategory]; ?>">
                            <a><?php echo $sCategory; ?></a>
                        </li>
                <?php
                }
            }?><br style="clear:both;" />
            </ul>
<?php }
//echo $tabOptions['showTabs'];
if($tabOptions['showTabs']==0){
?>
            <div id="iDivBlogPosts">
                    <div class="cDivBlogPosts">
                        <ul class="cUlBlogPosts">

                            <?php
                            if ( have_posts() ) : while ( have_posts() ) : the_post();
                                //continue;
                                //die();
								$postid = $post->ID;
								$title = wash_the_content_filter($post->post_title);
								$date = date('M d, Y',  strtotime($post->post_date));
                                if($post->post_type=='post'){
                                    $aPostCats =wp_get_post_categories($post->ID,array('fields'=>'all'));
                                    $aPostTags = wp_get_post_tags($post->ID);
                                    //var_dump($aPostTags);
                                    $aPostCatNames=array();
				foreach($aPostCats AS $oCat)$aPostCatNames[]=$oCat->name;
                                    $aPostTagNames=array();
				foreach($aPostTags AS $oTag)$aPostTagNames[]=$oTag->name;
                                    $sCategory = $aCategoryNames[array_search($aPostCats[0]->name,$aCategoryNames)];
                                    $sCategoryTag = join(', ',array_slice($aPostCatNames,0,2));
                                    //$sCategoryTag = '';
                                    $sReadMoreLink = get_permalink($post->ID);
                                    $width = 271;
                                    $height = 167;
                                    $classtext = 'no-border';
                                    $thumbnail = get_thumbnail($width, $height, $classtext, $title, $title, true, 'Featured');

                                    $thumb =$thumbnail['thumb'];
                                    $sImgSrc = print_thumbnail($thumb, $thumbnail["use_timthumb"], $title, $width, $height, $classtext,false,true);
                                    if($sImgSrc==''){
                                        $sFirstImg = catch_that_image();
                                        if($sFirstImg!=''){ 
                                            //$sFirstImg = str_replace('http://washalliance.nl/wp-content/blogs.dir/2/','http://www.washalliance.nl/',$sFirstImg);
                                            $sImgSrc = '/wp-content/plugins/akvo-site-config/classes/thumb.php?src='.$sFirstImg.'&w=271&h=167&zc=1&q=100';   
                                        }//$sImgSrc = catch_that_image();
                                    }
                                    if($sImgSrc==''){
                                        if (function_exists('z_taxonomy_image_url')){
                                            if(count($aPostTags)>0){
                                                //var_dump($aPostTags[0]);
                                                $sImgSrc= z_taxonomy_image_url($aPostTags[0]->term_id);
                                            }else{
                                                $sImgSrc= z_taxonomy_image_url($aPostCats[0]->term_id); 
                                            }
                                        }
                                    }
                                    if($sImgSrc=='' && isset($tabOptions['country'])){
                                        $sImgSrc = '/wp-content/themes/Quadro/images/countryplaceholders/'.$tabOptions['country'].'.jpg';
                                    }
                                    $sPostLabelImgClass='cDivBlogPostImageTag';
                                }elseif($post->post_type=='project_update'){
                                    $sCategory = 'project updates';
                                    $sCategoryTag = 'project updates';
                                    
                                    $aPostAttachments = AkvoPartnerCommunication::getUpdateImages($post->ID);
                                    $sAttachmentLink = null;
                                    if ($aPostAttachments) {
                                        foreach ($aPostAttachments as $oAttachment) {
                                            $sAttachmentLink = wp_get_attachment_url($oAttachment->ID);
                                        }
                                    }
                                    $sImgSrc = "";
                                    if (!is_null($sAttachmentLink)) {

                                        $sImgSrc = str_replace('uploads20', 'uploads/20', $sAttachmentLink);
                                        $sImgSrc = str_replace('files20', 'files/20', $sImgSrc);
                                        if(!@getimagesize($sImgSrc))$sImgSrc='';
                                    }
                                    if($sImgSrc==''){
                                        $sCountry = AkvoPartnerCommunication::readProjectUpdateCountry($post->ID);
                                        if($sCountry)$sImgSrc = '/wp-content/themes/Quadro/images/countryplaceholders/'.$sCountry.'.jpg';
                                    }
                                    $sPostLabelImgClass='cDivProjUpdateImageTag';
                                    //get the project Id to read more link (link to akvo.org site)
                                    $sReadMoreLink = "http://washalliance.akvoapp.org/en/project/";
                                    $oProjectId = $wpdb->get_results("SELECT project_id,update_id FROM " . $wpdb->prefix . "project_update_log WHERE post_id = ".$post->ID);
                                    foreach ($oProjectId as $iId){
                                        $iProjectId = $iId->project_id;
                                        $iUpdateId = $iId->update_id;
                                    }
                                    $sReadMoreLink = $sReadMoreLink.$iProjectId.'/update/'.$iUpdateId;
                                }
                                $sNoImgClass = ($sImgSrc=='') ? 'noImg' : '';
                                if(
                                       $sImgSrc==''
                                        
                                    ){
//                                    echo "NO IMG";
//                                    var_dump($post);
//                                    continue;
                                }
								//$i++;
								?>
								<li class="cLiBlogPost <?php echo $sNoImgClass; ?>" rel="<?php echo $sCategory; ?>" posttype="<?php echo $post->post_type; ?>">
									<div class="<?php echo $sPostLabelImgClass.' '.$sTagPlacementClass; ?>"></div>
									<?php if($sImgSrc!=''){ ?>
                                        <div class="cDivBlogPostImageWrapper">
                                            <div class="cDivBlogPostImage">
                                                <img src="<?php echo $sImgSrc; ?>" />
                                            </div>
                                        </div>
                                    <?php } ?>
									<div class ="cDivBlogPostTitle">
									<h2>
										<a href="<?php echo esc_url($sReadMoreLink); ?>" title="<?php echo esc_attr($title); ?>">
										<?php $sTitle = textClipper(strip_tags($title), 40);?>	
                                        <?php echo $sTitle; ?>
										</a>
									</h2>
									</div>

									<div class="cDivBlogPostDate">
										<?php echo $date.'  -  '.$sCategoryTag; ?>
									</div>
									
									<div class="cDivBlogPostTextContent">
										<?php
										$sContent = wash_the_content_filter($post->post_content);
                                        $iClipText =($sNoImgClass=='noImg') ? 800 : 200;
										echo textClipper(strip_tags($sContent), $iClipText);
                                        ?>
									</div>

									<div class="cDivReadmore">
										<a href="<?php echo $sReadMoreLink; ?>" rel="bookmark" title="<?php printf(esc_attr__('Permanent Link to %s', 'Quadro'), the_title()) ?>"><?php esc_html_e('Read More', 'Quadro'); ?></a>
									</div>
                                    <br style="clear:both;" />
								</li>
							<?php
							endwhile; endif;
                            
                            ?>
                            <br style="clear:both;" /> 
                            <div class="pagination">
                                <input type="hidden" name="iInputQueryString" id="iInputQueryString" value="<?php echo urldecode(http_build_query($tabOptions)); ?>" />
                                <?php
                                $prevpage = ($page-1);
                                $nextpage = ($page+1);
                                if($prevpage){
                                    $tabOptions['page']=$prevpage;
                                    $prevPaginationlink = urldecode(http_build_query($tabOptions));
                                    echo '<span><a href="#" onclick="getEntries(this);" rel="'.$prevPaginationlink.'">&laquo; Older Entries</a></span>&nbsp;&nbsp;&nbsp;';
                                }
                                if(get_next_posts_link()){
                                    $tabOptions['page']=$nextpage;
                                    $nextPaginationlink = urldecode(http_build_query($tabOptions));
                                    echo '<span><a href="#" onclick="getEntries(this);" rel="'.$nextPaginationlink.'">Next Entries &raquo;</a></span>';
                                }
                                
                                ?>
                            </div>
                    </ul><br style="clear:both;" />
                    
                    
        </div><br style="clear:both;" />
    </div>
<?php }else{ ?>
<div id="iDivBlogPosts">
    <input type="hidden" name="iInputQueryString" id="iInputQueryString" value="<?php echo urldecode(http_build_query($tabOptions)); ?>" />
</div>
<?php }
} ?> 