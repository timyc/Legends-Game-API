<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Config;

class LoginController extends Controller
{
    public static function getIp() {
        $ip = '';
        //strcasecmp 比较两个字符，不区分大小写。返回0，>0，<0。
        if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $res =  preg_match ( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
        return $res;
    }

    public function doLogin() {
        $data = $_GET;
        if (!array_key_exists('account', $data)) {
            return 0;
        }
        $account = $data['account'];
        if (!isset($account)) {
            return 100;
        }
        $getPwd = 0;
        if (array_key_exists('pwd', $data)) {
            $getPwd = 1;
        }
        $pf = '';
        if (array_key_exists('channel_id', $data) && $data['channel_id']) {
            $pf = $data['channel_id'];
        }

        $db2 = \DB::connection('mysql3');
        $srv_get = $db2->table('serverroute')->where([
            ['realid', '=', $data['sid']]
        ])->select('db')->first();

        if (!$srv_get) {
            return 100;
        }

        Config::set("database.connections.mysql.database", $srv_get->db);
        DB::purge('mysql');

        $db = \DB::connection('mysql');

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            die("Server does not exist at the moment!");
        }

        $user_get = $db->table('globaluser')->where([
            ['account', '=', $account]
        ])->select('account')->first();

        $ip = strval(self::getIp());

        if ($getPwd == 0) {
            if (!$user_get) {
                $passwd = $data['passwd'];
                if (!isset($passwd)) {
                    return 100;
                }
                $upwd = random_bytes(15);
                $user_create = $db->table('globaluser')->insert([
                    'account' => $account,
                    'passwd' => md5(strrev($account)),
                    'identity' => '430481198112113256',
                    'gmlevel' => '0',
                    'createtime' => now(),
                    'pf' => $pf,
                    'ipstr' => $ip
                ]);
                if (!$user_create) {
                    return 100;
                }
            } else {
                $user_update = $db->table('globaluser')->where('account', $user_get->account)->update(['ipstr' => $ip]);
            }
            return 0;
        } else {
            if (!$user_get) {
                return 100;
            }
            return 0;
        }


    }
	
}
