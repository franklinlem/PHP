<?php
/**
 * Plugin Name: Franklem Editorial
 * Description: Coleta e organiza pautas jornalísticas em uma caixa editorial, sem republicar textos de terceiros.
 * Version: 1.5.8
 * Author: Franklem
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Text Domain: franklem-editorial
 */
if (!defined('ABSPATH')) exit;

define('FNE_VERSION','1.5.8');
define('FNE_OPTION','fne_settings');

function fne_defaults(){return [
 'enabled'=>0,'hour'=>7,'ai_enabled'=>0,'ai_hour'=>8,'max_per_feed'=>8,'retention_days'=>30,
 'blocked_terms'=>'promoção,oferta,desconto,cupom,queima de estoque,menor preço,preço no chão,compre agora,amazon prime,seleção de produtos,smart speakers em promoção,caixas de som em promoção,soundbars em promoção',
 'text_model'=>'gpt-5.6-luna','image_model'=>'gpt-image-1-mini','image_quality'=>'medium','daily_drafts'=>6,
 'feeds'=>[
  ['name'=>'Agência Brasil - Política','url'=>'https://agenciabrasil.ebc.com.br/rss/politica/feed.xml','section'=>'politica','active'=>1],
  ['name'=>'Agência Brasil - Geral','url'=>'https://agenciabrasil.ebc.com.br/rss/geral/feed.xml','section'=>'tecnologia-ciencia','active'=>0],
  ['name'=>'Agência Brasil - Esportes','url'=>'https://agenciabrasil.ebc.com.br/rss/esportes/feed.xml','section'=>'esporte','active'=>1],
  ['name'=>'Pesquisa FAPESP - Ciência','url'=>'https://revistapesquisa.fapesp.br/feed/','section'=>'tecnologia-ciencia','active'=>1],
  ['name'=>'Olhar Digital - Tecnologia','url'=>'https://olhardigital.com.br/feed/','section'=>'tecnologia-ciencia','active'=>1],
 ]
];}
function fne_settings(){return wp_parse_args((array)get_option(FNE_OPTION,[]),fne_defaults());}

function fne_upgrade_existing_ai_drafts($author_id){
 $ids=get_posts(['post_type'=>'post','post_status'=>['draft','pending','future','publish','private'],'numberposts'=>500,'fields'=>'ids','meta_query'=>[['key'=>'fne_origin_pauta','compare'=>'EXISTS']]]);
 foreach($ids as $id){
  $post=get_post($id);$content=(string)$post->post_content;
  $clean=preg_replace('#<p>Texto produzido com auxílio de IA.*?Revise todo o conteúdo antes de publicar\.</p>#su','',$content);
  $update=['ID'=>$id];
  if($clean!==$content)$update['post_content']=$clean;
  if((int)$post->post_author===0&&$author_id)$update['post_author']=$author_id;
  if(count($update)>1)wp_update_post($update);
  if(get_post_meta($id,'franklem_byline',true)!=='Franklin Martins')update_post_meta($id,'franklem_byline','Franklin Martins');
  if(!get_post_meta($id,'fne_created_at',true))update_post_meta($id,'fne_created_at',$post->post_date);
 }
}

function fne_reopen_reporting_pautas(){
 $ids=get_posts(['post_type'=>'fne_pauta','post_status'=>'draft','numberposts'=>500,'fields'=>'ids']);
 foreach($ids as $id){$content=(string)get_post_field('post_content',$id);$content=str_replace('confirme fatos, nomes e datas em pelo menos mais uma fonte','confira fatos, nomes e datas na fonte indicada',$content);if($content!==get_post_field('post_content',$id))wp_update_post(['ID'=>$id,'post_content'=>$content]);if(get_post_meta($id,'fne_status',true)==='requer_apuracao'){update_post_meta($id,'fne_status','nova');delete_post_meta($id,'fne_research_cache');delete_post_meta($id,'fne_research_cached_at');}}
}

function fne_register_post_type(){
 register_post_type('fne_pauta',['labels'=>['name'=>'Pautas','singular_name'=>'Pauta','menu_name'=>'Pautas','add_new_item'=>'Adicionar pauta','edit_item'=>'Revisar pauta','search_items'=>'Buscar pautas','not_found'=>'Nenhuma pauta encontrada'],'public'=>false,'show_ui'=>true,'show_in_menu'=>true,'menu_icon'=>'dashicons-media-document','supports'=>['title','editor','custom-fields'],'capability_type'=>'post','map_meta_cap'=>true]);
 register_taxonomy('fne_editoria','fne_pauta',['labels'=>['name'=>'Editorias','singular_name'=>'Editoria'],'public'=>false,'show_ui'=>true,'show_admin_column'=>true,'hierarchical'=>true]);
}
add_action('init','fne_register_post_type');

function fne_activate(){
 fne_register_post_type();
 foreach(['politica'=>'Política','tecnologia-ciencia'=>'Tecnologia & Ciência','esporte'=>'Esporte'] as $slug=>$name){if(!term_exists($slug,'fne_editoria'))wp_insert_term($name,'fne_editoria',['slug'=>$slug]);}
 if(!get_option(FNE_OPTION))add_option(FNE_OPTION,fne_defaults(),'',false);
 fne_reschedule(); flush_rewrite_rules();
}
register_activation_hook(__FILE__,'fne_activate');
function fne_upgrade(){
 if(get_option('fne_version')===FNE_VERSION)return;
 $s=fne_settings();$known=array_column((array)$s['feeds'],'url');
 foreach(fne_defaults()['feeds'] as $feed){if(!in_array($feed['url'],$known,true))$s['feeds'][]=$feed;}
 $s['ai_enabled']=isset($s['ai_enabled'])?(int)$s['ai_enabled']:0;$s['ai_hour']=isset($s['ai_hour'])?max(0,min(23,(int)$s['ai_hour'])):8;$s['daily_drafts']=max(1,min(20,(int)($s['daily_drafts']??6)));
 if(empty($s['author_id'])){$admins=get_users(['role'=>'administrator','number'=>1,'fields'=>'ID','orderby'=>'ID','order'=>'ASC']);$s['author_id']=(int)($admins[0]??0);}
 update_option(FNE_OPTION,$s,false);update_option('fne_version',FNE_VERSION,false);
 fne_upgrade_existing_ai_drafts((int)$s['author_id']);
 fne_reopen_reporting_pautas();
 fne_reschedule();
}
add_action('admin_init','fne_upgrade',5);
function fne_deactivate(){wp_clear_scheduled_hook('fne_daily_collection');wp_clear_scheduled_hook('fne_daily_generation');wp_clear_scheduled_hook('fne_process_ai_queue');delete_option('fne_ai_queue');delete_transient('fne_ai_worker_lock');flush_rewrite_rules();}
register_deactivation_hook(__FILE__,'fne_deactivate');

function fne_next_timestamp($hour){$now=current_datetime();$next=$now->setTime(max(0,min(23,(int)$hour)),0);if($next<=$now)$next=$next->modify('+1 day');return $next->getTimestamp();}
function fne_reschedule(){wp_clear_scheduled_hook('fne_daily_collection');wp_clear_scheduled_hook('fne_daily_generation');$s=fne_settings();if(!empty($s['enabled']))wp_schedule_event(fne_next_timestamp($s['hour']),'daily','fne_daily_collection');if(!empty($s['ai_enabled']))wp_schedule_event(fne_next_timestamp($s['ai_hour']),'daily','fne_daily_generation');}
add_action('fne_daily_collection','fne_collect_all');

function fne_normalize_url($url){$parts=wp_parse_url($url);if(!$parts||empty($parts['host']))return esc_url_raw($url);$path=isset($parts['path'])?rtrim($parts['path'],'/'):'/';return strtolower(($parts['scheme']??'https').'://'.$parts['host'].$path);}
function fne_existing($url){$q=new WP_Query(['post_type'=>'fne_pauta','post_status'=>'any','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'fne_source_hash','meta_value'=>hash('sha256',fne_normalize_url($url)),'no_found_rows'=>true]);return !empty($q->posts);}
function fne_clean_summary($html){$text=wp_strip_all_tags((string)$html,true);$text=preg_replace('/\s+/u',' ',trim($text));return wp_trim_words($text,55,'…');}
function fne_contains_blocked_term($title,$summary){$s=fne_settings();$hay=remove_accents(mb_strtolower($title.' '.$summary));foreach(array_filter(array_map('trim',explode(',',(string)$s['blocked_terms']))) as $term){$term=remove_accents(mb_strtolower($term));if($term!==''&&str_contains($hay,$term))return $term;}return false;}
function fne_relevance_score($title,$summary,$section){$hay=' '.remove_accents(mb_strtolower($title.' '.$summary)).' ';$score=50;if($section==='tecnologia-ciencia'){foreach(['inteligencia artificial',' ia ','ciencia','cientista','pesquisa','estudo','espaco','astronomia','seguranca','ciber','software','internet','tecnologia','inovacao','robot','comput','energia','saude','biologia','clima'] as $term){if(str_contains($hay,$term))$score+=5;}foreach(['jogo','xbox','playstation','filme','serie','streaming'] as $term){if(str_contains($hay,$term))$score-=12;}}return max(0,min(100,$score));}
function fne_collect_feed($feed,$limit){
 if(empty($feed['active'])||empty($feed['url']))return ['created'=>0,'skipped'=>0,'errors'=>[]];
 require_once ABSPATH.WPINC.'/feed.php';
 $rss=fetch_feed(esc_url_raw($feed['url']));
 if(is_wp_error($rss))return ['created'=>0,'skipped'=>0,'errors'=>[$feed['name'].': '.$rss->get_error_message()]];
 $items=$rss->get_items(0,min(30,max(1,(int)$limit)*3));$created=0;$skipped=0;
 foreach($items as $item){$url=esc_url_raw($item->get_permalink());if(!$url||fne_existing($url)){$skipped++;continue;}
  $title=sanitize_text_field(wp_strip_all_tags($item->get_title()));if(!$title){$skipped++;continue;}
  $summary=fne_clean_summary($item->get_description());if(fne_contains_blocked_term($title,$summary)){$skipped++;continue;}if($created>=$limit)break;$date=$item->get_date('Y-m-d H:i:s');$score=fne_relevance_score($title,$summary,$feed['section']);
  $content="<p><strong>Resumo fornecido pela fonte:</strong></p><p>".esc_html($summary)."</p><p><strong>Antes de produzir:</strong> confira fatos, nomes e datas na fonte indicada. Não copie o texto original.</p>";
  $id=wp_insert_post(['post_type'=>'fne_pauta','post_status'=>'draft','post_title'=>$title,'post_content'=>$content,'post_date'=>$date?:current_time('mysql')],true);
  if(is_wp_error($id))continue;
  wp_set_object_terms($id,sanitize_key($feed['section']),'fne_editoria');
  update_post_meta($id,'fne_source_name',sanitize_text_field($feed['name']));update_post_meta($id,'fne_source_url',$url);update_post_meta($id,'fne_source_hash',hash('sha256',fne_normalize_url($url)));update_post_meta($id,'fne_collected_at',current_time('mysql'));update_post_meta($id,'fne_source_date',$date?:current_time('mysql'));update_post_meta($id,'fne_relevance',$score);update_post_meta($id,'fne_status','nova');$created++;
 }
 return compact('created','skipped')+['errors'=>[]];
}
function fne_collect_all(){
 $s=fne_settings();$total=['created'=>0,'skipped'=>0,'errors'=>[]];
 foreach((array)$s['feeds'] as $feed){$r=fne_collect_feed($feed,$s['max_per_feed']);$total['created']+=$r['created'];$total['skipped']+=$r['skipped'];$total['errors']=array_merge($total['errors'],$r['errors']);}
 fne_cleanup((int)$s['retention_days']);update_option('fne_last_run',['time'=>current_time('mysql'),'result'=>$total],false);return $total;
}
function fne_cleanup($days){$ids=get_posts(['post_type'=>'fne_pauta','post_status'=>'draft','numberposts'=>100,'fields'=>'ids','date_query'=>[['before'=>max(7,$days).' days ago']]]);foreach($ids as $id)wp_trash_post($id);}

function fne_meta_boxes(){add_meta_box('fne-source','Origem e verificação','fne_source_box','fne_pauta','side','high');}
add_action('add_meta_boxes','fne_meta_boxes');
function fne_source_box($post){$url=get_post_meta($post->ID,'fne_source_url',true);$name=get_post_meta($post->ID,'fne_source_name',true);$at=get_post_meta($post->ID,'fne_collected_at',true);echo '<p><strong>Fonte:</strong><br>'.esc_html($name).'</p><p><a href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer">Abrir matéria original ↗</a></p><p><strong>Coletada:</strong><br>'.esc_html($at).'</p><hr><p>O rascunho será limitado aos fatos sustentados pela fonte indicada e deverá ser revisado antes da publicação.</p>';}

function fne_secret_key(){return hash('sha256',(defined('AUTH_KEY')?AUTH_KEY:NONCE_SALT).'|franklem-editorial',true);}
function fne_encrypt($plain){if(!$plain||!function_exists('openssl_encrypt'))return '';$iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($plain,'aes-256-gcm',fne_secret_key(),OPENSSL_RAW_DATA,$iv,$tag,'fne');return base64_encode($iv.$tag.$cipher);}
function fne_decrypt($packed){if(!$packed||!function_exists('openssl_decrypt'))return '';$raw=base64_decode($packed,true);if($raw===false||strlen($raw)<29)return '';$iv=substr($raw,0,12);$tag=substr($raw,12,16);return (string)openssl_decrypt(substr($raw,28),'aes-256-gcm',fne_secret_key(),OPENSSL_RAW_DATA,$iv,$tag,'fne');}
function fne_api_key(){if(defined('FRANKLEM_OPENAI_API_KEY')&&FRANKLEM_OPENAI_API_KEY)return FRANKLEM_OPENAI_API_KEY;return fne_decrypt((string)get_option('fne_openai_key',''));}
function fne_api_request($path,$body,$timeout=90){$key=fne_api_key();if(!$key)return new WP_Error('fne_no_key','A chave da OpenAI ainda não foi configurada.');$r=wp_remote_post('https://api.openai.com/v1/'.$path,['timeout'=>$timeout,'headers'=>['Authorization'=>'Bearer '.$key,'Content-Type'=>'application/json'],'body'=>wp_json_encode($body)]);if(is_wp_error($r))return $r;$code=wp_remote_retrieve_response_code($r);$json=json_decode(wp_remote_retrieve_body($r),true);if($code<200||$code>=300)return new WP_Error('fne_api_error',sanitize_text_field($json['error']['message']??('Erro HTTP '.$code)));return $json;}
function fne_test_connection(){if(!fne_api_key())return new WP_Error('fne_no_key','Cadastre a chave antes de testar.');$s=fne_settings();foreach([$s['text_model'],$s['image_model']] as $model){$r=wp_remote_get('https://api.openai.com/v1/models/'.rawurlencode($model),['timeout'=>30,'headers'=>['Authorization'=>'Bearer '.fne_api_key()]]);if(is_wp_error($r))return $r;if(wp_remote_retrieve_response_code($r)!==200){$j=json_decode(wp_remote_retrieve_body($r),true);return new WP_Error('fne_model',sanitize_text_field($j['error']['message']??('O modelo '.$model.' não está acessível.')));}}return true;}
function fne_response_text($json){if(isset($json['output_text']))return (string)$json['output_text'];foreach((array)($json['output']??[]) as $out)foreach((array)($out['content']??[]) as $c)if(isset($c['text']))return (string)$c['text'];return '';}
function fne_json_from_text($text){$text=trim($text);$text=preg_replace('/^```(?:json)?\s*|\s*```$/u','',$text);$data=json_decode($text,true);return is_array($data)?$data:new WP_Error('fne_bad_json','A IA não devolveu o formato editorial esperado.');}
function fne_text_call($instructions,$input){$s=fne_settings();$r=fne_api_request('responses',['model'=>$s['text_model'],'instructions'=>$instructions,'input'=>$input,'max_output_tokens'=>2600]);if(is_wp_error($r))return $r;return fne_json_from_text(fne_response_text($r));}
function fne_is_sensitive($id){$hay=remove_accents(mb_strtolower(get_the_title($id).' '.wp_strip_all_tags(get_post_field('post_content',$id))));foreach(['acusacao','acusado','crime','prisao','investigacao','denuncia','saude','hospital','internado','avc','morte','morre','doenca','medicamento'] as $term)if(str_contains($hay,$term))return true;return false;}
function fne_single_source_research($id,$warning=''){ $url=esc_url_raw(get_post_meta($id,'fne_source_url',true));$name=sanitize_text_field(get_post_meta($id,'fne_source_name',true));if(!$url)return new WP_Error('fne_no_primary_source','Etapa de pesquisa: a pauta não possui uma fonte primária válida.');$data=['verified'=>true,'facts'=>[wp_strip_all_tags(get_post_field('post_content',$id))],'context'=>[],'sources'=>[['name'=>$name?:wp_parse_url($url,PHP_URL_HOST),'url'=>$url]],'warnings'=>array_filter([$warning?:'Rascunho baseado em uma única fonte; exige revisão manual antes da publicação.'])];update_post_meta($id,'fne_research_cache',wp_json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));update_post_meta($id,'fne_research_cached_at',time());return $data;}
function fne_research_call_legacy($id){$cached=json_decode((string)get_post_meta($id,'fne_research_cache',true),true);$cached_at=(int)get_post_meta($id,'fne_research_cached_at',true);if(is_array($cached)&&$cached_at>(time()-6*HOUR_IN_SECONDS))return $cached;$s=fne_settings();$original_url=esc_url_raw(get_post_meta($id,'fne_source_url',true));$original_name=sanitize_text_field(get_post_meta($id,'fne_source_name',true));$needed=1;$prompt=fne_article_prompt($id)."\n\nPesquise este assunto na web. A fonte primária indicada na pauta conta como uma fonte. Procure fontes complementares quando disponíveis, mas não recuse o rascunho se apenas a fonte primária confiável sustentar os fatos. Nesse caso, limite o texto estritamente ao que essa fonte informa. Prefira documentos oficiais e cobertura jornalística original. Evite agregadores, páginas comerciais e republicações. Responda APENAS JSON válido: {\"verified\":true,\"facts\":[\"\"],\"context\":[\"\"],\"sources\":[{\"name\":\"\",\"url\":\"https://...\"}],\"warnings\":[\"\"]}. Inclua a fonte primária na lista. Não inclua fatos sem apoio.";$r=fne_api_request('responses',['model'=>$s['text_model'],'tools'=>[['type'=>'web_search']],'tool_choice'=>'auto','input'=>$prompt,'max_output_tokens'=>1800],120);if(is_wp_error($r))return fne_single_source_research($id,'A busca complementar falhou; rascunho baseado somente na fonte primária.');$data=fne_json_from_text(fne_response_text($r));if(is_wp_error($data))return fne_single_source_research($id,'A busca complementar não retornou dados utilizáveis; rascunho baseado somente na fonte primária.');$clean=[];$seen=[];if($original_url){$host=wp_parse_url($original_url,PHP_URL_HOST);$clean[]=['name'=>$original_name?:$host,'url'=>$original_url];$seen[fne_normalize_url($original_url)]=true;}foreach((array)($data['sources']??[]) as $source){$url=esc_url_raw($source['url']??'');$norm=fne_normalize_url($url);if(!$url||isset($seen[$norm]))continue;$seen[$norm]=true;$clean[]=['name'=>sanitize_text_field($source['name']??wp_parse_url($url,PHP_URL_HOST)),'url'=>$url];}$hosts=array_unique(array_filter(array_map(fn($x)=>strtolower((string)wp_parse_url($x['url'],PHP_URL_HOST)),$clean)));if(count($hosts)<$needed){update_post_meta($id,'fne_status','requer_apuracao');return new WP_Error('fne_reporting','Etapa de pesquisa: foram confirmados '.count($hosts).' de '.$needed.' domínios confiáveis necessários.');}$data['sources']=$clean;update_post_meta($id,'fne_research_cache',wp_json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));update_post_meta($id,'fne_research_cached_at',time());return $data;}
function fne_research_call($id){delete_post_meta($id,'fne_research_cache');delete_post_meta($id,'fne_research_cached_at');return fne_single_source_research($id,'Versão 1.5.5: rascunho produzido somente com a fonte primária, sem busca web obrigatória.');}
function fne_editoria_for_pauta($id){$terms=wp_get_object_terms($id,'fne_editoria');return (!is_wp_error($terms)&&$terms)?$terms[0]->slug:'politica';}
function fne_article_prompt($id){$source=get_post_meta($id,'fne_source_url',true);$summary=wp_strip_all_tags(get_post_field('post_content',$id));$section=fne_editoria_for_pauta($id);return "EDITORIA: {$section}\nTÍTULO DA PAUTA: ".get_the_title($id)."\nRESUMO RSS: {$summary}\nFONTE PRIMÁRIA: {$source}\n\nProduza somente a partir destes dados. Uma única fonte confiável é suficiente para criar o rascunho, desde que o texto se limite estritamente aos fatos sustentados por ela. Defina requires_reporting=false nesse caso. Só marque requires_reporting=true se nem a fonte primária sustentar uma notícia factual mínima. Não invente declarações, números, datas ou contexto específico.";}
function fne_generate_article_data($id){
 $schema='Responda APENAS JSON válido: {"title":"","subtitle":"","body_html":"","image_prompt":"","requires_reporting":false,"warnings":[]}. body_html deve usar apenas <p>, <h2>, <ul>, <li>, <strong>. Escreva em português do Brasil, entre 250 e 700 palavras, usando um texto mais curto quando houver apenas uma fonte ou poucos fatos confirmados, com tom jornalístico claro e sem copiar o título literalmente. Não use citações não fornecidas. Inclua contexto apenas quando for conhecimento geral estável e sinalize incerteza.';
 $research=fne_research_call($id);if(is_wp_error($research))return $research;$evidence="\n\nAPURAÇÃO CONFIRMADA:\n".wp_json_encode($research,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
 $draft=fne_text_call('Você é redator do Franklem News. A apuração abaixo já passou pelo número mínimo de fontes. Use exclusivamente os fatos confirmados. Defina requires_reporting=false, exceto se houver contradição explícita entre as fontes que impeça uma notícia factual. '.$schema,fne_article_prompt($id).$evidence);if(is_wp_error($draft))return new WP_Error('fne_draft_error','Etapa de redação: '.$draft->get_error_message());if(!empty($draft['requires_reporting'])){update_post_meta($id,'fne_status','requer_apuracao');return new WP_Error('fne_draft_reporting','Etapa de redação: as fontes apresentaram contradição ou lacuna material.');}
 $review=fne_text_call('Você é editor-chefe. A apuração já atingiu o mínimo de fontes. Revise rigorosamente o rascunho, removendo alegações sem apoio, linguagem promocional, sensacionalismo e repetições. Defina requires_reporting=false se for possível publicar um texto factual apenas com o material confirmado. '.$schema,fne_article_prompt($id).$evidence."\n\nRASCUNHO PARA REVISAR:\n".wp_json_encode($draft,JSON_UNESCAPED_UNICODE));if(is_wp_error($review))return new WP_Error('fne_review_error','Etapa de revisão: '.$review->get_error_message());if(!empty($review['requires_reporting'])){update_post_meta($id,'fne_status','requer_apuracao');return new WP_Error('fne_review_reporting','Etapa de revisão: restou uma lacuna material que impede o rascunho.');}$review['_sources']=$research['sources'];$review['_research_warnings']=$research['warnings']??[];return $review;
}
function fne_generate_image($prompt,$title){$s=fne_settings();$safe='Crie uma imagem editorial jornalística horizontal. REGRA PRINCIPAL: não inclua texto, palavras, siglas, letras, números, legendas, placas, documentos legíveis, interfaces, logotipos nem marcas d’água. Represente conceitos por símbolos e composição visual sem tipografia. Nunca escreva AI; o termo correto em português é IA. Se algum texto for absolutamente inevitável, use exclusivamente português do Brasil (pt-BR), com ortografia correta e acentuação, mas prefira sempre não incluir texto. Evite rostos identificáveis quando não forem necessários. '.$prompt;$r=fne_api_request('images/generations',['model'=>$s['image_model'],'prompt'=>$safe,'size'=>'1536x1024','quality'=>$s['image_quality'],'n'=>1],180);if(is_wp_error($r))return $r;$b64=$r['data'][0]['b64_json']??'';if(!$b64)return new WP_Error('fne_no_image','A API não devolveu a imagem.');$bytes=base64_decode($b64,true);if($bytes===false||strlen($bytes)>15*1024*1024)return new WP_Error('fne_bad_image','A imagem recebida é inválida ou muito grande.');$upload=wp_upload_bits(sanitize_file_name(sanitize_title($title).'-ia.png'),null,$bytes);if(!empty($upload['error']))return new WP_Error('fne_upload',$upload['error']);$type=wp_check_filetype($upload['file']);$aid=wp_insert_attachment(['post_mime_type'=>$type['type']?:'image/png','post_title'=>$title.' — imagem ilustrativa','post_content'=>'Imagem ilustrativa gerada por inteligência artificial.','post_status'=>'inherit'],$upload['file']);if(is_wp_error($aid))return $aid;require_once ABSPATH.'wp-admin/includes/image.php';wp_update_attachment_metadata($aid,wp_generate_attachment_metadata($aid,$upload['file']));update_post_meta($aid,'_wp_attachment_image_alt','Imagem ilustrativa gerada por IA para: '.$title);return $aid;}
function fne_daily_count(){return (int)get_option('fne_ai_count_'.wp_date('Y-m-d'),0);}
function fne_create_ai_draft($pauta_id){$s=fne_settings();if(fne_daily_count()>=(int)$s['daily_drafts'])return new WP_Error('fne_daily_limit','O limite diário local de rascunhos foi atingido.');if(get_post_type($pauta_id)!=='fne_pauta')return new WP_Error('fne_invalid','Pauta inválida.');$data=fne_generate_article_data($pauta_id);if(is_wp_error($data))return $data;foreach(['title','subtitle','body_html','image_prompt'] as $field)if(empty($data[$field]))return new WP_Error('fne_incomplete','A revisão retornou um rascunho incompleto.');$section=fne_editoria_for_pauta($pauta_id);$cat=get_category_by_slug($section);$sources='';foreach((array)$data['_sources'] as $source)$sources.='<li><a href="'.esc_url($source['url']).'" target="_blank" rel="noopener noreferrer">'.esc_html($source['name']).'</a></li>';$content=wp_kses_post($data['body_html']).'<div class="sources-box"><strong>Fontes e transparência</strong><ul>'.$sources.'</ul></div>';$post_id=wp_insert_post(['post_type'=>'post','post_status'=>'draft','post_author'=>(int)($s['author_id']??0),'post_title'=>sanitize_text_field($data['title']),'post_content'=>$content,'post_category'=>$cat?[$cat->term_id]:[]],true);if(is_wp_error($post_id))return $post_id;update_post_meta($post_id,'franklem_subtitle',sanitize_textarea_field($data['subtitle']));update_post_meta($post_id,'franklem_byline','Franklin Martins');update_post_meta($post_id,'fne_origin_pauta',$pauta_id);update_post_meta($post_id,'fne_ai_reviewed',current_time('mysql'));update_post_meta($post_id,'fne_created_at',get_post_field('post_date',$post_id));update_post_meta($post_id,'fne_research_sources',wp_json_encode($data['_sources'],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));$image=fne_generate_image(sanitize_text_field($data['image_prompt']),sanitize_text_field($data['title']));if(is_wp_error($image)){wp_trash_post($post_id);return $image;}set_post_thumbnail($post_id,$image);update_post_meta($pauta_id,'fne_status','rascunho_criado');update_post_meta($pauta_id,'fne_generated_post',$post_id);update_option('fne_ai_count_'.wp_date('Y-m-d'),fne_daily_count()+1,false);return $post_id;}

function fne_pick_pauta($section,$exclude=[]){
 $ids=get_posts(['post_type'=>'fne_pauta','post_status'=>'draft','numberposts'=>40,'fields'=>'ids','tax_query'=>[['taxonomy'=>'fne_editoria','field'=>'slug','terms'=>$section]],'meta_query'=>[['key'=>'fne_status','value'=>'nova']],'date_query'=>[['after'=>'3 days ago']]]);
 $eligible=[];foreach($ids as $id){if(in_array((int)$id,array_map('intval',(array)$exclude),true)||get_post_meta($id,'fne_generated_post',true))continue;$text=get_the_title($id).' '.wp_strip_all_tags(get_post_field('post_content',$id));if(fne_contains_blocked_term($text,''))continue;$eligible[]=['id'=>$id,'score'=>(int)get_post_meta($id,'fne_relevance',true),'date'=>strtotime((string)get_post_meta($id,'fne_source_date',true))?:get_post_time('U',true,$id)];}
 usort($eligible,fn($a,$b)=>$b['score']<=>$a['score']?:$b['date']<=>$a['date']);return $eligible[0]['id']??0;
}
function fne_new_ai_report(){return ['date'=>wp_date('Y-m-d'),'started'=>current_time('mysql'),'finished'=>'','status'=>'em andamento','selected'=>[],'generated'=>[],'refused'=>[],'estimated_usd'=>0.0];}
function fne_build_ai_queue(){
 $s=fne_settings();if(empty($s['ai_enabled'])||!fne_api_key()||fne_daily_count()>=(int)$s['daily_drafts'])return;
 $report=fne_new_ai_report();$queue=[];$labels=['politica'=>'Política','tecnologia-ciencia'=>'Tecnologia & Ciência','esporte'=>'Esporte'];
 $rounds=(int)ceil((int)$s['daily_drafts']/count($labels));for($round=1;$round<=$rounds;$round++){foreach($labels as $section=>$label){if(count($queue)>=(int)$s['daily_drafts'])break 2;$id=fne_pick_pauta($section,array_column($queue,'pauta'));if(!$id){$report['refused'][]=['section'=>$label,'pauta'=>0,'message'=>$round===1?'Nenhuma pauta nova e elegível encontrada.':'Nenhuma pauta adicional nova e elegível encontrada.'];continue;}$queue[]=['pauta'=>$id,'section'=>$label];$report['selected'][]=['section'=>$label,'pauta'=>$id,'title'=>get_the_title($id)];}}
 if(!$queue){$report['status']='concluído';$report['finished']=current_time('mysql');update_option('fne_last_ai_report',$report,false);return;}
 update_option('fne_ai_queue',$queue,false);update_option('fne_last_ai_report',$report,false);if(!wp_next_scheduled('fne_process_ai_queue'))wp_schedule_single_event(time()+10,'fne_process_ai_queue');
}
add_action('fne_daily_generation','fne_build_ai_queue');
function fne_process_ai_queue(){
 if(get_transient('fne_ai_worker_lock'))return;set_transient('fne_ai_worker_lock',1,10*MINUTE_IN_SECONDS);$queue=(array)get_option('fne_ai_queue',[]);$report=(array)get_option('fne_last_ai_report',fne_new_ai_report());$item=array_shift($queue);
 if($item){$result=fne_create_ai_draft((int)$item['pauta']);if(is_wp_error($result)){$report['refused'][]=['section'=>$item['section'],'pauta'=>(int)$item['pauta'],'message'=>$result->get_error_message()];$report['estimated_usd']+=0.011;}else{$report['generated'][]=['section'=>$item['section'],'pauta'=>(int)$item['pauta'],'post'=>(int)$result,'title'=>get_the_title($result)];$report['estimated_usd']+=0.027;}}
 update_option('fne_ai_queue',$queue,false);if($queue&&fne_daily_count()<(int)fne_settings()['daily_drafts']){update_option('fne_last_ai_report',$report,false);wp_schedule_single_event(time()+120,'fne_process_ai_queue');}else{$report['status']='concluído';$report['finished']=current_time('mysql');$report['estimated_usd']=round($report['estimated_usd'],3);update_option('fne_last_ai_report',$report,false);delete_option('fne_ai_queue');}delete_transient('fne_ai_worker_lock');
}
add_action('fne_process_ai_queue','fne_process_ai_queue');

function fne_cleanup_promos_action(){if(!current_user_can('manage_options'))wp_die('Sem permissão.');check_admin_referer('fne_cleanup_promos');$ids=get_posts(['post_type'=>'fne_pauta','post_status'=>'draft','numberposts'=>500,'fields'=>'ids']);$count=0;foreach($ids as $id){if(fne_contains_blocked_term(get_the_title($id),wp_strip_all_tags(get_post_field('post_content',$id)))){wp_trash_post($id);$count++;}}wp_safe_redirect(add_query_arg('fne_cleaned',$count,admin_url('edit.php?post_type=fne_pauta&page=fne-settings')));exit;}
add_action('admin_post_fne_cleanup_promos','fne_cleanup_promos_action');

function fne_ai_box(){add_meta_box('fne-ai','Criar notícia com IA','fne_ai_box_html','fne_pauta','side','high');}add_action('add_meta_boxes','fne_ai_box');
function fne_ai_box_html($post){
 $generated=(int)get_post_meta($post->ID,'fne_generated_post',true);
 if($generated&&get_post($generated)){
  echo '<p><strong>Rascunho já criado.</strong></p><p><a class="button" href="'.esc_url(get_edit_post_link($generated)).'">Abrir notícia</a></p>';
  return;
 }
 $settings=fne_settings();
 $settings_url=admin_url('edit.php?post_type=fne_pauta&page=fne-settings');
 echo '<p>Gera texto, executa revisão separada, cria imagem e salva uma notícia como rascunho.</p>';
 echo '<p><strong>Uso hoje:</strong> '.esc_html(fne_daily_count()).'/'.esc_html($settings['daily_drafts']).'<br><a href="'.esc_url($settings_url).'">Alterar limite diário</a></p>';
 echo '<p><a class="button button-primary" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=fne_generate&pauta='.$post->ID),'fne_generate_'.$post->ID)).'">Gerar rascunho com IA</a></p>';
 echo '<p><small>Esta operação usa créditos da OpenAI e pode levar até dois minutos.</small></p>';
}
function fne_generate_action(){if(!current_user_can('edit_posts'))wp_die('Sem permissão.');$id=absint($_GET['pauta']??0);check_admin_referer('fne_generate_'.$id);$result=fne_create_ai_draft($id);if(is_wp_error($result)){wp_safe_redirect(add_query_arg(['post'=>$id,'action'=>'edit','fne_error'=>rawurlencode($result->get_error_message())],admin_url('post.php')));exit;}wp_safe_redirect(get_edit_post_link($result,'url'));exit;}add_action('admin_post_fne_generate','fne_generate_action');
function fne_save_key_action(){if(!current_user_can('manage_options'))wp_die('Sem permissão.');check_admin_referer('fne_save_key');$key=trim(sanitize_text_field(wp_unslash($_POST['openai_key']??'')));if(!empty($_POST['remove_key']))delete_option('fne_openai_key');elseif($key!==''){if(!str_starts_with($key,'sk-'))wp_die('A chave não parece válida. Use a chave secreta do projeto.');update_option('fne_openai_key',fne_encrypt($key),false);}wp_safe_redirect(add_query_arg('fne_key_saved','1',admin_url('edit.php?post_type=fne_pauta&page=fne-settings')));exit;}add_action('admin_post_fne_save_key','fne_save_key_action');
function fne_test_key_action(){if(!current_user_can('manage_options'))wp_die('Sem permissão.');check_admin_referer('fne_test_key');$r=fne_test_connection();$args=is_wp_error($r)?['fne_test'=>'error','fne_message'=>rawurlencode($r->get_error_message())]:['fne_test'=>'ok'];wp_safe_redirect(add_query_arg($args,admin_url('edit.php?post_type=fne_pauta&page=fne-settings')));exit;}add_action('admin_post_fne_test_key','fne_test_key_action');

function fne_news_columns($cols){
 $new=[];
 foreach($cols as $key=>$label){
  if($key==='date'||$key==='tags')continue;
  $new[$key]=$label;
  if($key==='title')$new['fne_workflow_status']='Situação';
 }
 $new['fne_created']='Criado em';
 $new['fne_published']='Publicado em';
 return $new;
}
add_filter('manage_post_posts_columns','fne_news_columns');

function fne_news_column($column,$post_id){
 $post=get_post($post_id);if(!$post)return;
 if($column==='fne_workflow_status'){
  $labels=['draft'=>'Rascunho','publish'=>'Publicado','pending'=>'Pendente','future'=>'Agendado','private'=>'Privado'];
  $colors=['draft'=>'#996800','publish'=>'#087b35','pending'=>'#8a4b08','future'=>'#135e96','private'=>'#50575e'];
  $status=$post->post_status;$label=$labels[$status]??ucfirst($status);$color=$colors[$status]??'#50575e';
  echo '<strong style="display:inline-block;padding:3px 8px;border:1px solid '.esc_attr($color).';border-radius:12px;color:'.esc_attr($color).'">'.esc_html($label).'</strong>';
  if(get_post_meta($post_id,'fne_origin_pauta',true))echo '<br><small>Gerado pela IA</small>';
 }
 if($column==='fne_created'){
  $created=get_post_meta($post_id,'fne_created_at',true)?:$post->post_date;
  echo esc_html(mysql2date('d/m/Y \à\s H:i',$created));
 }
 if($column==='fne_published'){
  echo $post->post_status==='publish'?esc_html(mysql2date('d/m/Y \à\s H:i',$post->post_date)):'—';
 }
}
add_action('manage_post_posts_custom_column','fne_news_column',10,2);

function fne_news_status_filter($post_type,$which){
 if($post_type!=='post'||$which!=='top')return;
 $selected=isset($_GET['fne_workflow'])?sanitize_key(wp_unslash($_GET['fne_workflow'])):'';
 echo '<select name="fne_workflow" aria-label="Situação da notícia">';
 echo '<option value="">Todas as situações</option>';
 foreach(['draft'=>'Somente rascunhos','publish'=>'Somente publicados','pending'=>'Pendentes','future'=>'Agendados'] as $value=>$label)echo '<option value="'.esc_attr($value).'" '.selected($selected,$value,false).'>'.esc_html($label).'</option>';
 echo '</select>';
}
add_action('restrict_manage_posts','fne_news_status_filter',10,2);

function fne_apply_news_status_filter($query){
 if(!is_admin()||!$query->is_main_query()||$query->get('post_type')!=='post')return;
 $status=isset($_GET['fne_workflow'])?sanitize_key(wp_unslash($_GET['fne_workflow'])):'';
 if(in_array($status,['draft','publish','pending','future'],true))$query->set('post_status',$status);
 if(!$query->get('orderby')){$query->set('orderby','date');$query->set('order','DESC');}
}
add_action('pre_get_posts','fne_apply_news_status_filter');

function fne_columns($cols){return ['cb'=>$cols['cb'],'title'=>'Título','fne_section'=>'Editoria','fne_relevance'=>'Relevância','fne_source'=>'Fonte','fne_source_date'=>'Data da fonte'];}
add_filter('manage_fne_pauta_posts_columns','fne_columns');
function fne_column($col,$id){if($col==='fne_section'){echo wp_kses_post(get_the_term_list($id,'fne_editoria','',''));}if($col==='fne_relevance'){$score=(int)get_post_meta($id,'fne_relevance',true);echo $score?'<strong>'.esc_html($score).'/100</strong>':'—';}if($col==='fne_source'){$url=get_post_meta($id,'fne_source_url',true);echo '<a href="'.esc_url($url).'" target="_blank" rel="noopener">'.esc_html(get_post_meta($id,'fne_source_name',true)).' ↗</a>';}if($col==='fne_source_date'){$date=get_post_meta($id,'fne_source_date',true);echo esc_html($date?wp_date('d/m/Y \à\s H:i',strtotime($date)):get_the_date('d/m/Y \à\s H:i',$id));}}
add_action('manage_fne_pauta_posts_custom_column','fne_column',10,2);

function fne_pauta_filters($post_type,$which){
 if($post_type!=='fne_pauta'||$which!=='top')return;
 $section=isset($_GET['fne_editoria_filter'])?sanitize_key(wp_unslash($_GET['fne_editoria_filter'])):'';
 wp_dropdown_categories(['show_option_all'=>'Todas as editorias','taxonomy'=>'fne_editoria','name'=>'fne_editoria_filter','orderby'=>'name','selected'=>$section,'hierarchical'=>true,'hide_empty'=>false,'value_field'=>'slug']);
 $day=isset($_GET['fne_source_day'])?sanitize_text_field(wp_unslash($_GET['fne_source_day'])):'';
 echo '<label for="fne-source-day" style="margin-left:6px;line-height:30px">Data da notícia:</label> <input id="fne-source-day" type="date" name="fne_source_day" value="'.esc_attr($day).'" aria-label="Data da notícia" title="Data da notícia">';
}
add_action('restrict_manage_posts','fne_pauta_filters',10,2);

function fne_apply_pauta_filters($query){
 if(!is_admin()||!$query->is_main_query()||$query->get('post_type')!=='fne_pauta')return;
 $section=isset($_GET['fne_editoria_filter'])?sanitize_key(wp_unslash($_GET['fne_editoria_filter'])):'';
 if(in_array($section,['politica','tecnologia-ciencia','esporte'],true))$query->set('tax_query',[['taxonomy'=>'fne_editoria','field'=>'slug','terms'=>$section]]);
 $day=isset($_GET['fne_source_day'])?sanitize_text_field(wp_unslash($_GET['fne_source_day'])):'';
 if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$day,$m)&&checkdate((int)$m[2],(int)$m[3],(int)$m[1]))$query->set('meta_query',[['key'=>'fne_source_date','value'=>[$day.' 00:00:00',$day.' 23:59:59'],'compare'=>'BETWEEN','type'=>'DATETIME']]);
}
add_action('pre_get_posts','fne_apply_pauta_filters');

function fne_admin_menu(){add_submenu_page('edit.php?post_type=fne_pauta','Configurações editoriais','Configurações','manage_options','fne-settings','fne_settings_page');}
add_action('admin_menu','fne_admin_menu');
function fne_admin_init(){register_setting('fne_group',FNE_OPTION,['sanitize_callback'=>'fne_sanitize']);}
add_action('admin_init','fne_admin_init');
function fne_sanitize($input){$old=fne_settings();$out=$old;$out['enabled']=empty($input['enabled'])?0:1;$out['hour']=max(0,min(23,(int)($input['hour']??7)));$out['ai_enabled']=empty($input['ai_enabled'])?0:1;$out['ai_hour']=max(0,min(23,(int)($input['ai_hour']??8)));$out['daily_drafts']=max(1,min(20,(int)($input['daily_drafts']??6)));$out['max_per_feed']=max(1,min(20,(int)($input['max_per_feed']??8)));$out['retention_days']=max(7,min(180,(int)($input['retention_days']??30)));$out['blocked_terms']=sanitize_textarea_field($input['blocked_terms']??$old['blocked_terms']);$out['feeds']=[];foreach((array)($input['feeds']??[]) as $f){$url=esc_url_raw($f['url']??'');if(!$url)continue;$out['feeds'][]=['name'=>sanitize_text_field($f['name']??''),'url'=>$url,'section'=>in_array(($f['section']??''),['politica','tecnologia-ciencia','esporte'],true)?$f['section']:'politica','active'=>empty($f['active'])?0:1];}wp_schedule_single_event(time()+5,'fne_apply_schedule');return $out;}
add_action('fne_apply_schedule','fne_reschedule');

function fne_settings_page(){if(!current_user_can('manage_options'))return;$s=fne_settings();$last=get_option('fne_last_run');$ai_report=get_option('fne_last_ai_report'); ?>
<div class="wrap"><h1>Franklem Editorial <small>v<?php echo esc_html(FNE_VERSION); ?></small></h1><p>Coleta manchetes e resumos de feeds RSS para uma caixa de pautas privada. O plugin não copia artigos completos nem publica notícias.</p><?php if(isset($_GET['fne_run'])&&check_admin_referer('fne_run')){$r=fne_collect_all();echo '<div class="notice notice-success"><p>'.esc_html($r['created']).' pautas criadas; '.esc_html($r['skipped']).' repetidas ignoradas.</p></div>';}if(isset($_GET['fne_key_saved']))echo '<div class="notice notice-success"><p>Configuração da chave atualizada.</p></div>';if(isset($_GET['fne_cleaned']))echo '<div class="notice notice-success"><p>'.esc_html(absint($_GET['fne_cleaned'])).' pautas promocionais antigas foram enviadas para a lixeira.</p></div>';if(($_GET['fne_test']??'')==='ok')echo '<div class="notice notice-success"><p>Conexão confirmada. Os dois modelos estão acessíveis.</p></div>';if(($_GET['fne_test']??'')==='error')echo '<div class="notice notice-error"><p>'.esc_html(sanitize_text_field(wp_unslash($_GET['fne_message']??'Falha na conexão.'))).'</p></div>'; ?>
<h2>OpenAI</h2><p>A chave é criptografada com as chaves de segurança desta instalação e nunca é exibida novamente. Como alternativa avançada, defina <code>FRANKLEM_OPENAI_API_KEY</code> no <code>wp-config.php</code>.</p><table class="form-table"><tr><th>Status</th><td><strong><?php echo fne_api_key()?'Chave configurada':'Não configurada'; ?></strong><br>Texto: <code><?php echo esc_html($s['text_model']); ?></code> · Imagem: <code><?php echo esc_html($s['image_model']); ?></code> · Qualidade média</td></tr></table>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" autocomplete="off"><input type="hidden" name="action" value="fne_save_key"><?php wp_nonce_field('fne_save_key'); ?><label for="fne-openai-key"><strong>Nova chave secreta</strong></label><br><input id="fne-openai-key" type="password" name="openai_key" value="" autocomplete="new-password" style="width:min(620px,100%)" placeholder="sk-proj-…"><p><button class="button button-primary" type="submit">Salvar chave</button><?php if(fne_api_key()): ?> <label style="margin-left:12px"><input type="checkbox" name="remove_key" value="1"> Remover chave armazenada</label><?php endif; ?></p></form>
<?php if(fne_api_key()): ?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="fne_test_key"><?php wp_nonce_field('fne_test_key'); ?><button class="button" type="submit">Testar conexão e modelos</button></form><?php endif; ?><hr>
<p><a class="button button-primary" href="<?php echo esc_url(wp_nonce_url(admin_url('edit.php?post_type=fne_pauta&page=fne-settings&fne_run=1'),'fne_run')); ?>">Executar coleta agora</a> <a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=fne_pauta')); ?>">Ver pautas</a></p>
<?php if($last): ?><p><strong>Última execução:</strong> <?php echo esc_html($last['time']); ?></p><?php endif; ?>
<h2>Última geração automática</h2>
<?php if($ai_report): ?><p><strong><?php echo esc_html($ai_report['started']??''); ?></strong> · <?php echo esc_html($ai_report['status']??''); ?> · <?php echo esc_html(count((array)($ai_report['generated']??[]))); ?> geradas · <?php echo esc_html(count((array)($ai_report['refused']??[]))); ?> não geradas · custo estimado US$ <?php echo esc_html(number_format((float)($ai_report['estimated_usd']??0),3,',','.')); ?></p><ul><?php foreach((array)($ai_report['generated']??[]) as $row): ?><li><strong><?php echo esc_html($row['section']); ?>:</strong> <a href="<?php echo esc_url(get_edit_post_link((int)$row['post'])); ?>"><?php echo esc_html($row['title']); ?></a></li><?php endforeach; ?><?php foreach((array)($ai_report['refused']??[]) as $row): ?><li><strong><?php echo esc_html($row['section']); ?>:</strong> <?php echo esc_html($row['message']); ?></li><?php endforeach; ?></ul><?php else: ?><p>Nenhuma execução automática registrada.</p><?php endif; ?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="fne_cleanup_promos"><?php wp_nonce_field('fne_cleanup_promos'); ?><button class="button" type="submit">Limpar pautas promocionais antigas</button></form><hr>
<form method="post" action="options.php"><?php settings_fields('fne_group'); ?><table class="form-table"><tr><th>Coleta diária</th><td><label><input type="checkbox" name="<?php echo esc_attr(FNE_OPTION); ?>[enabled]" value="1" <?php checked($s['enabled']); ?>> Ativar coleta automática</label></td></tr><tr><th><label for="fne-hour">Horário da coleta</label></th><td><input id="fne-hour" type="number" min="0" max="23" name="<?php echo esc_attr(FNE_OPTION); ?>[hour]" value="<?php echo esc_attr($s['hour']); ?>"> h <p class="description">Usa o fuso horário configurado no WordPress.</p></td></tr><tr><th>Geração diária com IA</th><td><label><input type="checkbox" name="<?php echo esc_attr(FNE_OPTION); ?>[ai_enabled]" value="1" <?php checked($s['ai_enabled']); ?>> Ativar seleção e criação automática de rascunhos</label><p class="description">Cria somente rascunhos. Se uma editoria não tiver pauta verificável, ela não será substituída por outra.</p></td></tr><tr><th><label for="fne-ai-hour">Horário da geração</label></th><td><input id="fne-ai-hour" type="number" min="0" max="23" name="<?php echo esc_attr(FNE_OPTION); ?>[ai_hour]" value="<?php echo esc_attr($s['ai_hour']); ?>"> h</td></tr><tr><th>Máximo de rascunhos por dia</th><td><input type="number" min="1" max="20" name="<?php echo esc_attr(FNE_OPTION); ?>[daily_drafts]" value="<?php echo esc_attr($s['daily_drafts']); ?>"> <p class="description">Valor ajustável de 1 a 20. A automação distribui as pautas de forma equilibrada entre as editorias.</p></td></tr><tr><th>Limite por feed</th><td><input type="number" min="1" max="20" name="<?php echo esc_attr(FNE_OPTION); ?>[max_per_feed]" value="<?php echo esc_attr($s['max_per_feed']); ?>"></td></tr><tr><th>Descartar pautas antigas</th><td><input type="number" min="7" max="180" name="<?php echo esc_attr(FNE_OPTION); ?>[retention_days]" value="<?php echo esc_attr($s['retention_days']); ?>"> dias</td></tr></table>
<h2>Filtro de qualidade</h2><p>Termos separados por vírgula. Pautas comerciais que contenham estes termos serão ignoradas nas próximas coletas.</p><textarea rows="4" style="width:100%" name="<?php echo esc_attr(FNE_OPTION); ?>[blocked_terms]"><?php echo esc_textarea($s['blocked_terms']); ?></textarea>
<h2>Fontes RSS</h2><table class="widefat striped"><thead><tr><th>Ativa</th><th>Nome</th><th>URL</th><th>Editoria</th></tr></thead><tbody><?php for($i=0;$i<8;$i++):$f=$s['feeds'][$i]??['name'=>'','url'=>'','section'=>'politica','active'=>0]; ?><tr><td><input type="checkbox" name="<?php echo esc_attr(FNE_OPTION); ?>[feeds][<?php echo $i; ?>][active]" value="1" <?php checked($f['active']); ?>></td><td><input style="width:100%" name="<?php echo esc_attr(FNE_OPTION); ?>[feeds][<?php echo $i; ?>][name]" value="<?php echo esc_attr($f['name']); ?>"></td><td><input style="width:100%" type="url" name="<?php echo esc_attr(FNE_OPTION); ?>[feeds][<?php echo $i; ?>][url]" value="<?php echo esc_attr($f['url']); ?>"></td><td><select name="<?php echo esc_attr(FNE_OPTION); ?>[feeds][<?php echo $i; ?>][section]"><option value="politica" <?php selected($f['section'],'politica'); ?>>Política</option><option value="tecnologia-ciencia" <?php selected($f['section'],'tecnologia-ciencia'); ?>>Tecnologia & Ciência</option><option value="esporte" <?php selected($f['section'],'esporte'); ?>>Esporte</option></select></td></tr><?php endfor; ?></tbody></table><?php submit_button('Salvar configurações'); ?></form>
<hr><h2>Geração manual</h2><p>Você também pode abrir uma pauta e usar a caixa <strong>Criar notícia com IA</strong>. O fluxo cria texto, revisão e imagem, mas mantém o resultado como rascunho.</p></div><?php }

function fne_admin_notice(){if(get_post_type()!=='fne_pauta')return;if(isset($_GET['fne_error']))echo '<div class="notice notice-error"><p><strong>Não foi possível gerar:</strong> '.esc_html(sanitize_text_field(wp_unslash($_GET['fne_error']))).'</p></div>';echo '<div class="notice notice-info"><p><strong>Franklem Editorial v'.esc_html(FNE_VERSION).':</strong> geração com uma única fonte primária, sem busca web obrigatória. Todo resultado permanece como rascunho para revisão.</p></div>';}
add_action('admin_notices','fne_admin_notice');
