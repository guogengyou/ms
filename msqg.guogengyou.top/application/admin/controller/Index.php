<?php

/**
 * 后台首页(需登录): 
 *      商品信息及修改、修改信息
 * 
 * 由父类Common的构造函数自动检查登录
 */
 
//必须要有，否则报错：  控制器不存在 :app\index\controller\Index 
namespace app\admin\controller;  
use think\Controller;               // 这是一对
use think\Loader;
use think\Db;
use think\Request;

//use PHPMailer\PHPMailer\PHPMailer;

class Index extends Common            // 登陆验证，拦截功能
{  
    public function index(){
        
        //同步redis（抢购开始后不能修改）
        $time = strtotime("2021-05-01 14:00:00");
        if(time() <  $time){
            self::update_redis_goods();
        }

        $info = Db('goods')->select();
        $this->assign('lists', $info);
        
        return $this->fetch('');
        
    }

    //每次修改商品、后台登录后，都同步redis中商品优惠数量、库存
    private function update_redis_goods(){
        $info = Db::table('goods')->where('id',1)->find ();
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->set("discount_number", $info['discount_number']);     
        $redis->set("stock_number", $info['stock_number']);     
        return;
    }

    public function test2() {
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $x = $redis->get("discount_number");    
        //$x = $redis->set("discount_number", $x); 
    }
    
    // 修改商品信息
    public function change_goods(){
        //同步redis（抢购开始后不能修改）
        $time = strtotime("2021-05-01 14:00:00");
        if(time() >  $time){
            return $this->error('已经开始抢购，不能修改');
        }
        
        $weight = input('weight');
        $discount_number = input('discount_number');
        $discount_price = input('discount_price');
        $normal_price = input('normal_price');
        $stock_number = input('stock_number');
        
        //****验证数据合法性：邮箱、密码
        
        $info = Db::table('goods')->where('id',1)->find ();
        $str1 = '智利进口车厘子';
        $str2 = '斤装新鲜水果当季樱桃特大整箱顺丰包邮每人限购1次';
        $str3 = '5月1日14点开抢！前';
        $str4 = '名享特优价 ';
        $str5 = '元，其余享优惠价 ';
        $str6 = '元。';
        $title = $str1.$weight.$str2;
        $detail = $str3.$discount_number.$str4.$discount_price.$str5.$normal_price.$str6;
        
        Db::table('goods')->where('id', 1)->update(['weight' => $weight, 'discount_number' => $discount_number, 'discount_price' => $discount_price,'normal_price' => $normal_price,'stock_number' => $stock_number,'title' => $title,'detail' => $detail]);
        
        //echo "change_goods";
        $info = Db('goods')->select();
        $this->assign('lists', $info);
        
        return $this->success('修改商品信息成功', 'index/index');
    } 
    
    public function change_userinfo() {
        // 发送邮件
        $c = controller(Login);
        $c->email();
        return $this->fetch();
    }
    public function dochange_userinfo() {

        $code = input('code');
        $password = input('password');
        
        // 解封web token，获取信息
        $c = controller(Common);
        $payload = $c->token_payload();
        $email = $payload->email;
        
        // 操作数据库
        $info = Db::table('admin')->where('id',1)->find () ;
        
        
        if($email != $info['email'] || $code != $info['email_code']) {       
            return $this->error('验证码或邮箱错误');
        }
        else {
            cookie('token', null);      // 清除旧密码颁发的token
            //md5 加盐
            $salt = config('salt');
            $password = md5( md5($password).$salt );
            Db::table('admin')->where('id',1)->setField('password', $password);
            return $this->success('修改密码成功', 'login/index');
        }
    }

}