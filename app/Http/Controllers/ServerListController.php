<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

class ServerListController extends Controller
{

    public function listServers() {
        if (!isset($_GET['account'])) {
            return 100;
        }

        $loginURL = env('LOGIN_URL');
        $platform = 'dgcq';

        $res = array();

        $data = $_GET;

        $db = \DB::connection('mysql2');

        if (array_key_exists('srvid', $_GET)) {
            $srv_m = $db->table('serverlist')->where([
                ['account', '=', $data['account']],
                ['srvid', '=', $data['srvid']]
            ])->select('idx')->get();
            if (count($srv_m)) {
                $srv_u = $db->table('serverlist')->where([
                    ['idx', '=', $srv_m[0]->idx]
                ])->update(['updatetime' => now()]);
            } else {
                $d = date("Y-m-d H:i:s", time());
                $srv_u = $db->table('serverlist')->insert([
                    'account' => $data['account'],
                    'srvid' => $data['srvid'],
                    'updatetime' => now()
                ]);
            }

            return 0;
        } else {
            $srv_s = $db->table('serverlist')->where([
                ['account', '=', $data['account']]
            ])->select('srvid')->orderByDesc('updatetime')->limit(10)->get();
            
            foreach($srv_s as $ss) {
                $res['login'][] = $ss->srvid;
            }



            $db2 = \DB::connection('mysql3');

            $srv_a = $db2->table('serverroute')->where([
                ['list', '=', 1]
            ])->select('id', 'serverid', 'hostname', 'port', 'name', 'opentime', 'realid', 'status', 'list')->orderBy('serverid', 'asc')->get();

            foreach($srv_a as $ss) {
                $opentime = $ss->opentime;
                $opentime_t = strtotime($opentime);
                $now = time();
                if ($opentime_t <= $now) {
                    $status = $ss->status;
                    if ($now - $opentime_t > 86400 && $status == 0) {
                        $ss->status = 1;
                    }
                    $maintain = $db2->table('serverroute')->where([
                        ['status', '=', 2]
                    ])->select('*')->orderByDesc('serverid')->get();
                    foreach($maintain as $mt) {
                        if ($ss->serverid >= $mt['begin'] && $ss->serverid <= $mt['end']) {
                            $ss->status = 2;
                        }
                    }
                    $ss->name = 's'.$ss->serverid.'.'.$ss->name;
                    $ss->pf = $platform;
                    $ss->url = $loginURL;
                    $ss->loadurl = '';
                    $res['serverlist'][] = $ss;
                } else {
                    $whitelist = $db2->table('serverroute')->where([
                        ['status', '=', 69]
                    ])->select('*')->orderByDesc('serverid')->get();
                    if (in_array($ip, $whitelist)) {
                        $res['serverlist'][] = $ss;
                    }
                }
            }
        }

        $res = urldecode(json_encode($res));
		
        return $res;
    }
	
}
