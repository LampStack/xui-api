<?php

class v2Ray{
    private $host;
	private $port;
    private $username;
    private $password;

    function __construct($host, $port, $username, $password) {
        $this->host = $host;
		$this->port = $port;
        $this->username = $username;
        $this->password = $password;
        if(!file_exists('./.cookie.txt')) $this->login();
    }

    private function login(){
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://'.$this->host.':' . $this->port . '/login',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_COOKIEFILE => './.cookie.txt',
        CURLOPT_COOKIEJAR => './.cookie.txt',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'password='.$this->password.'&username='.$this->username.'',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    
    private function guidv4($data = null) {
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    private function RandomString($len=7){
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $randstring = null;
        for($i = 0; $i < $len; $i++)
            $randstring .= $characters[rand(0, strlen($characters))];
        return $randstring;
    }
    
    public function users(){
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://'.$this->host.':' . $this->port . '/xui/inbound/list',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_COOKIEFILE => './.cookie.txt',
        CURLOPT_COOKIEJAR => './.cookie.txt',
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        ));
        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        return $response['obj'];
    }
    
    public function delete($uid){
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'http://'.$this->host.':' . $this->port . '/xui/inbound/del/' . $uid,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_COOKIEFILE => './.cookie.txt',
        CURLOPT_COOKIEJAR => './.cookie.txt',
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        ));
        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        return $response;
    }
    
    public function genVmess($remark = null, $port = null, $traffic = 0){
        if($remark == null) $remark = $this->RandomString();
        if($port == null) $port = rand(11111, 99999);
        $traffic = $traffic * 1024 * 1024 * 1024;
        $guidv4 = $this->guidv4();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://'.$this->host.':' . $this->port . '/xui/inbound/add'); 
        curl_setopt($ch, CURLOPT_COOKIEFILE, './.cookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEJAR, './.cookie.txt');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'up=0&down=0&total='.$traffic.'&remark='.$remark.'&enable=true&expiryTime=0&listen=&port='.$port.'&protocol=vmess&settings={
  "clients": [
    {
      "id": "'.$guidv4.'",
      "alterId": 0
    }
  ],
  "disableInsecureEncryption": false
}&streamSettings={
  "network": "tcp",
  "security": "none",
  "tcpSettings": {
    "header": {
      "type": "none"
    }
  }
}&sniffing={
  "enabled": false,
  "destOverride": [
    "http",
    "tls"
  ]
}');
        $response = curl_exec($ch);
        curl_close ($ch);
        usleep(150000);
        $users = $this->users();
        $myJson = [];
        $myJson['type'] = 'vmess';
        $myJson['id'] = $users[count($users) - 1]['id'];
        $myJson['link'] = 'vmess://' . base64_encode('{
  "v": "2",
  "ps": "'.$remark.'",
  "add": "'.$this->domain.'",
  "port": '.$port.',
  "id": "'.$guidv4.'",
  "aid": 0,
  "net": "ws",
  "type": "none",
  "host": "",
  "path": "/",
  "tls": "none"
}');
        return $myJson;
    }

    public function genVless($remark = null, $port = null, $traffic = 0){
        if($remark == null) $remark = $this->RandomString();
        if($port == null) $port = rand(11111, 99999);
        $traffic = $traffic * 1024 * 1024 * 1024;
        $guidv4 = $this->guidv4();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://'.$this->host.':' . $this->port . '/xui/inbound/add'); 
        curl_setopt($ch, CURLOPT_COOKIEFILE, './.cookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEJAR, './.cookie.txt');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'up=0&down=0&total='.$traffic.'&remark='.$remark.'&enable=true&expiryTime=0&listen=&port='.$port.'&protocol=vless&settings={
  "clients": [
    {
      "id": "'.$guidv4.'",
      "flow": "xtls-rprx-direct"
    }
  ],
  "decryption": "none",
  "fallbacks": []
}&streamSettings={
  "network": "tcp",
  "security": "none",
  "tcpSettings": {
    "header": {
      "type": "none"
    }
  }
}&sniffing={
  "enabled": false,
  "destOverride": [
    "http",
    "tls"
  ]
}');
        $response = curl_exec($ch);
        curl_close ($ch);
        usleep(150000);
        $users = $this->users();
        $myJson = [];
        $myJson['type'] = 'vless';
        $myJson['id'] = $users[count($users) - 1]['id'];
        $myJson['link'] = 'vless://'.$guidv4.'@'.$this->host.':'.$port.'?type=ws&security=none&path=/#'.$remark;
        return $myJson;
    }
}
