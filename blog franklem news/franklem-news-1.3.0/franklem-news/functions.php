<?php
if (!defined('ABSPATH')) exit;
function franklem_setup(){
 load_theme_textdomain('franklem-news',get_template_directory().'/languages');
 add_theme_support('title-tag'); add_theme_support('post-thumbnails'); add_theme_support('html5',['search-form','gallery','caption','style','script']); add_theme_support('responsive-embeds'); add_theme_support('editor-styles');
 register_nav_menus(['primary'=>'Menu principal','footer'=>'Menu do rodapé']);
 add_image_size('franklem-card',760,428,true);
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
function franklem_schema(){if(!is_single())return;$cat=franklem_first_category();$author=get_post_meta(get_the_ID(),'franklem_byline',true)?:get_the_author();$data=['@context'=>'https://schema.org','@type'=>'NewsArticle','headline'=>get_the_title(),'datePublished'=>get_the_date(DATE_W3C),'dateModified'=>get_the_modified_date(DATE_W3C),'author'=>['@type'=>'Person','name'=>$author],'publisher'=>['@type'=>'Organization','name'=>get_bloginfo('name')],'mainEntityOfPage'=>get_permalink()];if(has_post_thumbnail())$data['image']=[get_the_post_thumbnail_url(null,'full')];echo '<script type="application/ld+json">'.wp_json_encode($data,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).'</script>';}
add_action('wp_head','franklem_schema');
