<?php
// Isolated unit/smoke test harness. Never contacts ZarinPal or a WordPress site.
error_reporting(E_ALL);
define('ABSPATH','/tmp/');
define('ARRAY_A','ARRAY_A');
define('ZP24_VERSION','2.4.0');
define('ZP24_PLUGIN_FILE','/tmp/GravityForms-master/index.php');
class WP_Error {
    private $code; private $message;
    public function __construct($code,$message,$data=null){$this->code=$code;$this->message=$message;}
    public function get_error_message(){return $this->message;}
    public function get_error_code(){return $this->code;}
}
function is_wp_error($v){return $v instanceof WP_Error;}
function rgar($a,$k,$default=null){return is_array($a)&&array_key_exists($k,$a)?$a[$k]:$default;}
function sanitize_text_field($s){return strip_tags((string)$s);}
function sanitize_email($s){return filter_var($s,FILTER_SANITIZE_EMAIL);}
function is_email($s){return filter_var($s,FILTER_VALIDATE_EMAIL);}
function esc_html($s){return htmlspecialchars((string)$s);}
function esc_url($s){return $s;}
function esc_html__($s,$d=null){return $s;}
function absint($a){return abs((int)$a);}
function wp_salt($s=''){return 'SECRET-SALT';}
function wp_generate_password($l=12,$s=false,$e=false){return str_repeat('t',$l);}
function add_query_arg($params,$url){return $url . '?' . http_build_query($params);}
function home_url($s='/'){return 'https://example.test'.$s;}
function plugin_basename($s){return 'GravityForms-master/index.php';}
function wp_parse_args($a,$d){return array_merge($d,$a);}
function wp_json_encode($s){return json_encode($s);}
function wp_unslash($s){return $s;}
function wp_remote_retrieve_response_code($s){return $s['code'];}
function wp_remote_retrieve_body($s){return $s['body'];}
function current_user_can($s){return true;}
function add_action(){return null;}
function add_filter(){return null;}
function gform_update_meta($id,$k,$v){$GLOBALS['meta'][$id][$k]=$v;return true;}
function gform_get_meta($id,$k){return $GLOBALS['meta'][$id][$k] ?? '';}
function get_option($k,$default=null){return $GLOBALS['options'][$k] ?? $default;}
function update_option($k,$v,$autoload=false){$GLOBALS['options'][$k]=$v;return true;}
function add_option($k,$v,$c='',$a=false){if(array_key_exists($k,$GLOBALS['options']))return false;$GLOBALS['options'][$k]=$v;return true;}
function delete_option($k){unset($GLOBALS['options'][$k]);return true;}
function maybe_unserialize($s){return @unserialize($s)?:$s;}
function wp_remote_post($url,$args){$GLOBALS['calls'][]=['url'=>$url,'body'=>json_decode($args['body'],true),'headers'=>$args['headers']];$payload=array_shift($GLOBALS['responses']);return $payload;}
function mock_response($data){return ['code'=>200,'body'=>json_encode(['data'=>$data,'errors'=>[]])];}
class GFPaymentAddOn {
    protected $_slug;
    private $settings=[];
    public function init(){}
    public function get_plugin_settings(){return $this->settings;}
    public function update_plugin_settings($v){$this->settings=$v;}
    public function product_amount_choices(){return [['label'=>'Form Total','value'=>'form_total']];}
    public function trigger_payment_delayed_feeds($ref,$feed,$entry,$form){$GLOBALS['delayed'][]=['ref'=>$ref,'feed'=>$feed['id'],'form'=>$form['id']];}
    public function complete_payment($entry,$action){$GLOBALS['completions'][]=$action;$entry['payment_status']='Paid';$entry['transaction_id']=$action['transaction_id'];$entry['payment_amount']=$action['amount'];GFAPI::update_entry($entry);return true;}
}
class GFAPI {
    public static function get_entry($id){return $GLOBALS['entries'][$id] ?? false;}
    public static function update_entry($e){$GLOBALS['entries'][$e['id']]=$e;return true;}
    public static function add_note($id,$user,$name,$message){$GLOBALS['notes'][]=$message;}
    public static function get_form($id){return $GLOBALS['forms'][$id] ?? false;}
    public static function get_feeds($a=null,$form=null,$slug=null,$active=null){return array_values(array_filter($GLOBALS['feeds'],function($x)use($form,$slug){return $x['form_id']==$form&&$x['addon_slug']==$slug;}));}
    public static function get_feed($id){return $GLOBALS['feeds'][$id] ?? false;}
    public static function add_feed($form,$meta,$slug){$id=count($GLOBALS['feeds'])+1;$GLOBALS['feeds'][$id]=['id'=>$id,'form_id'=>$form,'addon_slug'=>$slug,'meta'=>$meta,'is_active'=>1];return $id;}
    public static function update_feed_property($id,$k,$v){$GLOBALS['feeds'][$id][$k]=$v;return true;}
}
class MockWpdb {
 public $prefix='wp_'; public $rowset=[]; public function prepare($sql,$v){return sprintf($sql,$v);}
 public function esc_like($s){return $s;}
 public function get_var($q){return 'wp_gf_zarinpal';}
 public function get_results($sql,$mode){return $this->rowset;}
}
$GLOBALS['meta']=[];$GLOBALS['options']=[];$GLOBALS['entries']=[];$GLOBALS['forms']=[49=>['id'=>49,'title'=>'ثبت نام']];$GLOBALS['calls']=[];$GLOBALS['responses']=[];$GLOBALS['notes']=[];$GLOBALS['completions']=[];$GLOBALS['delayed']=[];$GLOBALS['feeds']=[];$GLOBALS['wpdb']=new MockWpdb();
require __DIR__.'/../includes/class-zp24-api.php';
require __DIR__.'/../includes/class-gf-zarinpal-v24.php';
require __DIR__.'/../includes/class-zp24-migrator.php';
$check=function($truth,$desc){if(!$truth){throw new RuntimeException('FAIL: '.$desc);}echo 'PASS '.$desc."\n";};
$addon=ZP24_Gravity_Forms_Addon::get_instance();
$check(ZP24_Gravity_Forms_Addon::normalize_amount('۲۶۰,۰۰۰')===260000,'normalize Persian digits');
$check(ZP24_Gravity_Forms_Addon::normalize_amount('260000.00')===260000,'normalize zero decimals');
$check(is_wp_error(ZP24_Gravity_Forms_Addon::normalize_amount('260000.20')),'reject fractional currency');
$check(is_wp_error(ZP24_Gravity_Forms_Addon::normalize_amount('0')),'reject zero');
$check(is_wp_error(ZP24_Gravity_Forms_Addon::normalize_amount('1e10')),'reject scientific notation');
$check(ZP24_Gravity_Forms_Addon::convert_amount(260000,'IRT','IRR')===2600000,'Toman->Rial');
$check(ZP24_Gravity_Forms_Addon::convert_amount(2600000,'IRR','IRT')===260000,'Rial->Toman');
$check(is_wp_error(ZP24_Gravity_Forms_Addon::convert_amount(11,'IRR','IRT')),'reject lossy currency conversion');
$feed=['id'=>91,'meta'=>['transactionType'=>'product','zp24_description'=>'فرم {form_title}, ورودی {entry_id}','zp24_mobile_field'=>'4']];
$GLOBALS['entries'][17743]=['id'=>17743,'form_id'=>49,'currency'=>'IRT','payment_status'=>'Processing','4'=>'09120000000'];
$GLOBALS['feeds'][91]=['id'=>91,'form_id'=>49,'addon_slug'=>'gravityformszarinpalv24','meta'=>$feed['meta'],'is_active'=>1];
$addon->update_plugin_settings(['merchant_id'=>'12345678-1234-1234-1234-123456789012','source_currency'=>'IRT','gateway_currency'=>'IRT','sandbox'=>true]);
$GLOBALS['responses'][]=mock_response(['code'=>100,'authority'=>'S'.str_repeat('X',35)]);
$url=$addon->redirect_url($feed,['payment_amount'=>260000],$GLOBALS['forms'][49],$GLOBALS['entries'][17743]);
$check(str_contains($url,'sandbox.zarinpal.com/pg/StartPay/S'),'start sandbox payment');
$check($GLOBALS['calls'][0]['body']['amount']===260000&&$GLOBALS['calls'][0]['body']['currency']==='IRT','send proper amount and unit');
$check($GLOBALS['calls'][0]['body']['metadata']['order_id']==='17743','preserve order id');
$check(gform_get_meta(17743,'zp24_state')==='pending','store pending state');
$check(gform_get_meta(17743,'zp24_amount')===260000,'snapshot verified bank amount');
$ref=new ReflectionClass($addon);
$authorized=$ref->getMethod('authorized_entry');
$check((bool)$authorized->invoke($addon,17743,str_repeat('t',48)),'signed callback accepted');
$check(!$authorized->invoke($addon,17743,'wrong-token'),'invalid callback rejected');
$verify=$ref->getMethod('process_verified_payment');
$GLOBALS['responses'][]=mock_response(['code'=>100,'ref_id'=>987654321]);
$verify->invoke($addon,17743,$GLOBALS['entries'][17743]);
$check($GLOBALS['entries'][17743]['payment_status']==='Paid','complete payment after server verification');
$check(gform_get_meta(17743,'zp24_verified_ref_id')==='987654321','store bank Ref ID');
$check(gform_get_meta(17743,'zp24_state')==='paid','mark verified as paid');
$check(count($GLOBALS['completions'])===1,'deliver fulfillment once');
$check($GLOBALS['completions'][0]['transaction_type']==='payment'&&$GLOBALS['completions'][0]['payment_method']==='zarinpal','GF payment action correctly typed');
$check(count($GLOBALS['delayed'])===1&&$GLOBALS['delayed'][0]['ref']==='987654321','dispatch delayed GF feed after verified payment');
$check(gform_get_meta(17743,'zp24_delayed_feeds_done')==='1','durably record delayed feed dispatch');


// Code 101 must not be treated as a new verified payment in absence of local proof.
$GLOBALS['entries'][17744]=['id'=>17744,'form_id'=>49,'currency'=>'IRT','payment_status'=>'Processing'];
foreach(['zp24_merchant'=>'12345678-1234-1234-1234-123456789012','zp24_amount'=>260000,'zp24_source_amount'=>260000,'zp24_authority'=>'A'.str_repeat('X',35)] as $k=>$v)gform_update_meta(17744,$k,$v);
$GLOBALS['responses'][]=mock_response(['code'=>101,'ref_id'=>987654321]);
$verify->invoke($addon,17744,$GLOBALS['entries'][17744]);
$check(gform_get_meta(17744,'zp24_state')==='review'&&count($GLOBALS['completions'])===1,'unmatched 101 never fulfills');
// If gateway is unavailable, leave financial state under review.
$GLOBALS['entries'][17745]=['id'=>17745,'form_id'=>49,'currency'=>'IRT','payment_status'=>'Processing'];
foreach(['zp24_merchant'=>'12345678-1234-1234-1234-123456789012','zp24_amount'=>260000,'zp24_source_amount'=>260000,'zp24_authority'=>'A'.str_repeat('Y',35)] as $k=>$v)gform_update_meta(17745,$k,$v);
$GLOBALS['responses'][]=new WP_Error('http','timeout');
$verify->invoke($addon,17745,$GLOBALS['entries'][17745]);
$check(gform_get_meta(17745,'zp24_state')==='review','timeout must not mark payment failed or paid');
// Error during initiation must have a working receipt token.
$GLOBALS['entries'][17746]=['id'=>17746,'form_id'=>49,'currency'=>'IRT','payment_status'=>'Processing'];
$url=$addon->redirect_url($feed,['payment_amount'=>260000.5],$GLOBALS['forms'][49],$GLOBALS['entries'][17746]);
$check(str_contains($url,'zp24_receipt=17746') && (bool)$authorized->invoke($addon,17746,str_repeat('t',48)),'invalid amount creates authorized error receipt');
// HTTP contract: only 2xx + JSON/data/code can be trusted.
$api=new ZP24_Api(true);
$GLOBALS['responses'][]=['code'=>502,'body'=>'<h1>Unavailable</h1>'];
$bad=$api->verify_payment('12345678-1234-1234-1234-123456789012',260000,'S'.str_repeat('X',35));
$check(is_wp_error($bad),'reject malformed upstream response');
$GLOBALS['responses'][]=['code'=>200,'body'=>json_encode(['errors'=>['code'=>-53,'message'=>'Gateway Error']])];
$bad=$api->verify_payment('12345678-1234-1234-1234-123456789012',260000,'S'.str_repeat('X',35));
$check(is_wp_error($bad),'reject non-success gateway error envelope');
$lock=$ref->getMethod('acquire_lock');
$check($lock->invoke($addon,17743)===true,'acquire atomic callback lock');
$check($lock->invoke($addon,17743)===false,'reject concurrent callback lock');
$GLOBALS['options']['zp24_lock_17743']=(string)(time()-130);
$check($lock->invoke($addon,17743)===true,'recover expired callback lock');
// Initiation timeout returns an authorized error receipt, not an uncontrolled bank URL.
$GLOBALS['entries'][17747]=['id'=>17747,'form_id'=>49,'currency'=>'IRT','payment_status'=>'Processing'];
$GLOBALS['responses'][]=new WP_Error('timeout','Connection timed out');
$url=$addon->redirect_url($feed,['payment_amount'=>260000],$GLOBALS['forms'][49],$GLOBALS['entries'][17747]);
$check(str_contains($url,'zp24_receipt=17747')&&gform_get_meta(17747,'zp24_state')==='error','request timeout receives protected error receipt');
// Currency conversion is persisted at checkout; the snapshot is then used for Verify.
$GLOBALS['entries'][17748]=['id'=>17748,'form_id'=>49,'currency'=>'IRT','payment_status'=>'Processing'];
$addon->update_plugin_settings(['merchant_id'=>'12345678-1234-1234-1234-123456789012','source_currency'=>'IRT','gateway_currency'=>'IRR','sandbox'=>true]);
$GLOBALS['responses'][]=mock_response(['code'=>100,'authority'=>'S'.str_repeat('Z',35)]);
$addon->redirect_url($feed,['payment_amount'=>260000],$GLOBALS['forms'][49],$GLOBALS['entries'][17748]);
$check(gform_get_meta(17748,'zp24_amount')===2600000&&gform_get_meta(17748,'zp24_source_amount')===260000,'immutable source and bank units stored separately');
$check($GLOBALS['calls'][count($GLOBALS['calls'])-1]['body']['currency']==='IRR','currency sent explicitly as IRR');
// Source compatibility: legacy feed mapping without destructive database operations.
$GLOBALS['wpdb']->rowset=[['id'=>7,'form_id'=>49,'is_active'=>1,'meta'=>serialize(['desc_pm'=>'پیش پرداخت {form_title}','customer_fields_mobile'=>'4'])]];
$GLOBALS['options']['gf_zarinpal_settings']=['merchent'=>'12345678-1234-1234-1234-123456789012'];
$GLOBALS['options']['zp24_needs_migration']='1';
$GLOBALS['options']['gf_zarinpal_configured']=true;
ZP24_Migrator::maybe_migrate();
$check(count($GLOBALS['feeds'])===2&&count($GLOBALS['options']['zp24_migration_map'])===1,'legacy feed migrated to GF native feed');
ZP24_Migrator::maybe_migrate();
$check(count($GLOBALS['feeds'])===2,'legacy migration idempotent');

$check(get_option('zp24_needs_migration')==='0','migration marked complete');
$check(get_option('gf_zarinpal_settings')['merchent']==='12345678-1234-1234-1234-123456789012','old merchant settings retained');
echo 'ALL TESTS PASSED'."\n";
