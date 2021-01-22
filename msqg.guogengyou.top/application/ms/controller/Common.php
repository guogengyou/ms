<?php

/**
 * 公共文件, 前置操作，用于检验是否登录： 
 *      JWT校验、没有JWT的要先登陆。

 */
namespace app\ms\controller;
use think\Controller;
use think\Loader;
use think\Db;
use think\Request;

class Common extends Controller {
    
    // 登录验证
    
    public function __construct(\think\Request $request = null) {
        parent::__construct($request);

        
        if (self::check() == false) {      
            //echo('失败');
            //return login/index;  错误写法
            
            $this->error('common check失败请登陆', 'login/index', '', 0);  //和->success一样都是提示并转跳,'user'是控制器
        }    
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
            return false;
        }
        $key = config('salt');
        list($base64,$payload,$signature)=explode(".",$token);
        $payload_array = json_decode(self::base64url_decode($payload), true);     // 解封成关联数组
        
        $input = $base64.".".$payload;
        $newSignature = $this->signature($input,$key,self::$header['alg']);
        $newSignature = self::base64url_encode($newSignature);
        

        
        if($newSignature == $signature && $payload_array['auth'] == "ms"){        // 未被篡改过,且没有跨模块访问
            //echo "<br>未被篡改过,且权限正确<br>";
            return true;
        }
        else {
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
}