<?php

/**
 * 后台公共文件, 前置操作，用于检验是否登录： 
 *      JWT校验、没有JWT的要先登陆。

 */
namespace app\admin\controller;
use think\Controller;
use think\Loader;
use think\Db;
use think\Request;

class Common extends Controller {
    
    // 登录验证
    
    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        if (self::check() == false) {      
            $this->error('common check失败请登陆', 'login/index', '', 0);  //和->success一样都是提示并转跳,'user'是控制器
        }    
        //记录日志
        $this->_addLog();
    }
        

    // JWT
    private static $header=array(
        'alg'=>'SHA256', //生成signature的算法
        'typ'=>'JWT'  //类型
    );
    private function base64url_encode($plainText) {
        $base64 = base64_encode($plainText);
        $base64url = strtr($base64, '+/=', '-_,');
        return $base64url;  
    }
    private function base64url_decode($plainText) {
        $base64url = strtr($plainText, '-_,', '+/=');
        $base64 = base64_decode($base64url);
        return $base64;  
    }
    private function signature($input,$key,$alg){
        $base64 = hash_hmac($alg,$input,$key,true);
        return $base64;
    }
    //校验客户端提供的 JWT
    public function check(){
        
        //本函数虽然是被调用，但依旧以可读 Request
        //$authorization = Request::instance()->header('Authorization'); 
        //$header = input('server.HTTP_AUTHORIZATION');
            
        //$authorization = cookie('token');
        //list($bearer,$token)=explode(" ",$authorization);

        $token = cookie('token');
        
        if(strlen($token) == 0){
            //echo "token空的";
            return false;
        }
        $key = config('salt');
        list($base64,$payload,$signature)=explode(".",$token);
        $payload_array = json_decode(self::base64url_decode($payload), true);     // 解封成关联数组
        
        $input = $base64.".".$payload;
        $newSignature = $this->signature($input,$key,self::$header['alg']);
        $newSignature = self::base64url_encode($newSignature);
        
        //var_dump($newSignature);
        //echo "<br><br>";
        //var_dump($signature);
        
        if($newSignature == $signature && $payload_array['auth'] == "admin"){        // 未被篡改过,且没有跨模块访问
            //echo "<br>未被篡改过,且权限正确<br>";
            return true;
        }
        else {
            //echo "<br>token篡改<br>";
            return false;
        }
    }

    //解封token，得到 用户的学号、过期时间；返回格式为对象
    public function token_payload(){
        //本函数虽然是被调用，但依旧以可读 Request
        //$authorization = Request::instance()->header('Authorization'); 
        
        $token = cookie('token');
        list($base64,$payload,$signature)=explode(".",$token);
        
        $payloadArray = self::base64url_decode($payload);
        $Obj = json_decode($payloadArray);			// 解封成对象
        
        return $Obj;
    }
    //记录日志
    private function _addLog() {
        $data = array();
        $data['querystring'] = request()->query()?'?'.request()->query():'';
        $data['c'] = request()->controller();
        $data['a'] = request()->action();
        $data['ip'] = ip2long(request()->ip());
	    $data['time'] = time();
        $arr = array('Index/change_goods','Index/dochange_userinfo');
        if (!in_array($data['c'].'/'.$data['a'], $arr)) {
            db('admin_log')->insert($data);
        } 
    }
}