<?php
if (!defined('ABSPATH')) exit;
function franklem_setup(){
 load_theme_textdomain('franklem-news',get_template_directory().'/languages');
 add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('html5',['search-form','gallery','caption','style','script']); add_theme_support('responsive-embeds'); add_theme_support('editor-styles');
 register_nav_menus(['primary'=>'Menu principal','footer'=>'Menu do rodapé']);
 add_image_size('franklem-card',760,428,true);
 add_image_size('franklem-schema-square',1200,1200,true);
 add_image_size('franklem-schema-4x3',1200,900,true);
 add_image_size('franklem-schema-16x9',1200,675,true);
}
add_action('after_setup_theme','franklem_setup');
function franklem_assets(){ wp_enqueue_style('franklem-style',get_stylesheet_uri(),[],wp_get_theme()->get('Version')); wp_enqueue_style('franklem-caption',get_template_directory_uri().'/assets/caption.css',['franklem-style'],wp_get_theme()->get('Version')); wp_enqueue_script('franklem-script',get_template_directory_uri().'/assets/theme.js',[],wp_get_theme()->get('Version'),true); }
add_action('wp_enqueue_scripts','franklem_assets');
function franklem_excerpt_length(){return 24;} add_filter('excerpt_length','franklem_excerpt_length');
function franklem_section_data(){return ['politica'=>['Política','#d64a3a'],'tecnologia-ciencia'=>['Tecnologia & Ciência','#18786f'],'esporte'=>['Esporte','#bd8b1e']];}
function franklem_first_category($post_id=null){$cats=get_the_category($post_id);return $cats?$cats[0]:null;}
function franklem_subtitle($post_id=null){return get_post_meta($post_id?:get_the_ID(),'franklem_subtitle',true);}
function franklem_ai_image_caption($html,$post_id){if(!is_singular('post')||!get_post_meta($post_id,'fne_ai_reviewed',true))return $html;return '<figure class="featured-figure">'.$html.'<figcaption class="featured-caption">Imagem ilustrativa gerada por inteligência artificial.</figcaption></figure>';}
add_filter('post_thumbnail_html','franklem_ai_image_caption',10,2);
function franklem_add_subtitle_box(){add_meta_box('franklem-subtitle','Subtítulo da notícia','franklem_subtitle_box','post','normal','high');} add_action('add_meta_boxes','franklem_add_subtitle_box');
function franklem_subtitle_box($post){wp_nonce_field('franklem_save_subtitle','franklem_subtitle_nonce');$v=franklem_subtitle($post->ID);echo '<textarea style="width:100%;min-height:80px" name="franklem_subtitle">'.esc_textarea($v).'</textarea><p>Resumo curto exibido abaixo do título.</p>';}
function franklem_save_subtitle($id){if(!isset($_POST['franklem_subtitle_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['franklem_subtitle_nonce'])),'franklem_save_subtitle')||defined('DOING_AUTOSAVE'))return;if(current_user_can('edit_post',$id)&&isset($_POST['franklem_subtitle']))update_post_meta($id,'franklem_subtitle',sanitize_textarea_field(wp_unslash($_POST['franklem_subtitle'])));} add_action('save_post_post','franklem_save_subtitle');
function franklem_activate(){
 foreach(array_keys(franklem_section_data()) as $slug){if(!term_exists($slug,'category'))wp_insert_term(franklem_section_data()[$slug][0],'category',['slug'=>$slug]);}
 $pages=['sobre'=>['Sobre','O Franklem News é um portal independente dedicado a explicar os assuntos essenciais do dia com clareza, contexto e transparência.'],'contato'=>['Contato','Use esta página para disponibilizar um formulário ou endereço de contato editorial.'],'politica-editorial'=>['Política Editorial','Publicamos sínteses originais, verificamos informações em fontes identificadas e corrigimos erros com transparência. Conteúdos auxiliados por inteligência artificial passam por supervisão editorial.'],'privacidade'=>['Política de Privacidade','Esta página deverá informar o uso de cookies, métricas de audiência, publicidade e os direitos dos titulares conforme a LGPD.'],'correcoes'=>['Correções','Correções relevantes serão registradas com clareza, indicando o que foi alterado e a data da atualização.']];
 foreach($pages as $slug=>$p){if(!get_page_by_path($slug))wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$p[0],'post_name'=>$slug,'post_content'=>$p[1]]);}
}
add_action('after_switch_theme','franklem_activate');

/**
 * SEO essencial do tema. Se um plugin de SEO conhecido estiver ativo, o tema
 * deixa a geração de metadados a cargo dele para evitar tags duplicadas.
 */
function franklem_has_seo_plugin(){
 return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('SEOPRESS_VERSION') || defined('AIOSEO_VERSION') || class_exists('The_SEO_Framework\Load');
}

function franklem_meta_description(){
 if(is_front_page())return 'Notícias de política, tecnologia, ciência e esporte com contexto, fontes transparentes e atualização diária no Franklem News.';
 if(is_singular()){
  $description=is_single()?franklem_subtitle():'';
  if(!$description)$description=get_the_excerpt();
 }elseif(is_category()||is_tag()||is_tax()){
  $description=term_description();
  if(!$description)$description=sprintf('Últimas notícias de %s no Franklem News, com contexto, fontes transparentes e atualização diária.',single_term_title('',false));
 }elseif(is_search()){
  $description=sprintf('Resultados da busca por %s no Franklem News.',get_search_query());
 }else{
  $description=get_bloginfo('description');
 }
 $description=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags((string)$description,true)));
 return wp_html_excerpt($description,160,'…');
}

function franklem_canonical_url(){
 if(is_front_page())return home_url('/');
 if(is_singular())return get_permalink();
 if(is_category()||is_tag()||is_tax())return get_term_link(get_queried_object());
 return '';
}

function franklem_social_image(){
 if(is_singular()&&has_post_thumbnail())return get_the_post_thumbnail_url(null,'full');
 $site_icon=get_site_icon_url(1200);
 return $site_icon?:'';
}

function franklem_seo_meta(){
 if(is_admin()||is_feed()||franklem_has_seo_plugin())return;
 $description=franklem_meta_description();
 $canonical=franklem_canonical_url();
 $title=wp_get_document_title();
 $url=$canonical?:home_url(add_query_arg([],wp_unslash($_SERVER['REQUEST_URI']??'/')));
 $image=franklem_social_image();
 $type=is_single()?'article':'website';
 if($description)echo '<meta name="description" content="'.esc_attr($description).'">' . "\n";
 if(is_front_page()&&$canonical)echo '<link rel="canonical" href="'.esc_url($canonical).'">' . "\n";
 echo '<meta property="og:locale" content="'.esc_attr(get_locale()).'">' . "\n";
 echo '<meta property="og:type" content="'.esc_attr($type).'">' . "\n";
 echo '<meta property="og:site_name" content="'.esc_attr(get_bloginfo('name')).'">' . "\n";
 echo '<meta property="og:title" content="'.esc_attr($title).'">' . "\n";
 echo '<meta property="og:description" content="'.esc_attr($description).'">' . "\n";
 echo '<meta property="og:url" content="'.esc_url($url).'">' . "\n";
 if($image)echo '<meta property="og:image" content="'.esc_url($image).'">' . "\n";
 echo '<meta name="twitter:card" content="'.($image?'summary_large_image':'summary').'">' . "\n";
 echo '<meta name="twitter:title" content="'.esc_attr($title).'">' . "\n";
 echo '<meta name="twitter:description" content="'.esc_attr($description).'">' . "\n";
 if($image)echo '<meta name="twitter:image" content="'.esc_url($image).'">' . "\n";
 if(is_single()){
  echo '<meta property="article:published_time" content="'.esc_attr(get_the_date(DATE_W3C)).'">' . "\n";
  echo '<meta property="article:modified_time" content="'.esc_attr(get_the_modified_date(DATE_W3C)).'">' . "\n";
  $cat=franklem_first_category();if($cat)echo '<meta property="article:section" content="'.esc_attr($cat->name).'">' . "\n";
 }
}
add_action('wp_head','franklem_seo_meta',2);

function franklem_schema_images($post_id){
 $thumbnail_id=get_post_thumbnail_id($post_id);if(!$thumbnail_id)return [];
 $images=[];
 foreach(['franklem-schema-square','franklem-schema-4x3','franklem-schema-16x9','full'] as $size){
  $src=wp_get_attachment_image_src($thumbnail_id,$size);if($src&&!empty($src[0]))$images[]=$src[0];
 }
 return array_values(array_unique($images));
}

function franklem_schema(){
 if(!is_single()||franklem_has_seo_plugin())return;
 $cat=franklem_first_category();
 $author=get_post_meta(get_the_ID(),'franklem_byline',true)?:get_the_author();
 $about=get_page_by_path('sobre');
 $author_url=$about?get_permalink($about):get_author_posts_url((int)get_the_author_meta('ID'));
 $publisher=['@type'=>'Organization','name'=>get_bloginfo('name'),'url'=>home_url('/')];
 $logo=get_site_icon_url(512);if($logo)$publisher['logo']=['@type'=>'ImageObject','url'=>$logo];
 $data=[
  '@context'=>'https://schema.org','@type'=>'NewsArticle','headline'=>get_the_title(),
  'description'=>franklem_meta_description(),'datePublished'=>get_the_date(DATE_W3C),
  'dateModified'=>get_the_modified_date(DATE_W3C),'inLanguage'=>get_bloginfo('language'),
  'isAccessibleForFree'=>true,'url'=>get_permalink(),
  'author'=>['@type'=>'Person','name'=>$author,'url'=>$author_url],
  'publisher'=>$publisher,'mainEntityOfPage'=>['@type'=>'WebPage','@id'=>get_permalink()]
 ];
 if($cat)$data['articleSection']=$cat->name;
 $images=franklem_schema_images(get_the_ID());if($images)$data['image']=$images;
 echo '<script type="application/ld+json">'.wp_json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).'</script>' . "\n";
}
add_action('wp_head','franklem_schema',5);
